# API mobile — module client

API REST consommée par l'application mobile Maisha Bora. Elle vit dans ce projet Laravel, sous le préfixe `/api/v1/client`, et n'utilise ni session ni cookie : le mobile s'identifie avec un jeton Sanctum.

## Tranche 1 — Socle et connexion

### Règles de sécurité

- **Identifiant** : le code membre (`users.code`, unique). Le téléphone n'est pas unique et ne peut pas servir d'identifiant.
- **Code court** : le membre peut taper son code complet ou seulement son numéro (voir « Code court » ci-dessous).
- **Seuls les comptes `role = membre`** passent par cette API. Le personnel est refusé avec la même réponse qu'un mauvais mot de passe.
- **Accès mobile activé en agence** : tant que `users.mobile_activated_at` est vide, la connexion est refusée (403), même avec le bon mot de passe.
- **Mot de passe par défaut `1234` ou mot de passe temporaire** : la connexion réussit mais délivre un jeton *limité* (capacité `password:change`). Avec ce jeton, toutes les routes de données répondent 403 `password_change_required`. Le drapeau `users.must_change_password` passe à vrai.
- **Force brute** : 5 échecs par couple code + adresse IP bloquent la connexion 5 minutes (429).
- **Un jeton par appareil**, valable 30 jours. Se reconnecter sur le même appareil remplace l'ancien jeton.
- **Contrôle à chaque requête** : un membre désactivé ou suspendu après sa connexion perd son jeton à la requête suivante.
- Les événements de connexion sont écrits dans le journal `daily`, comme pour la connexion web.

### Code court

Le code complet a la forme `34` + année + numéro sur 10 chiffres (`3420250000000258`). Le numéro ne repart jamais à zéro, il identifie donc le membre à lui seul. La classe `App\Support\MemberCode` en tire un code court `MB000258`, renvoyé par l'API dans le champ `short_code`.

| Le membre tape | Compris comme |
|---|---|
| `258`, `000258` | numéro 258 |
| `MB000258`, `mb 258`, `MB-258` | numéro 258 |
| `3420250000000258` | code complet |

- Rien ne change en base : `users.code` garde le code complet.
- Si un numéro correspond à zéro ou à plusieurs membres, la connexion est refusée comme un mauvais mot de passe.
- Toutes les écritures d'un même code partagent le même compteur d'essais : varier l'écriture ne contourne pas le blocage.
- Un code non standard (par exemple avec un espace ou une tabulation enregistrés par erreur) n'a pas de code court ; il doit être corrigé en base.

### Routes

| Méthode | Route | Protection | Rôle |
|---|---|---|---|
| POST | `/api/v1/client/auth/login` | publique, limitée | Délivre un jeton |
| POST | `/api/v1/client/auth/logout` | jeton | Révoque le jeton de l'appareil |
| GET | `/api/v1/client/me` | jeton complet | Profil du membre |

### Connexion

Requête :

```json
{ "code": "MB000258", "password": "…", "device_name": "Tecno Spark 10" }
```

Réponse 200 :

```json
{
  "token": "12|abc…",
  "token_type": "Bearer",
  "expires_at": "2026-12-13T10:00:00+00:00",
  "must_change_password": false,
  "client": { "id": 1, "code": "3420250000000258", "short_code": "MB000258", "full_name": "…", "agent": { "full_name": "…", "telephone": "…" } }
}
```

Les requêtes suivantes envoient l'en-tête `Authorization: Bearer <token>` et `Accept: application/json`.

### Codes d'erreur

| HTTP | `code` | Signification |
|---|---|---|
| 401 | — | Jeton absent, invalide ou expiré |
| 403 | `access_denied` | Compte inactif, suspendu ou accès mobile non activé |
| 403 | `password_change_required` | Le mot de passe doit être changé avant tout accès |
| 403 | `temporary_password_expired` | Le code provisoire a expiré : nouveau code à demander en agence |
| 422 | `invalid_credentials` | Code membre ou mot de passe incorrect |
| 422 | `invalid_current_password` | Mot de passe actuel incorrect (changement de mot de passe) |
| 422 | — | Champs manquants (détail dans `errors`) |
| 429 | `too_many_attempts` | Trop d'échecs, voir `retry_after` (secondes) |

