# Retenu de la première mise d'un carnet (28/11/2026)

## Règle
Au **premier versement** dans un nouveau carnet d'épargne, la valeur d'une mise journalière est retenue. Elle va sur le compte des retenus et n'est pas créditée au client. Le carnet est alors marqué `first_mise_retained = 1`. Les versements suivants ne retiennent rien.

## Problème constaté
Quand l'agent versait **plusieurs mises d'un coup** au premier versement, le retenu pouvait être oublié. Avec une seule mise, il se faisait correctement.

### Cause
`MemberDetails::contribute()` décidait du retenu **après** avoir marqué les mises payées. La méthode cherchait « la plus ancienne mise payée », triée par date de mise, et la comparait à la première mise du lot.

Depuis le commit `b02b00d` du **12/10/2026**, chaque mise payée reçoit la date du jour. Les mises d'un même versement ont donc toutes **la même date**. Le tri ne les départage plus, et c'est la base de données qui choisit laquelle renvoyer :
- si elle renvoie la première du lot, le retenu est fait ;
- sinon, **le retenu est oublié**.

Avec une seule mise, il n'y a qu'une ligne possible, d'où la différence. Sur le MySQL 8.4 local, l'ordre tombait juste à chaque essai (24 simulations). D'autres serveurs (MariaDB, autre version, absence de l'index du 07/11) peuvent départager autrement.

## Correction
- Nouvelle méthode **`MembershipCard::doitRetenirPremiereMise()`** : vrai si le retenu n'a pas encore été fait **et** si le carnet n'a encore aucune mise payée.
- `contribute()` l'appelle **avant** de marquer les mises payées. Le résultat ne dépend plus des dates ni de l'ordre renvoyé par la base.
- Le carnet est **verrouillé** pendant le versement (`lockForUpdate`) : deux versements simultanés ne peuvent pas être comptés tous les deux comme « le premier ».
- Les deux sorties anticipées (montant trop faible, carnet entièrement payé) annulent désormais la transaction ouverte.

Comportement inchangé pour les carnets antérieurs à la règle (fin novembre 2025) : s'ils ont déjà des mises payées sans retenu, rien n'est retenu après coup.

## Contrôle en production (lecture seule)
```bash
php artisan carnets:controle-retenus
```
La commande liste les carnets d'épargne non annulés qui ont des mises payées **sans retenu**, avec un premier versement depuis le 12/10/2026. Pour chacun : le carnet, le client, la devise, le montant du retenu manquant, la date du premier versement et le nombre de mises payées. Elle donne ensuite un total par devise.

**Elle ne modifie rien.** Option : `--depuis=AAAA-MM-JJ`. Avant le 28/11/2025, les carnets sont antérieurs à la règle du retenu et apparaissent normalement dans la liste.

Voir la section « Régularisation » plus bas.

## Vérifié (local)
- **Vrai versement** (composant `MemberDetails`, dans une transaction annulée ensuite, sans aucune donnée laissée), pour une mise journalière de 1 000 CDF :

  | Premier(s) versement(s) | Versé | Crédité au client | Retenu |
  |---|---|---|---|
  | 1 mise | 1 000 | 0 | 1 000 |
  | 2 mises | 2 000 | 1 000 | 1 000 |
  | 5 mises | 5 000 | 4 000 | 1 000 |
  | 3 mises puis 2 mises | 5 000 | 4 000 | 1 000 (au premier versement seulement) |
- **Tests** : `tests/Feature/FirstMiseRetentionTest.php` (3 tests). Premier versement de 1, 2, 5 et 31 mises ; versement suivant sans retenu ; carnet déjà retenu ; carnet ancien avec des mises déjà payées.
- **Commande de contrôle** sur la base locale : aucun carnet touché depuis le 12/10/2026.

