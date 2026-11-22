# Cockpit décisionnel (`/tableau-de-bord-decisionnel`)

Refonte en 3 étapes, pour les décideurs (direction, conseil, administrateurs) :
1. **Filtres et synthèse fiable** (22/11/2026) : fait ;
2. Graphiques : à faire ;
3. Alertes et décisions : à faire.

Plus tard, dans une interface séparée : **états financiers et ratios comptables** destinés aux partenaires, aux bailleurs et à la BCC (bilan, compte de résultat, autosuffisance…). Ils attendent que la comptabilité soit à jour.

## Étape 1 : filtres et synthèse (22/11/2026)

### Filtres (dans l'adresse de la page, donc partageables)
| Paramètre | Valeurs | Défaut |
|---|---|---|
| `periode` | `year` (exercice), `semester`, `quarter`, `month`, `week`, `day`, `custom` (intervalle) | `month` |
| `ref` | date de référence (la période qui la contient) | aujourd'hui |
| `du`, `au` | bornes de l'intervalle (`custom`) | début du mois → aujourd'hui |
| `devise` | `CDF` ou `USD` (jamais additionnées) | `CDF` |
| `agent_id` | un utilisateur non membre | toute l'institution |

- Les flèches **‹ ›** passent à la période précédente ou suivante. On ne peut pas aller au-delà d'aujourd'hui.
- Chaque chiffre de la période est comparé à la **période précédente équivalente** : le mois d'avant, le semestre d'avant, la veille, ou les N jours qui précèdent un intervalle de N jours.
- Une flèche verte signale une évolution favorable, une flèche rouge une évolution défavorable. Pour les retraits, une **hausse** s'affiche donc en rouge.

### Indicateurs
**Activité de la période**
- **Collecte** (dépôts + mises) : lignes **côté compte membre** uniquement. Une mise produit deux lignes (compte du membre et caisse de l'agent) ; l'ancien cockpit comptait les deux, ce qui doublait presque la collecte.
- **Retraits** et **épargne nette** (collecte − retraits).
- **Crédits octroyés** : nombre et montant.
- **Capital remboursé**.
- **Produits encaissés** : intérêts, pénalités, frais de crédit et ventes de carnets.
- **Taux de remboursement** : payé ÷ attendu sur les échéances tombées pendant la période.
- **Nouveaux membres** et **membres actifs** (au moins une opération).

**Portefeuille de crédit (à ce jour)**
- **Encours** = **capital restant dû**. L'ancien cockpit additionnait le montant initial des crédits non soldés : en local, 24,6 M au lieu de 18,4 M CDF.
- Emprunteurs actifs, crédit moyen.
- **Crédits en retard**, dans la devise choisie.
- **PAR 1, 30 et 90 jours** : part de l'encours dont une échéance est impayée depuis plus de N jours. Repères affichés : PAR 30 < 5 % en vert, 5 à 10 % en orange, au-delà en rouge.

**Épargne et liquidité (à ce jour)**
- **Dépôts de la clientèle** : ce que nous devons aux clients, soit la somme des soldes positifs des membres de catégorie « client ». C'est le même chiffre que sur `/comptes-membres`.
- **Liquidité** : caisse centrale + caisses des agents. Avec un filtre agent, seulement la caisse de cet agent.
- **Couverture des dépôts** = liquidité ÷ dépôts. Repère interne : ≥ 20 % confortable, 10 à 20 % à surveiller, < 10 % tension.
- **Portée** : nombre de membres clients et part des femmes, un indicateur suivi par les bailleurs.

### Règles communes
Les chiffres réutilisent les règles déjà validées dans l'application : **`ObjectifProgressService::mesurer()`** (module Objectifs) et **`ClientBalanceRanking::liabilities()`** (comptes membres). Ils concordent donc avec ces écrans. Les calculs sont mis en cache **5 minutes** par combinaison de filtres.

### Constat sur les données locales
Les 93 crédits non soldés ont tous dépassé leur **dernière échéance** : la plus récente tombait le 28/09/2026. Le PAR affiche donc **100 %**. Si la production montre la même chose, il s'agit soit de crédits réellement en souffrance, soit de crédits remboursés mais jamais marqués « soldés ». C'est à vérifier, car cela fausse l'encours.

### Blocs conservés en l'état (repris aux étapes 2 et 3)
Le centre d'alertes, les actions prioritaires, les crédits à surveiller, la tendance sur 30 jours, l'analyse et la performance des agents restent inchangés. Ils seront corrigés ensuite, notamment la tendance, qui additionne encore les devises.

L'ancien bloc « Activités du jour » est **supprimé** : la période « Jour » le remplace. Le rechargement automatique toutes les 60 s est aussi retiré.

### Fichiers
| Rôle | Fichier |
|---|---|
| Calculs (périodes, synthèse, PAR, liquidité) | `app/Services/DecisionDashboardService.php` (nouveau) |
| Page | `app/Http/Controllers/DecisionDashboardController.php`, `resources/views/decision-dashboard.blade.php` |
| Tests | `tests/Feature/DecisionDashboardServiceTest.php` (3 tests, 32 vérifications) |

Aucune migration, aucune modification de données.

### Vérifié (local)
- Tests : périodes et périodes précédentes (mois, semestre, jour, intervalle) ; collecte sans double comptage ; devise séparée ; encours réel ; PAR ; liquidité ; couverture ; filtre agent.
- Navigateur : mois, intervalle en USD, exercice, trimestre filtré sur un agent, agent inexistant ignoré, affichage sur téléphone sans défilement horizontal.
- Avril 2026 en CDF : collecte 21,0 M (−38,5 % par rapport à mars), encours 18,35 M, couverture des dépôts 81 %, 43,1 % de femmes.
