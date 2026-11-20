# Situation des crédits : retards triés par ancienneté croissante (20/11/2026)

Composant : `credit.credit-overview` (`app/Livewire/Credit/CreditOverview.php`). Il est affiché sur l'accueil de chaque collecteur, à côté de sa caisse, et dans le tableau de bord crédit global.

## Objectif
Savoir **qui recouvrer en priorité**, avant que le retard ne s'installe. Les échéances en retard s'affichent donc de la **moins en retard à la plus en retard** :
- échéance du jour (**0 jour**) ;
- puis 1 jour, 20 jours, 100 jours, 300 jours…

Avant, la liste était triée par date de création de l'échéance, et l'ordre paraissait aléatoire (53 j, 114 j, 63 j, 189 j…).

## Ce qui a changé
- **Tri** : `orderByLeastLate()` trie par `due_date` décroissante (l'échéance la plus récente d'abord), puis par `id` pour garder un ordre stable d'une page à l'autre. Il est utilisé pour la liste paginée et pour le **PDF « Crédits en retard »**.
- **Jours de retard en jours entiers** : `CreditOverview::daysLate()`, où une échéance d'aujourd'hui vaut 0 et hier vaut 1. Avant, le calcul à l'heure près puis arrondi pouvait afficher « 1 jour » pour une échéance du jour.
- **Jours restants** du bloc « à venir », en jours entiers : `CreditOverview::daysRemaining()`, où demain vaut 1. C'est utilisé dans la liste et dans le PDF « Crédits à venir ».
- Inchangé : les blocs, les totaux par devise, la pagination (10 par page) et les règles d'accès.

Une échéance qui tombe aujourd'hui figure dans le bloc « en retard », à 0 jour, et donc tout en haut de la liste.

## Vérifié (base locale)
- Les 258 échéances en retard sont bien rangées par retard croissant, de 53 à 393 jours. La base locale n'a pas d'échéance plus récente.
- Le calcul est correct : aujourd'hui = 0 jour, hier = 1 jour, demain = 1 jour restant.
- Le composant s'affiche dans l'ordre attendu, et le PDF des retards se génère.

## Fichiers
- `app/Livewire/Credit/CreditOverview.php` : `orderByLeastLate()`, `daysLate()`, `daysRemaining()`.
- `resources/views/livewire/credit/credit-overview.blade.php` : calcul des jours.
- `resources/views/pdf/credit-overdue-export.blade.php` et `credit-upcoming-export.blade.php` : calcul des jours.