## Fichiers
| Rôle | Fichier |
|---|---|
| Règle | `app/Models/MembershipCard.php` (`doitRetenirPremiereMise`) |
| Versement des mises | `app/Livewire/Members/MemberDetails.php` (`contribute`) |
| Contrôle | `app/Console/Commands/CheckFirstMiseRetention.php` (`carnets:controle-retenus`) |
| Classement des carnets | `app/Services/FirstMiseRetentionAudit.php` |
| Régularisation | `app/Console/Commands/RegularizeFirstMiseRetention.php` (`carnets:regulariser-retenus`) |
| Tests | `tests/Feature/FirstMiseRetentionTest.php`, `tests/Feature/FirstMiseRetentionAuditTest.php` |

Aucune migration, aucune donnée modifiée.

## Résultat en production (28/11/2026)
`carnets:controle-retenus` a trouvé **48 carnets** sans retenu, soit **186 000 CDF et 20 USD**.
- **Premier cas : le 12/10 à 18h53**, quelques heures après le déploiement du commit `b02b00d` (12/10 à 12h54).
- **Dernier cas : le 06/11.** Depuis l'index du 07/11 sur les mises, la base renvoie les mises dans un ordre fixe, et le retenu se faisait de nouveau, par chance.

Cette chronologie confirme la cause décrite plus haut.

## Ce que deviennent ces carnets
`submitRetraitCarte()` **prélève le retenu au retrait** quand il n'a pas été fait. Le client reçoit le total versé moins une mise, comme dans le cas normal. **Aucun argent n'est donc perdu** pour un carnet retiré par l'écran normal.

En revanche, le marqueur `first_mise_retained` reste à 0. Le rapport des agents (`AgentReportController`), qui compte les retenus grâce à ce marqueur, **sous-compte** donc ces carnets.

## Régularisation
### 1. Contrôle détaillé (lecture seule)
```bash
php artisan carnets:controle-retenus
```
La commande répartit les carnets en quatre groupes, avec un total par devise :
| Groupe | Signification | Action |
|---|---|---|
| Actif | Le retenu sera prélevé automatiquement au retrait | **Rien.** Surtout ne pas mettre le marqueur à 1 : le retrait ne prélèverait plus le retenu |
| Retiré, retenu pris au retrait | Écriture « Entree Retenu de la carte #id » trouvée sur le compte des retenus | Mettre le marqueur à 1 (étape 2) |
| Retiré, retenu NON pris | Retrait enregistré, mais aucune écriture de retenu | Examiner au cas par cas |
| Inactif sans opération de retrait | Carnet désactivé sans l'opération « Retrait carnet #id » (anciens carnets en local) | Examiner au cas par cas |

### 2. Correction des rapports
```bash
php artisan carnets:regulariser-retenus              # simulation : affiche les carnets, n'écrit rien
php artisan carnets:regulariser-retenus --appliquer  # écrit
```
- La commande ne traite **que les carnets retirés dont le retenu a été pris au retrait**. Elle met seulement `first_mise_retained = 1`.
- **Aucun mouvement d'argent.** `updated_at` n'est pas modifié : c'est la date du retrait utilisée par la page « Carnets non renouvelés ».
- Chaque exécution avec `--appliquer` est inscrite dans les **Logs du système**, avec la liste des carnets.
- Les carnets actifs ne sont jamais modifiés. Une deuxième exécution ne trouve plus rien à faire.

### Vérifié (local)
- **Contrôle détaillé**, en élargissant la période aux anciens carnets (`--depuis=2025-11-01`) :
  - 14 actifs ;
  - 83 retirés avec retenu pris au retrait ;
  - 0 retiré sans retenu ;
  - 15 anciens carnets inactifs sans opération de retrait.

  Tous les carnets vraiment retirés ont donc eu leur retenu prélevé au retrait.
- **Régularisation avec `--appliquer`**, dans une transaction annulée ensuite :
  - 83 carnets marqués, 0 actif touché ;
  - dates de mise à jour inchangées ;
  - journal écrit ;
  - deuxième passage sans effet.
- **Tests** : `FirstMiseRetentionAuditTest` (4 tests). Classement des 4 groupes, le retenu du carnet #30 n'est pas confondu avec celui du #3, simulation sans écriture, application ciblée sans toucher `updated_at`.
