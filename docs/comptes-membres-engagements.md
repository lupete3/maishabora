# Comptes membres : ce que nous devons aux clients (20/11/2026)

Page : **`/comptes-membres`**. Écran de classement : **`/comptes-membres/classement`**.

## Bonnes pratiques retenues
- **Ce que nous devons aux clients** (engagements envers la clientèle) = **somme des soldes positifs des comptes de vrais clients**, par devise. On y inclut les comptes actifs, **inactifs** et **dormants** : l'argent reste dû au client même si le compte ne bouge plus.
- **Exclus** : les comptes **internes** de l'institution (retenues, associés, commissions…) et les comptes **de test**.
- **Soldes négatifs** : ce n'est **pas une dette envers le client**, mais un découvert ou une anomalie. Ils sont affichés **à part**, à régulariser, et ne sont **pas soustraits** du total dû.
- **Deux notions séparées** :
  - le cycle de vie du compte : actif, inactif, dormant ;
  - la nature du titulaire : client, interne, test.
- **Dormant = calculé** : aucune opération sur le compte depuis 12 mois. Le seuil se règle dans `config/comptes.php` (`COMPTES_DORMANT_APRES_MOIS`), et rien n'est enregistré.

## Phase 1 : rapport (lecture seule)
- Bloc **« Ce que nous devons aux clients »** en haut de la page. Il porte sur **toute l'institution**, sans tenir compte des filtres. On y trouve, par devise :
  - le montant dû et le nombre de comptes à solde positif ;
  - les **soldes négatifs à régulariser** (montant et nombre) ;
  - le montant **non compté** sur les comptes internes et de test.
- Filtre **« Catégorie de comptes »** :
  - tous ;
  - actifs (statut « Actif ») ;
  - inactifs (statut « Inactif ») ;
  - **dormants** (calculés) ;
  - **soldes négatifs**.
- Filtre **« Nature du titulaire »** : **Client** (par défaut), Compte interne, Compte de test, ou toutes natures.
- Les 4 cartes de totaux existantes deviennent « Totaux de la sélection ». Elles suivent les filtres, comme avant.
- **Exports PDF et Excel** : mêmes filtres. Le PDF indique la catégorie, la nature et le total dû à l'échelle de l'institution.

## Phase 2 : nature du titulaire
- Migration **`2026_11_20_100000_add_client_category_to_users_table.php`**, qui ne fait qu'ajouter :
  - la colonne `users.client_category`, de valeur `client` par défaut, avec un index. **Aucun membre n'est reclassé automatiquement** ;
  - la permission **`classer-titulaires-comptes`**, attribuée aux rôles Admin et SUPER IT.
- **Écran de classement** (bouton « Classer les titulaires » sur `/comptes-membres`) :
  - compteurs par nature (client, interne, test) ;
  - option **« Suggestions seulement »** : les membres encore classés « client » dont le nom contient COMPTE, RETENU, COMMISSION, ASSOCI, CHARGE, INTERET, MUTUELLE, TEST ou ESSAI ;
  - recherche, filtre par nature actuelle, et changement de nature ligne par ligne ;
  - chaque changement est **fait par un utilisateur** et **journalisé** (`Classement_titulaire` dans les journaux d'activité).
- Le statut opérationnel `accounts.status` (« Actif » / « Inactif ») **n'est pas modifié**. Il continue de bloquer les opérations comme avant.

## Fichiers
| Rôle | Fichier |
|---|---|
| Migration | `database/migrations/2026_11_20_100000_add_client_category_to_users_table.php` |
| Réglage dormance | `config/comptes.php` |
| Natures (constantes, scope) | `app/Models/User.php` : `CLIENT_CATEGORIES`, `scopeRealClients()` |
| Catégories, filtres, total dû | `app/Support/ClientBalanceRanking.php` : `CATEGORIES`, `accountFilter()`, `liabilities()` |
| Page | `app/Livewire/Repports/RapportCompteClients.php`, `resources/views/livewire/repports/rapport-compte-clients.blade.php` |
| Exports | `app/Http/Controllers/ClientStatReportController.php`, `resources/views/pdf/rapport-comptes-membres.blade.php` |
| Classement | `app/Livewire/Repports/ClassementTitulaires.php`, `resources/views/livewire/repports/classement-titulaires.blade.php`, `resources/views/rapports/classement-titulaires.blade.php`, route `member.accounts.classement` |
| Tests | `tests/Feature/ComptesMembresTest.php` |

## Déploiement
```bash
php artisan migrate
php artisan optimize:clear
```
**La migration doit être lancée avant d'utiliser la page** : elle filtre sur `users.client_category`. Ensuite, ouvrir **Classer les titulaires** et reclasser les comptes internes et de test.

## Pistes suivantes
- **Phase 3** : bloquer ou clôturer un compte, avec motif, date et auteur, et regrouper les contrôles « Inactif » dans une seule méthode.
- **À analyser** : les comptes à **-10** (2 285 en local) et à **-5 005** (78).
