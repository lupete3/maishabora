# Cockpit décisionnel (`/tableau-de-bord-decisionnel`)

Refonte en 3 étapes, pour les décideurs (direction, conseil, administrateurs) :
1. **Filtres et synthèse fiable** (22/11/2026) : fait ;
2. **Graphiques** (23/11/2026) : fait ;
3. **Alertes et décisions** (23/11/2026) : fait.

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

### Anciens blocs
Tous les anciens blocs ont été remplacés : la tendance sur 30 jours à l'étape 2, les alertes, l'analyse, les crédits à surveiller et la performance des agents à l'étape 3.

L'ancien bloc « Activités du jour » est **supprimé** : la période « Jour » le remplace. Le rechargement automatique toutes les 60 s est aussi retiré.

### Fichiers
| Rôle | Fichier |
|---|---|
| Calculs (périodes, synthèse, PAR, liquidité) | `app/Services/DecisionDashboardService.php` (nouveau) |
| Page | `app/Http/Controllers/DecisionDashboardController.php`, `resources/views/decision-dashboard.blade.php` |
| Tests | `tests/Feature/DecisionDashboardServiceTest.php` |

Aucune migration, aucune modification de données.

### Vérifié (local)
- Tests : périodes et périodes précédentes (mois, semestre, jour, intervalle) ; collecte sans double comptage ; devise séparée ; encours réel ; PAR ; liquidité ; couverture ; filtre agent.
- Navigateur : mois, intervalle en USD, exercice, trimestre filtré sur un agent, agent inexistant ignoré, affichage sur téléphone sans défilement horizontal.
- Avril 2026 en CDF : collecte 21,0 M (−38,5 % par rapport à mars), encours 18,35 M, couverture des dépôts 81 %, 43,1 % de femmes.

## Étape 2 : graphiques (23/11/2026)

Les 4 graphiques suivent les filtres de la page : période, devise, agent.

La période est découpée automatiquement selon sa longueur :
| Période | Découpage |
|---|---|
| Jour | par heure |
| Semaine, mois | par jour |
| Trimestre, semestre | par semaine |
| Exercice | par mois |
| Intervalle | par jour jusqu'à 62 jours, par semaine jusqu'à 200 jours, par mois au-delà |

| Graphique | Contenu | Comment le lire |
|---|---|---|
| **Évolution de l'activité** | Collecte et retraits (barres), crédits octroyés et capital remboursé (lignes) | Des barres vertes au-dessus des rouges : l'épargne progresse. Les totaux sont ceux des indicateurs de la page. |
| **Composition des produits** | Intérêts, pénalités, frais de crédit, ventes de carnets | Indique la dépendance aux pénalités ou aux carnets, par rapport aux intérêts. |
| **Encours et portefeuille à risque** | Encours (aire) et PAR 30 (ligne, échelle de droite, repère à 5 %) à la fin de chaque tranche | Un encours qui monte avec un PAR stable : croissance saine. Un PAR qui monte : dégradation. |
| **Comparaison des agents** | Collecte de la période et encours actuel, PAR 30 au survol (10 premiers) | Repère les agents qui collectent beaucoup mais suivent un portefeuille à risque, et inversement. Masqué quand un agent est filtré. |

### Encours passé (reconstitution)
L'application n'enregistre pas de photo quotidienne du portefeuille. L'encours d'une date passée est donc **reconstitué à partir des échéances** (`DecisionDashboardService::encoursAu()`) :
- **capital prévu** des crédits accordés jusqu'à cette date ;
- **moins le capital payé** jusqu'à cette date. Un paiement compte à sa date de paiement ; à défaut, à la date de mise à jour de l'échéance. En local, 35 échéances ont un paiement sans date ;
- un **crédit soldé** ne pèse plus rien après la date où il a été marqué soldé.

Le **dernier point retombe exactement sur l'encours actuel** (vérifié : 18 354 180 CDF). Le PAR 30 passé suit la même logique : une échéance dépassée de plus de 30 jours et pas encore payée à cette date.

Limite : un paiement partiel suivi d'un autre paiement compte entièrement à la date du dernier paiement. Pour un historique exact, il faudrait plus tard une **photo quotidienne du portefeuille** (nouvelle table, alimentée par le cron).

### Autres règles
- **Remboursements** : la date de paiement n'a pas d'heure. En vue « Jour », ils tombent tous sur 00h.
- **Agents** : utilisateurs ayant collecté dans la devise pendant la période, ou suivant des crédits non soldés. Les comptes internes (rôles `admin` et `comptable` : « COMPTE CHARGES », « RETENU MISE »…) sont exclus.
- Les données sont mises en cache 5 minutes. L'année entière se calcule en environ 1,5 s.

### Constat sur les données locales (vue Exercice 2026)
L'activité s'arrête presque après avril. Le PAR 30 passe d'environ 3 % fin avril à **près de 100 % dès juin**. À vérifier sur la production, avec le constat de l'étape 1 sur les crédits jamais marqués soldés.

### Vérifié
- **Tests** (6 tests, 53 vérifications) :
  - découpage : mois en 30 jours, exercice en 12 mois, jour en 24 heures, trimestre en semaines bornées au 1er avril et au 30 juin ;
  - totaux des graphiques égaux à ceux de la synthèse ;
  - encours et PAR au premier jour et au dernier point ;
  - comparaison des agents ;
  - encours reconstitué après un paiement et après un crédit soldé.
