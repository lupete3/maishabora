# Module Objectifs & Performances

Ce module permet de fixer des objectifs sur une période, **propres à chaque agent collecteur** ou **globaux pour l'entreprise**. Il suit ensuite leur progression à partir des données déjà présentes dans l'application, sans aucune saisie en double.

Il a sa propre interface, séparée du tableau de bord des caisses agents (`/tableau-de-bord-agent`).

## Accès

| Page | URL | Permission |
|---|---|---|
| Suivi des objectifs | `/objectifs` | `afficher-objectifs` |
| Configuration (fixer les objectifs, répartition, indicateurs) | `/objectifs/configuration` | `gerer-objectifs` |

Les deux permissions sont créées par migration et données aux rôles **Admin** et **SUPER IT**. Pour d'autres rôles (superviseur, comptable…), il suffit de les attribuer depuis la gestion des rôles.

Dans le menu, l'entrée s'appelle **Objectifs & Performances**.

## Fonctionnement

### 1. Fixer les objectifs (Configuration › Saisie)
1. Choisir le type de période (semaine, mois, trimestre, semestre, année) et la période voulue.
2. Choisir **Entreprise** (objectifs globaux) ou **un agent**.
3. Remplir les objectifs voulus. Une case vide signifie « pas d'objectif » ; vider une case supprime l'objectif existant.
4. La colonne **Réalisé (période précédente)** sert de repère pour fixer un objectif réaliste.

Les montants se fixent **séparément en CDF et en USD**.

### 2. Outils (Configuration › Répartition & reprise)
- **Attribuer un objectif à plusieurs agents**, selon trois modes :
  - *Même objectif pour chaque agent* ;
  - *Répartir un total selon le nombre de clients* : chaque agent reçoit une part proportionnelle à son portefeuille. Cela tient compte du fait que les agents n'ont pas tous le même volume de clients ;
  - *Répartir un total selon le réalisé de la période précédente*.
- **Reprendre la période précédente** : recopie tous les objectifs (entreprise et agents), avec une évolution en % si besoin (ex. +10 %). Par défaut, les objectifs déjà fixés ne sont pas remplacés.

### 3. Paramétrer les indicateurs (Configuration › Indicateurs)
Chaque indicateur peut être activé ou désactivé, et son ordre d'affichage modifié, sans toucher au code.

### 4. Suivre (Suivi)
- Onglet **Entreprise** : progression de chaque objectif global, regroupée par thème, avec un score global.
- Onglet **Agents collecteurs** : classement des agents par score, puis détail de chaque agent.
- Chaque barre affiche le réalisé, l'objectif, le taux, ce qui reste à faire et un **repère noir** qui indique où l'on devrait être à la date du jour.

## Statuts

| Statut | Signification |
|---|---|
| Atteint | réalisé ≥ objectif |
| Dans les temps | réalisé ≥ attendu à date |
| Vigilance | réalisé ≥ 80 % de l'attendu à date |
| En retard | en dessous |
| Sous le plafond / Proche du plafond / Plafond dépassé | indicateurs de type « plafond » (retraits, PAR 30, crédits en retard) |
| À venir | période pas encore commencée |

**Attendu à date** : pour un indicateur cumulé sur la période, c'est l'objectif × la part du temps écoulé. Par exemple, au 15 d'un mois de 30 jours, on attend 50 % de l'objectif. Pour un indicateur « à date » (encours, taux, PAR…), l'attendu est l'objectif lui-même.

**Score** : moyenne des objectifs, chacun plafonné à 100 %.

Les seuils (80 % pour la vigilance, 20 % de tolérance sur les plafonds) se règlent dans `config/objectifs.php` › `seuils`.

## Catalogue des indicateurs

| Groupe | Indicateur | Unité | Portée | Type |
|---|---|---|---|---|
| Activité | Nombre d'opérations | nombre | agent / entreprise | cumul |
| Collecte | Mises quotidiennes (nombre, montant) | nombre / montant | agent / entreprise | cumul |
| Collecte | Dépôts (nombre, montant) | nombre / montant | agent / entreprise | cumul |
| Collecte | Collecte totale (mises + dépôts) | montant | agent / entreprise | cumul |
| Collecte | Retraits | montant | agent / entreprise | cumul, **plafond** |
| Collecte | Épargne nette (collecte − retraits) | montant | agent / entreprise | cumul |
| Collecte | Encours d'épargne | montant | agent / entreprise | à date |
| Clientèle | Clients sensibilisés (nouveaux membres) | nombre | agent / entreprise | cumul |
| Clientèle | Clients actifs | nombre | agent / entreprise | à date |
| Clientèle | Taux d'activité du portefeuille | % | agent / entreprise | à date |
| Clientèle | Taille du portefeuille | nombre | agent / entreprise | à date |
| Carnets | Carnets vendus | nombre | agent / entreprise | cumul |
| Carnets | Ventes de carnets | montant | agent / entreprise | cumul |
| Crédit | Crédits octroyés (nombre, montant) | nombre / montant | entreprise (ou agent) | cumul |
| Crédit | Bénéficiaires / Nouveaux bénéficiaires | nombre | entreprise (ou agent) | cumul |
| Crédit | Remboursements (capital) | montant | entreprise (ou agent) | cumul |
| Crédit | Intérêts encaissés | montant | entreprise (ou agent) | cumul |
| Crédit | Pénalités encaissées | montant | entreprise (ou agent) | cumul |
| Crédit | Frais de dossier | montant | entreprise (ou agent) | cumul |
| Crédit | Encours de crédit | montant | entreprise (ou agent) | à date |
| Qualité | Taux de remboursement | % | entreprise (ou agent) | à date |
| Qualité | PAR 30 | % | entreprise (ou agent) | à date, **plafond** |
| Qualité | Crédits en retard | nombre | entreprise (ou agent) | à date, **plafond** |

