# Gestion des inventaires de carnets

## Objectif

L’inventaire compare, à une date donnée, le total des mises payées enregistrées dans le système avec le montant lu dans chaque carnet physique.

**Écart = total constaté dans le carnet − total théorique.**

- Un écart négatif indique un manque.
- Un écart positif indique un surplus.
- Exemple : 10 mises payées de 10 000 CDF donnent un théorique de 100 000 CDF. Un montant physique de 90 000 CDF produit un écart de −10 000 CDF.

Les montants sont comparés en centimes et les devises ne sont jamais additionnées entre elles.

## Utilisation

1. Ouvrir **Inventaire des carnets** dans le menu.
2. Choisir **Nouvel inventaire**, sélectionner un agent et afficher ses carnets.
3. La mise quotidienne et le code du membre sont affichés à côté de chaque carnet. La recherche permet de retrouver des résultats par code carnet, code client ou nom (plusieurs mots sont acceptés).
4. Utiliser **Effacer** pour retirer la recherche et réafficher tous les carnets de l’agent.
5. Choisir **Créer le brouillon**. Les lignes du contrôle sont alors conservées dans la base et peuvent être reprises depuis la liste des inventaires, même après avoir quitté la page ou fermé l’onglet.
6. Dans le brouillon, saisir le total physique et cliquer **Enregistrer la ligne** pour chaque carnet. La ligne affiche alors **Montant enregistré**. Si le carnet n’est pas présent, laisser le champ vide et enregistrer la ligne : elle sera explicitement marquée **Non inventorié**, sans écart calculé.
7. Télécharger à tout moment le **PDF — situation actuelle** d’un brouillon. Il est marqué provisoire et présente les carnets inventoriés, en attente ou absents, ainsi que les surplus, manques et écarts nets calculés uniquement sur les montants déjà saisis.
8. Finaliser l’inventaire une fois que toutes les lignes ont un statut. Le brouillon ne peut pas être finalisé tant qu’une ligne est encore « À traiter ». Après finalisation, le PDF devient le rapport définitif et comporte une ligne de signature pour l’agent.
9. Retrouver un contrôle antérieur grâce aux filtres de date et d’agent ; ouvrir **Continuer** pour reprendre un brouillon.
10. Dans le détail d’un inventaire, rechercher un carnet par son code, le code membre ou les noms du membre. Les résultats sont filtrés dans l’inventaire courant, et la recherche reste affichée pendant la pagination ou l’enregistrement d’une ligne.

Le système inscrit uniquement les carnets **actifs**, de type **épargne**, affectés à l’agent sélectionné. Les mises non payées ne sont pas incluses. Les montants, code membre et mise quotidienne sont conservés dans l’inventaire comme instantané. Un carnet absent est conservé avec le statut `not_inventoried`, et non avec un montant zéro.

## Données conservées

- `membership_card_inventories` : date de contrôle, agent responsable (identifiant et nom conservé), créateur, statut `draft`/`recorded` et date de finalisation.
- `membership_card_inventory_items` : code du carnet, code et nom du membre, devise, mise quotidienne, nombre de mises payées, théorique, montant physique et écart (nuls si le carnet est absent), statut `pending`/`inventoried`/`not_inventoried`.

Chaque détail est un instantané : les contrôles déjà enregistrés ne changent pas si les mises ou les renseignements du carnet évoluent ensuite. La suppression d’un carnet ou d’un utilisateur ne supprime pas les lignes d’historique.

L’agent utilisé pour sélectionner les carnets provient du champ `membership_cards.user_id`, qui est la colonne ajoutée pour l’agent responsable dans la migration de référence. Les carnets non affectés à un agent ne sont donc pas proposés.

## Permission

La permission dédiée est `gerer-inventaire-carnets`. Elle protège les pages, l’enregistrement et l’export PDF. La migration l’attribue aux rôles administrateur existants (`admin`, `Admin` et `SUPER IT` lorsqu’ils existent). Pour autoriser d’autres rôles, attribuer cette permission dans la gestion des rôles.

## Déploiement en production

La fonctionnalité ajoute deux tables et une permission ; une migration additive ultérieure ajoute les statuts de brouillon et les montants physiques facultatifs. Elle ne réécrit pas les mises ni les carnets. Aucun migrate, seeder ou autre changement de base n’est exécuté automatiquement par le code de l’application.

Avant le déploiement, vérifier les migrations déjà appliquées et faire la sauvegarde habituelle. Si les migrations prérequises de l’application sont déjà présentes, exécuter les migrations dédiées dans l’ordre depuis la racine du projet. Si la migration de création a déjà été appliquée, ne lancer que la deuxième :

```powershell
php artisan migrate --path=database/migrations/2026_11_12_140000_create_membership_card_inventories.php --force
php artisan migrate --path=database/migrations/2026_11_12_151000_add_draft_support_to_membership_card_inventories.php --force
```

**Prérequis à vérifier :** la migration de référence `2025_10_13_152527_add_agent_id_to_credits_and_user_id_to_membership_cards_tables.php` doit avoir été appliquée et la colonne `membership_cards.user_id` doit exister. Une vérification de syntaxe PHP ne confirme pas à elle seule que la migration a été appliquée dans la base ciblée.

Ne pas utiliser `migrate:fresh`, `migrate:refresh` ou un rollback pour déployer cette fonctionnalité. La migration de brouillon refuse volontairement son rollback, car les statuts « non inventorié » et les brouillons ne peuvent pas être représentés sans perte dans l’ancien schéma.

Les tests de cette fonctionnalité utilisent SQLite en mémoire via la configuration PHPUnit ; ils n’ont pas besoin de la base de production.
