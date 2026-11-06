# Comptes membres : classement des grands clients

Page : **`/comptes-membres`** (Rapports › Soldes des membres). Permission inchangée.

## Ce qui a été ajouté (06/11/2026)

### Sélecteur « Classer par »
| Choix | Ordre |
|---|---|
| Ordre alphabétique | par nom (par défaut, comme avant) |
| Plus gros soldes USD | du plus gros solde en dollars au plus petit |
| Plus gros soldes CDF | du plus gros solde en francs au plus petit |
| Plus gros soldes (total converti en USD) | USD + CDF convertis au **dernier taux USD → CDF** enregistré (Gestion des taux de change). C'est le meilleur critère pour repérer les grands clients toutes devises confondues |

Le classement **respecte les filtres** : recherche, type de compte (courant / épargne), devise, solde minimum et tranche alphabétique. Exemple : « Épargne uniquement » + « Plus gros soldes CDF » donne les plus gros épargnants en francs.

Le tri est fait **en base de données, sur tous les membres**. Le n°1 de la page 1 est donc le plus gros client de toute l'institution, et le rang continue d'une page à l'autre (la page 2 commence au rang 11).

### Colonnes affichées lors d'un classement par solde
- **Rang** : les 3 premiers sont mis en évidence.
- **Total équiv. USD** : uniquement pour le classement « total converti ».
- **Part de l'encours** : poids du client dans l'**encours créditeur**, c'est-à-dire la somme des soldes **positifs** des membres filtrés.
  - Un client à solde nul ou négatif n'a pas de part (affichage « — »).
  - Les soldes négatifs sont exclus du calcul, sinon un total négatif donnerait des parts absurdes.

### Exports PDF et Excel
Les deux exports reprennent le classement choisi. Le PDF se génère par lots, voir plus bas.
- le PDF indique le critère de classement et le taux utilisé ;
- le PDF et l'Excel ajoutent les colonnes Rang, Total équiv. USD et Part.

En ordre alphabétique, les exports gardent leur format d'origine.

## Génération par lots et performances (06/11/2026)

Avec plus de 1 200 clients, générer le PDF de tous les membres d'un coup provoquait une **erreur 500**, car les ressources du serveur sont dépassées. Mesure en local pour 701 clients : 14 à 17 s et jusqu'à 268 Mo de mémoire.

### PDF par lots
- Choix **« Taille d'un lot PDF »** : 50, 100 (par défaut) ou 200 membres. Le maximum de 200 est imposé côté serveur.
- Le bouton **PDF** ouvre la liste des lots selon les filtres en cours, par exemple « Lot 1 : membres 1 à 100 », « Lot 2 : membres 101 à 200 »…
- Chaque PDF ne contient que son lot :
  - le **rang reste global** (le lot 2 commence au rang 101) ;
  - les **totaux en tête du PDF portent sur tous les membres filtrés** ;
  - l'en-tête indique « Lot X / N : membres a à b sur total ».
- Avec un classement par solde, le **lot 1 correspond au Top 50, 100 ou 200 des grands clients**.
- Paramètres d'URL : `lotSize` (50, 100 ou 200) et `lot` (n° du lot). Sans eux, l'URL donne le lot 1 de 100 membres.

### Excel
L'export Excel contient toujours **tous les membres** dans un seul fichier, car il est écrit au fil de l'eau sans saturer la mémoire. Sa limite de temps est portée à 300 s.

### Totaux calculés en base
Les totaux (cartes en haut de page, encours créditeur pour la « part ») sont calculés **en une seule requête SQL** (`ClientBalanceRanking::totals()`), au lieu de relire tous les membres en PHP. J'ai vérifié qu'ils donnent exactement les mêmes résultats sur plusieurs combinaisons de filtres.

### Chargement différé et lignes par page
- La page s'ouvre immédiatement avec « Chargement des soldes des membres… », puis le tableau s'affiche (`lazy`).
- Choix **« Lignes par page »** : 10, 25 ou 50.

### Gains mesurés (base locale, 701 clients)
| | Avant | Après |
|---|---|---|
| PDF | 14 à 17 s, jusqu'à 268 Mo (tout) | 1 à 3 s par lot, +38 Mo au maximum |
| Affichage de la page | 3,5 s par changement | environ 0,65 s |
| Excel (tout) | 7,8 s | 1,6 s |

## Règles de calcul
- Solde USD / CDF d'un membre = somme de ses comptes dans la devise, **après filtres**. Tous types de comptes sont comptés, sauf si un type est filtré.
- Total converti = solde USD + solde CDF ÷ taux. Sans taux de change enregistré, le classement « total » se replie sur le solde USD.
- En cas d'égalité (ex. nombreux clients à 0), les clients sont départagés par ordre alphabétique.

## Fichiers
| Rôle | Fichier |
|---|---|
| Logique de classement partagée | `app/Support/ClientBalanceRanking.php` |
| Écran (Livewire) | `app/Livewire/Repports/RapportCompteClients.php`, `resources/views/livewire/repports/rapport-compte-clients.blade.php` |
| Exports PDF / Excel | `app/Http/Controllers/ClientStatReportController.php` (`compteClientsPdf`, `compteClientsExcel`), `resources/views/pdf/rapport-comptes-membres.blade.php` |

Aucune migration, aucune donnée modifiée.

## Points relevés pendant les tests (base locale)
- **Soldes négatifs** : environ 700 comptes USD et 1 100 comptes au total ont un solde négatif. La plupart sont à -10, mais certains descendent jusqu'à -5 010 USD. Le total des comptes courants USD affiché en haut de page est de ce fait négatif (-725 962 USD). C'est à vérifier en production.
- **Comptes techniques** : « COMPTE RETENU SALAIRE » est enregistré comme membre et apparaît dans le classement (5e). Il faudrait l'exclure de la liste des membres.