### Fichiers

| Fichier | Rôle |
|---|---|
| `routes/api.php` | Déclaration des routes de l'API |
| `bootstrap/app.php` | Charge `routes/api.php`, alias des middlewares, erreurs en JSON pour `api/*` |
| `app/Models/User.php` | Trait `HasApiTokens`, casts des deux nouvelles colonnes |
| `app/Http/Controllers/Api/V1/Client/AuthController.php` | `login`, `logout`, `me` |
| `app/Http/Requests/Api/V1/Client/LoginRequest.php` | Validation des champs de connexion |
| `app/Http/Resources/Api/V1/Client/ClientProfileResource.php` | Champs du membre exposés au mobile |
| `app/Support/MemberCode.php` | Lecture du code complet ou court, formatage `MB000258` |
| `app/Http/Middleware/EnsureActiveClient.php` | Compte membre toujours autorisé |
| `app/Http/Middleware/EnsurePasswordChanged.php` | Bloque les données tant que le mot de passe n'est pas changé |
| `config/sanctum.php` | Configuration Sanctum (valeurs par défaut) |
| `database/migrations/2026_11_13_090000_create_personal_access_tokens_table.php` | Nouvelle table des jetons |
| `database/migrations/2026_11_13_100000_add_mobile_access_to_users_table.php` | Colonnes `mobile_activated_at`, `must_change_password` |
| `tests/Feature/Api/V1/Client/AuthTest.php` | Tests de la connexion |
| `tests/Unit/MemberCodeTest.php` | Tests du formatage du code membre |

### Mise en service

Les deux migrations sont additives (une nouvelle table, deux colonnes). Elles se lancent avec :

```bash
php artisan migrate
```

Lancer les tests de la tranche (base SQLite en mémoire) :

```bash
php artisan test tests/Feature/Api/V1/Client tests/Feature/MobileAccessTest.php tests/Unit/MemberCodeTest.php
```

## Tranche 2 — Activation en agence et changement de mot de passe

### Parcours du membre

1. **En agence**, un utilisateur ayant la permission `gerer-acces-mobile` ouvre la fiche du membre. Le bloc **Accès application mobile** affiche son identifiant (`MB000258`) et un bouton **Activer l'accès mobile**.
2. L'activation génère un **code provisoire à 6 chiffres**, affiché une seule fois dans une fenêtre. L'agent le remet au membre en main propre. En base, seule l'empreinte (hash) du code est enregistrée.
3. Le code est valable **72 heures**. Passé ce délai, la connexion est refusée (`temporary_password_expired`) et il faut un nouveau code.
4. Dans l'application, le membre se connecte avec `MB000258` et le code. Il reçoit un jeton limité : seule la route de changement de mot de passe lui est ouverte.
5. Il choisit son mot de passe. Il reçoit un nouveau jeton complet et accède enfin à ses données.

Le même bouton (**Nouveau code provisoire**) sert quand un membre a oublié son mot de passe. **Désactiver** coupe l'accès et déconnecte tous ses appareils. Chaque action est tracée dans le journal des utilisateurs (`activation_acces_mobile`, `nouveau_code_acces_mobile`, `desactivation_acces_mobile`), sans jamais y écrire le code.

### Règles du mot de passe (`App\Rules\ClientPassword`)

- 6 caractères minimum, 64 maximum, confirmation identique, différent de l'actuel.
- Refusés : un même caractère répété (`111111`), une suite (`123456`, `654321`, `abcdef`), les mots courants (`azerty`, `1234`…).
- Refusés : la date de naissance du membre (`15031990`, `19900315`, `150390`), les 6 derniers chiffres de son téléphone, son code membre ou son numéro court (`000258`, `MB000258`).

### Route

| Méthode | Route | Protection | Rôle |
|---|---|---|---|
| PUT | `/api/v1/client/password` | jeton limité ou complet, 5 essais/minute | Change le mot de passe |

Requête :

