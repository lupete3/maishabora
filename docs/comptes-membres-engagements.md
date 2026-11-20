# Comptes membres : ce que nous devons aux clients (20/11/2026)

Page : **`/comptes-membres`**. Écran de classement : **`/comptes-membres/classement`**.

## Bonnes pratiques retenues
- **Ce que nous devons aux clients** (engagements envers la clientèle) = **somme des soldes positifs des comptes de vrais clients**, par devise. On y inclut les comptes actifs, **inactifs** et **dormants** : l'argent reste dû au client même si le compte ne bouge plus.
- **Exclus** : les comptes **internes** de l'institution (retenues, associés, commissions…) et les comptes **de test**.
- **Soldes négatifs** : ce n'est **pas une dette envers le client**, mais un découvert ou une anomalie. Ils sont affichés **à part**, à régulariser, et ne sont **pas soustraits** du total dû.
- **Deux notions séparées** :
  - le cycle de vie du compte : actif, inactif, dormant ;
  - la nature du titulaire : client, interne, test.
- **Dormant = calculé** : un compte **avec un solde positif** (de l'argent dû au client) et **sans aucune opération depuis 12 mois**. Le seuil se règle dans `config/comptes.php` (`COMPTES_DORMANT_APRES_MOIS`), et rien n'est enregistré.
  - Un compte vide, même jamais utilisé, n'est pas dormant.
  - Un solde négatif relève de la catégorie « Soldes négatifs ».
  - Avant cet ajustement, 694 membres sur 696 apparaissaient en local, à cause de comptes vides ou à -10. Après : **24 membres**, avec 594 770 CDF et 10 USD qui dorment.

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

## Présentation des chiffres (20/11/2026, suite à la lecture du PDF de production)
Sur le PDF de production, les chiffres étaient justes. Deux sortes de totaux s'y côtoyaient toutefois sans explication : les **soldes nets** (courant, épargne) et le **dû** (soldes positifs). Exemple : USD net 19 089,60 + soldes négatifs 25,00 = dû 19 114,60.

1. **Tableau récapitulatif de la sélection** (page et PDF), qui remplace les 4 cartes. Par devise : comptes courants, comptes d'épargne, **total net**, **soldes négatifs** (nombre), **dû aux clients**. Rapprochement affiché : *dû = total net − soldes négatifs*. Le calcul se fait en une requête : `ClientBalanceRanking::recap()`.
2. **Format français partout** (« 1 234,56 ») avec `ClientBalanceRanking::fr()`. Dans les tableaux de membres, un **« — »** remplace les zéros.
3. **« Masquer les membres sans solde »**, coché par défaut, à l'écran comme dans les exports. Le paramètre d'URL est `masquerSansSolde`. Un membre n'apparaît que s'il a au moins un compte filtré au solde non nul. Les totaux ne changent pas, puisque les membres masqués sont à zéro, et le nombre de lots PDF diminue.
4. **Colonnes « Total USD » et « Total CDF »** par membre (page, PDF et Excel), et ligne **« Sous-total du lot »** en bas de chaque PDF. La somme des lots redonne le total général.
5. **Annexe « Soldes négatifs à régulariser »** à la fin du **dernier lot** du PDF : code, membre, type de compte, devise, solde et statut. Elle est limitée aux 200 soldes les plus négatifs ; la liste complète se consulte à l'écran avec la catégorie « Soldes négatifs ».

Correction trouvée en générant un vrai PDF : la liste des membres servant à l'annexe doit être copiée **avant** la pagination du lot, car MySQL refuse un `LIMIT` dans une sous-requête `IN`.

Vérifié en local : rapprochement exact (CDF : net 28 017 761,15 + négatifs 102 475,00 = dû 28 120 236,15) ; lot 1 en 3,8 s ; dernier lot avec annexe en 6,2 s. Ce sont 8 tests automatiques.

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