Les objectifs de crédit sont prévus au niveau de l'entreprise. Ils peuvent aussi être fixés par agent, puisque chaque crédit a un agent gestionnaire (`credits.agent_id`).

## Règles de calcul (d'où viennent les chiffres)

- **Opérations d'un agent** : transactions dont `user_id` est l'agent, c'est-à-dire l'écriture côté caisse agent. Types comptés :
  - mises : `mise_quotidienne` et `encaissement_mise` ;
  - dépôts : `depot` et `dépôt` ;
  - retraits : `retrait` et `retrait_carte_adhesion` ;
  - carnets : `vente_carte_adhesion`.
- **Opérations globales** : écritures côté compte membre (`account_id` renseigné). Les comptes techniques (frais d'adhésion, retenues…) ne sont donc pas comptés.
- **Clientèle** : membres (`role = membre`) rattachés à l'agent par `users.agent_id`.
  - Clients actifs : membres ayant fait au moins une mise ou un dépôt sur la période.
- **Carnets** : `membership_cards.user_id` (agent responsable), hors carnets annulés.
- **Crédits** : date d'octroi = `credits.created_at`. Les remboursements se basent sur `repayments.paid_date` (capital, intérêts et pénalités séparés).
- **Encours de crédit** : capital restant dû des crédits non soldés.
- **PAR 30** : encours des crédits ayant une échéance impayée depuis plus de 30 jours, divisé par l'encours total.
- **Taux de remboursement** : montant payé divisé par le montant dû, pour les échéances tombées dans la période (jusqu'à aujourd'hui au plus tard).

### Limites à connaître
- Les indicateurs **« à date »** (encours, PAR, crédits en retard, encours d'épargne) reflètent la **situation actuelle**, même quand on consulte une période passée : l'historique des soldes n'est pas conservé.
- Les indicateurs **clientèle par agent** dépendent du rattachement des membres à un agent (`users.agent_id`). Un membre sans agent n'est compté que dans le global. Sur la base locale de test, seuls 9 membres sur 701 étaient rattachés : il faut compléter ce rattachement pour que ces indicateurs soient justes.
- L'écran existant « Performance des agents » (modal du tableau de bord caisse) ne compte pas le type `encaissement_mise` utilisé par l'écran *Mise quotidienne* ; le module Objectifs, lui, le compte.

## Fichiers du module

| Rôle | Fichier |
|---|---|
| Catalogue, périodes, seuils | `config/objectifs.php` |
| Migrations (additives) | `database/migrations/2026_11_06_100000_create_objectifs_tables.php`, `..._100001_add_objectifs_permissions.php` |
| Modèles | `app/Models/Objectif.php`, `app/Models/ObjectifIndicateur.php` |
| Calculs | `app/Services/ObjectifProgressService.php` |
| Contrôleur | `app/Http/Controllers/ObjectifController.php` |
| Composants Livewire | `app/Livewire/Objectifs/ObjectifsSuivi.php`, `ObjectifsConfiguration.php` |
| Vues | `resources/views/objectifs/*`, `resources/views/livewire/objectifs/*` |
| Routes | `routes/web.php` (bloc « Module Objectifs ») |
| Menu | `resources/views/layouts/partials/aside.blade.php` |

### Ajouter un indicateur
1. Le déclarer dans `config/objectifs.php` › `indicateurs` (clé en snake_case).
2. Ajouter dans `ObjectifProgressService` une méthode `protected` portant le même nom en camelCase. Elle doit renvoyer un total global (`float`) ou un tableau `[agentId => valeur]`, en s'appuyant sur `agreger()`.

## Déploiement en production

```bash
php artisan migrate
```

Ces migrations ne font qu'ajouter des éléments (2 nouvelles tables et 2 permissions) : aucune donnée existante n'est modifiée. **Ne jamais utiliser** `migrate:fresh`, `migrate:refresh` ni `db:seed`.

Ensuite, vider les caches :

```bash
php artisan optimize:clear
```