- **Rendu serveur** sans erreur pour mois, jour en USD, exercice filtré sur un agent, et un intervalle sans produits (message « Aucun produit »).
- **Navigateur** : mois et exercice sur ordinateur, mois sur téléphone (sans défilement horizontal).

## Étape 3 : alertes et décisions (23/11/2026)

### Alertes et décisions (en haut de la page)
Des règles s'appliquent aux chiffres de la page, avec la même période, la même devise et le même agent. Chaque alerte donne :
- **le constat chiffré** ;
- **une décision proposée** ;
- **un lien** vers l'écran où agir.

Les alertes sont classées : critique (rouge), puis à surveiller (orange), puis à traiter (bleu).

| Alerte | Déclenchement | Décision proposée |
|---|---|---|
| Portefeuille à risque | PAR 30 ≥ 5 % (critique à partir de 10 %) | Freiner les nouveaux crédits chez les agents exposés, recouvrer les plus gros retards |
| Crédits arrivés à terme non soldés | Dernière échéance dépassée depuis plus de 90 jours | Marquer soldé si remboursé, sinon contentieux ou passage en perte |
| Encours concentré | Un agent suit au moins 50 % de l'encours | Répartir le suivi |
| Échéances mal remboursées | Taux de remboursement de la période < 95 % (critique sous 80 %) | Visites ciblées |
| Caisse négative | Caisse centrale ou caisses des agents < 0 | Contrôler et régulariser |
| Liquidité | Couverture des dépôts < 20 % (critique sous 10 %) | Limiter les décaissements et les grosses sorties |
| Retraits supérieurs à la collecte | Épargne nette de la période < 0 | Comprendre les gros retraits, relancer la collecte |
| Collecte en forte baisse | −20 % ou plus par rapport à la période précédente | Vérifier chaque agent, fixer des objectifs |
| Clients inactifs avec épargne | Au moins 30 jours sans opération et solde > 0 | Visites par l'agent |
| Soldes négatifs | Comptes clients négatifs | Analyser et régulariser |
| Clients sans agent | Clients actifs sans `agent_id` | Attribuer un agent |

Les alertes liées à toute l'institution (concentration, soldes négatifs, clients sans agent) ne s'affichent pas quand un agent est filtré.

### Listes de travail (situation à ce jour, dans la devise choisie)
- **Crédits à surveiller** (« balance âgée ») :
  - les crédits en retard sont classés par ancienneté de la plus vieille échéance impayée : 1 à 30, 31 à 90, 91 à 180, plus de 180 jours ;
  - pour chaque tranche : nombre de crédits et capital restant dû ;
  - ensuite, les 10 plus gros restants dus, avec le client, l'agent, les jours de retard et le nombre d'échéances impayées ;
  - la ligne ouvre la fiche du client, selon les droits de l'utilisateur.
- **Clients inactifs avec épargne** :
  - tranches de 30, 60, 90 et 180 jours sans opération, avec le nombre de clients et l'épargne qui dort ;
  - ensuite, les 10 plus gros soldes.

  Cela remplace l'ancien bloc, qui comptait tous les membres sans regarder leur solde.
- **Performance des agents** sur la période : clients rattachés, clients actifs et taux d'activité, collecte, encours, PAR 30, crédits en retard. Les comptes internes (rôles `admin` et `comptable`) sont exclus.

### Corrections par rapport à l'ancien cockpit
- L'ancienne « Analyse intelligente » et les « Actions prioritaires » utilisaient la collecte comptée deux fois et mélangeaient les devises. Elles sont remplacées par les alertes.
- Les anciens « Crédits à surveiller » comptaient des **échéances** et non des crédits, sans montant ni devise.
- L'ancienne performance des agents ne retenait que les recouvreurs. Elle additionnait les devises, et elle classait un crédit « en retard » selon un champ qui n'est pas mis à jour.

### Fichiers
| Rôle | Fichier |
|---|---|
| Alertes, décisions, listes de travail | `app/Services/DecisionAlertsService.php` (nouveau) |
| Contrôleur réduit à l'assemblage des filtres et des services | `app/Http/Controllers/DecisionDashboardController.php` |
| Vue | `resources/views/decision-dashboard.blade.php` |

### Constat sur les données locales (avril 2026, CDF)
- 8 alertes : 2 critiques, 3 à surveiller, 3 à traiter.
- **86,8 % de l'encours** est suivi par un seul utilisateur (« AGENT ASSOCIERS », réceptionniste).
- **27 crédits** sont arrivés à terme sans être soldés.
- **281 clients inactifs** détiennent 27,3 M CDF.
- La plupart des clients n'ont **aucun agent attribué**.

### Vérifié
- **Tests** (10 tests, 84 vérifications) :
  - balance âgée et devise séparée ;
  - clients inactifs avec épargne ;
  - performance d'un agent ;
  - déclenchement et ordre des alertes, y compris caisse négative, liquidité en tension et clients sans agent.
- **Rendu serveur** sans erreur : mois, jour en USD, exercice filtré sur un agent, intervalle.
- **Navigateur** : alertes, listes de travail et tableau des agents sur ordinateur.
