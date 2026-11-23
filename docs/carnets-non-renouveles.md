# Carnets non renouvelés (23/11/2026)

Page : **Carnets non renouvelés** (menu, section des carnets) → `/carnets-non-renouveles`.

Elle liste les **clients qui ont retiré leur carnet sans en reprendre un**, pour que les agents les relancent. Un bouton permet d'exporter la liste en Excel.

## Filtres (dans l'adresse de la page, donc partageables)
| Filtre | Valeurs | Défaut |
|---|---|---|
| Retraits du | jour, semaine, mois, période personnalisée (du… au…) ; flèches ‹ › pour changer de période | mois en cours |
| Agent collecteur | agent inscrit sur le carnet | tous |
| Devise | CDF, USD | toutes |
| Carnet | épargne, simple, tous | épargne |
| Retard minimum | jours minimum depuis le retrait | 0 |
| Masquer les clients qui ont encore un carnet actif | case à cocher | non |

La période choisie porte sur la **date du retrait**. Le renouvellement est vérifié **à aujourd'hui**.

## Règles
- **Retrait** : un carnet inactif (`is_active = 0`), hors carnets annulés. La date du retrait est sa date de mise à jour. Elle est identique à celle de l'opération « Retrait carnet #id » pour 912 retraits sur 913.
- **Renouvelé** : le client possède un **autre carnet non annulé, plus récent** que celui retiré.
  - Cela vaut aussi quand le nouveau carnet a été acheté **avant** le retrait de l'ancien. Le cas concerne 90 clients sur la base locale ; sans cette règle, ils seraient relancés à tort.
  - Cela vaut aussi pour deux carnets tenus en parallèle : le plus ancien compte comme renouvelé, puisque le client détenait déjà le plus récent.
- **Client avec un autre carnet actif** : il a retiré un carnet sans en reprendre un plus récent, mais un carnet plus ancien reste actif. Il reste dans la liste avec la mention « A encore un carnet actif ». La case à cocher permet de le masquer.
- **Une ligne par client** : s'il a plusieurs retraits non renouvelés dans la période, seule la plus récente est affichée.
- **Jours depuis le retrait** : aujourd'hui − date du retrait. Couleurs : vert sous 7 jours, orange de 7 à 30, rouge au-delà.
- **Agent collecteur** : l'agent inscrit sur le carnet (`membership_cards.user_id`). L'agent de la fiche client n'est renseigné que pour 14 clients sur 701.

## Contenu de la page
- **Compteurs** : carnets retirés, renouvelés, clients à relancer, taux de renouvellement (renouvelés ÷ retirés).
- **Clients à relancer**, du retard le plus long au plus court :
  - client, code et nombre de carnets déjà terminés ;
  - téléphone, cliquable sur mobile ;
  - agent ;
  - carnet retiré : numéro, type, mise journalière, mises versées ;
  - date du retrait et jours écoulés.

  50 lignes par page. Un clic ouvre la fiche du client, selon les droits de l'utilisateur.
- **Renouvellement par agent** : retirés, à relancer, taux, du plus faible au plus élevé. Un clic sur un agent filtre sa liste.

## Export Excel
Le bouton **Exporter (Excel)** reprend les mêmes filtres. Il produit le fichier `carnets-non-renouveles_<du>_<au>.xlsx`, avec une feuille « Clients à relancer ». Colonnes :
- code client, client, téléphone, agent collecteur ;
- carnet, type, devise, mise journalière, mises versées ;
- date du retrait, jours depuis le retrait ;
- carnets terminés, autre carnet actif, période analysée.

Le code client, le téléphone et le numéro de carnet sont enregistrés comme **texte**, pour qu'Excel conserve le « + » des numéros.

## Accès
Permission **`voir-carnets-non-renouveles`**, attribuée aux rôles **Admin** et **SUPER IT** par la migration `2026_11_23_100000_add_card_renewal_permission.php`. Cette migration ne fait qu'ajouter des lignes (`insertOrIgnore`). On peut donner la permission à d'autres rôles depuis la gestion des rôles.

## Fichiers
| Rôle | Fichier |
|---|---|
| Calcul | `app/Services/CardRenewalService.php` |
| Page et export | `app/Http/Controllers/CardRenewalController.php`, `resources/views/carnets/non-renouveles.blade.php` |
| Excel | `app/Exports/CardRenewalExport.php` |
| Permission | `database/migrations/2026_11_23_100000_add_card_renewal_permission.php` |
| Routes | `routes/web.php` : `carnets.non-renouveles`, `carnets.non-renouveles.export` |
| Menu | `resources/views/layouts/partials/aside.blade.php` |
| Tests | `tests/Feature/CardRenewalServiceTest.php` |

Les périodes réutilisent `DecisionDashboardService::periode()`, comme le cockpit décisionnel.

## Déploiement
```bash
php artisan migrate
php artisan optimize:clear
```

## Constat sur la base locale
- Sur tout l'historique : **1 027 retraits, 778 renouvelés, 249 clients à relancer**.
- Mars 2026 : 151 retraits, 117 renouvelés (**77,5 %**), 34 clients à relancer. Taux par agent : de 40 % (CAISSE 02 CITO) à plus de 80 %.
- Certains carnets portent comme agent un compte interne, par exemple « COMPTE CHARGES ». Ils apparaissent tels quels dans le tableau par agent.

## Vérifié
- **Tests** (4 tests, 29 vérifications) :
  - retrait et renouvellement, y compris anticipé ou en parallèle ;
  - autre carnet actif ;
  - mises payées seulement ;
  - une ligne par client, retard minimum, masquage ;
  - compteurs, tranches et taux par agent ;
  - filtres par agent, devise et type.
- **Rendu serveur** : mois, période personnalisée sur tout l'historique avec retard minimum, semaine filtrée sur un agent, jour sans retrait.
- **Accès** : Admin et SUPER IT autorisés, Recouvreur refusé (403).
- **Export** : 34 lignes pour mars, téléphones avec « + », montants en nombres.
- **Navigateur** : page sur ordinateur.
