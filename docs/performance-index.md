# Performance : index et filtres par date (07/11/2026)

## Pourquoi
L'audit des requêtes les plus fréquentes a montré que certaines lisaient des tables entières. Par exemple, la caisse du jour d'un agent lisait environ 24 000 transactions pour en afficher quelques dizaines. Plus la table `transactions` grossit, plus ces pages ralentissent et consomment de ressources sur le serveur.

## 1. Nouveaux index
Migration : `database/migrations/2026_11_07_100000_add_performance_indexes.php`.
- Elle ne fait qu'ajouter des index : aucune donnée modifiée, aucun index existant supprimé.
- Chaque index n'est créé que s'il n'existe pas déjà, donc la migration peut être rejouée sans risque.
- Le retour en arrière ne supprime que ces index.

| Table | Index | Pages qui en profitent |
|---|---|---|
| `transactions` | `user_id, created_at` | caisse agents, clôtures, rapports agents, Objectifs |
| `transactions` | `account_id, created_at` | historique et relevés des membres |
| `daily_contributions` | `membership_card_id, is_paid, contribution_date` | détail d'un carnet, cotisations impayées |
| `daily_contributions` | `contribution_date, is_paid` | statistiques de cotisations par période |
| `repayments` | `is_paid, due_date` et `paid_date` | retards, PAR, rapports de remboursement |
| `credits` | `is_paid, created_at` | crédits en cours, octrois par période |
| `membership_cards` | `created_at` | carnets vendus (rapport hebdo, Objectifs) |
| `accounts` | `user_id, currency, type` | recherche du compte d'un membre (dépôts, retraits) |

## 2. Filtres par date indexables
`whereDate('created_at', $jour)` fait calculer `DATE(created_at)` sur **chaque ligne**, ce qui empêche MySQL d'utiliser l'index de la date. Trois raccourcis de requête donnent **exactement le même résultat** avec une plage horaire, que l'index sait parcourir :

| Raccourci | Équivaut à |
|---|---|
| `->whereOnDate('created_at', $jour)` | `whereDate('created_at', $jour)` (même jour) |
| `->whereFromDate('created_at', $jour)` | `whereDate('created_at', '>=', $jour)` |
| `->whereToDate('created_at', $jour)` | `whereDate('created_at', '<=', $jour)` |

- Ils sont définis dans `App\Providers\AppServiceProvider::registerDateQueryMacros()`.
- Ils sont disponibles sur les requêtes Eloquent, les relations (`withSum`, `whereHas`…) et `DB::table()`.
- Les 51 usages de `whereDate('created_at', …)` ont été remplacés, dans 19 fichiers (caisse agents, caisse centrale, clôtures, écarts de caisse, tableau de bord décisionnel, rapports, paie, décaissements, virements, logs).
- L'équivalence a été vérifiée sur les données réelles (mêmes nombres et mêmes sommes) : jour simple, plage de dates, `DB::table()`, sous-requêtes de relation.
- Tous les appels qui reçoivent une date variable sont protégés par un `if` ou un `when` : une date vide n'y arrive jamais.

**Règle pour la suite** : pour filtrer une colonne date et heure sur un jour, utiliser ces raccourcis plutôt que `whereDate`.

## Gains mesurés (base locale)
| Requête | Avant | Après |
|---|---|---|
| Caisse du jour d'un agent | 440 ms, ≈ 24 000 lignes lues | **2,6 ms, 61 lignes** |
| Même requête avec l'ancien `whereDate` | 440 ms | 78 ms, toujours ≈ 24 000 lignes (d'où la correction du code) |
| Cotisations payées sur une période | 211 ms, table entière | **5 ms** |
| Carnets vendus sur une période | 53 ms, table entière | **2 ms** |
| Cotisations impayées d'un carnet | 5 ms | 1,4 ms |
| Historique du compte d'un membre | 1,8 ms + tri en mémoire | 0,9 ms, sans tri |

## Déploiement en production
```bash
php artisan migrate
php artisan optimize:clear
```
- La migration a pris **32 s en local**. Elle peut être plus longue en production, selon la taille de `transactions`. MySQL laisse les utilisateurs travailler pendant la création d'un index, mais mieux vaut la lancer **en dehors des heures de forte activité**.
- Ne jamais utiliser `migrate:fresh`, `migrate:refresh` ni `db:seed`.

## Pistes suivantes (non traitées)
- `whereYear` et `whereMonth('created_at')` (35 usages) empêchent aussi l'index. Ils pourraient être remplacés par des plages de dates de la même façon.
- Quelques écrans restent lents à l'ouverture pour d'autres raisons (calculs en PHP, nombreuses requêtes) : transfert de fonds (≈ 3,5 s), caisse centrale (≈ 3,8 s), caisse agents (≈ 3,6 s), décaissements (≈ 2,6 s).