```json
{ "current_password": "482913", "password": "Kivu#2026", "password_confirmation": "Kivu#2026" }
```

Réponse 200 : même forme que la connexion (`token`, `expires_at`, `must_change_password: false`, `client`). **Tous les autres appareils sont déconnectés** ; l'appareil qui a fait le changement reçoit un nouveau jeton et doit remplacer l'ancien.

Les erreurs de règles arrivent en 422 avec le détail dans `errors.password`.

### Fichiers

| Fichier | Rôle |
|---|---|
| `app/Services/MobileAccessService.php` | Activer / désactiver l'accès, générer le code provisoire |
| `app/Services/ClientTokenIssuer.php` | Création des jetons (connexion et changement de mot de passe) |
| `app/Livewire/Members/MobileAccess.php` + `resources/views/livewire/members/mobile-access.blade.php` | Bloc de la fiche membre |
| `resources/views/livewire/members/member-details.blade.php` | Insertion du bloc, visible avec la permission `gerer-acces-mobile` |
| `app/Http/Controllers/Api/V1/Client/PasswordController.php` | Changement de mot de passe |
| `app/Http/Requests/Api/V1/Client/ChangePasswordRequest.php` | Validation du changement |
| `app/Rules/ClientPassword.php` | Refus des mots de passe faibles |
| `app/Models/User.php` | Cast `temporary_password_expires_at`, méthode `temporaryPasswordExpired()` |
| `database/migrations/2026_11_13_110000_add_temporary_password_expiry_to_users_table.php` | Colonne `temporary_password_expires_at` |
| `database/migrations/2026_11_13_110001_add_mobile_access_permission.php` | Permission `gerer-acces-mobile` (rôles Admin et SUPER IT) |
| `tests/Concerns/BuildsClientApiSchema.php` | Schéma SQLite et membre de test partagés |
| `tests/Feature/Api/V1/Client/PasswordTest.php` | Tests du changement de mot de passe |
| `tests/Feature/MobileAccessTest.php` | Tests de l'activation en agence |

### Mise en service

Deux nouvelles migrations additives (une colonne nullable, une permission insérée avec `insertOrIgnore`) :

```bash
php artisan migrate
```

Pour donner la permission à un autre rôle que Admin et SUPER IT (par exemple les réceptionnistes), passer par l'écran de gestion des rôles.

## Tranche 3 — Soldes et historique

### Routes

| Méthode | Route | Protection | Rôle |
|---|---|---|---|
| GET | `/api/v1/client/accounts` | jeton complet | Comptes ouverts et soldes |
| GET | `/api/v1/client/transactions` | jeton complet | Historique paginé de tous les comptes |

### Comptes

Seuls les comptes au statut `Actif` du membre connecté sont renvoyés, triés courant puis épargne, CDF puis USD. Les devises ne sont jamais additionnées.

```json
{
  "data": [
    { "id": 12, "type": "current", "type_label": "Compte courant", "currency": "CDF",
      "balance": "15000.00", "balance_hidden": false, "updated_at": "2026-11-16T09:12:00+01:00" }
  ],
  "balance_hidden": false
}
```

**Soldes masqués** : si l'agence a cliqué « Masquer les soldes » sur la fiche du membre (`users.visible_account = false`), `balance` et `balance_after` valent `null` et `balance_hidden` vaut `true`. C'est la même règle que sur la fiche web et le relevé PDF. Les opérations restent visibles.

### Historique

Filtres facultatifs, combinables :

