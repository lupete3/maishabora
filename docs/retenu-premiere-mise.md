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

Une éventuelle régularisation des carnets touchés est à décider à part : par exemple une écriture de retenu par carnet, avec une trace dans le journal.

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
| Tests | `tests/Feature/FirstMiseRetentionTest.php` |

Aucune migration, aucune donnée modifiée.