| Paramètre | Exemple | Effet |
|---|---|---|
| `account_id` | `12` | Un seul compte (404 `account_not_found` s'il n'est pas au membre ou s'il est fermé) |
| `from`, `to` | `2026-10-01` | Période, bornes incluses |
| `direction` | `credit` ou `debit` | Entrées ou sorties d'argent |
| `per_page` | `20` (max 50) | Taille de page |
| `page` | `2` | Page demandée |

```json
{
  "data": [
    { "id": 981, "account_id": 12, "date": "2026-11-15T10:30:00+01:00", "type": "retrait",
      "label": "Retrait", "direction": "debit", "amount": "400.00", "currency": "CDF",
      "balance_after": "600.00", "description": "Retrait au guichet" }
  ],
  "links": { "next": "…/transactions?page=2" },
  "meta": { "current_page": 1, "last_page": 4, "per_page": 20, "total": 73 }
}
```

- L'historique passe **toujours par les comptes du membre** (`transactions.account_id`). `transactions.user_id` désigne l'agent qui a saisi l'opération, pas le client.
- Le montant est toujours positif ; `direction` dit si l'argent entre (`credit`) ou sort (`debit`). Un type absent de la table renvoie `direction: null` : l'app l'affiche sans signe.

### Table des types (`App\Support\TransactionType`)

| Sens | Types |
|---|---|
| Crédit | `depot` / `dépôt`, `mise_quotidienne`, `mise_quotidienne_multiple`, `transfert_entrant`, `virement_caisse_entrant`, `octroi_de_credit`, `paie_entrant`, `conversion_entree_client`, `echeance_compte_terme`, `retrait_anticipe_compte_terme` |
| Débit | `retrait`, `retrait_carte_adhesion`, `transfert_sortant`, `remboursement_de_credit`, `penalite_de_credit`, `frais_mutuelle`, `commission_credit`, `conversion_sortie_client`, `placement_compte_terme`, `annulation_paie` |
| Selon la description | `rectification_solde` (« SOLDE: + » ou « SOLDE: - ») |
| Sans signe | `frais_adhesion` (payés au guichet, le solde du compte ne bouge pas), `regularisation` (origine inconnue), tout type absent de la table |
| **Exclus de l'historique** | `Entrée de fonds`, `Sortie de fonds`, `octroi_de_credit_client` |

La table a été vérifiée le 2026-11-16 contre les 22 types présents en production sur des comptes membres.

**Écritures exclues** : l'ancien code (jusqu'en juin 2025) enregistrait des opérations de la caisse principale avec un `account_id` de compte membre et le **solde de la caisse principale** dans `balance_after` :

- `Entrée de fonds` (22 lignes) : `account_id` recevait l'id de la caisse principale, donc la ligne est rattachée au compte membre portant le même numéro ;
- `octroi_de_credit_client` (13 lignes) : doublon côté caisse de `octroi_de_credit`, qui reste, lui, visible.

Ces lignes ne sont jamais envoyées au mobile. Elles restent visibles sur la fiche web du membre et dans son relevé PDF (voir « Points ouverts »).

Pour revérifier la liste des types en production (lecture seule) :

```sql
SELECT type, COUNT(*) FROM transactions WHERE account_id IS NOT NULL GROUP BY type ORDER BY 2 DESC;
```

### Points ouverts

- La fiche web du membre et le relevé PDF affichent encore les 35 écritures de caisse ci-dessus, avec le solde de la caisse principale. Pour lister les membres concernés (lecture seule) :

  ```sql
  SELECT a.user_id, t.type, COUNT(*) FROM transactions t JOIN accounts a ON a.id = t.account_id
  WHERE t.type IN ('Entrée de fonds', 'Sortie de fonds', 'octroi_de_credit_client') GROUP BY a.user_id, t.type;
  ```

### Fichiers

| Fichier | Rôle |
|---|---|
| `app/Http/Controllers/Api/V1/Client/AccountController.php` | Liste des comptes |
| `app/Http/Controllers/Api/V1/Client/TransactionController.php` | Historique et filtres |
| `app/Http/Requests/Api/V1/Client/TransactionIndexRequest.php` | Validation des filtres |
| `app/Http/Resources/Api/V1/Client/AccountResource.php` | Format d'un compte |
| `app/Http/Resources/Api/V1/Client/TransactionResource.php` | Format d'une opération |
| `app/Support/TransactionType.php` | Libellé et sens de chaque type |
| `app/Support/Money.php` | Montants en texte à 2 décimales |
| `app/Models/Account.php` | Scope `active()` |
| `tests/Feature/Api/V1/Client/AccountsTest.php` | Tests de la tranche |

Aucune migration dans cette tranche.

## Tranches suivantes

4. Crédits et échéancier de remboursement.
