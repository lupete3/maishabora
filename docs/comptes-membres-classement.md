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
Les deux boutons reprennent le classement choisi :
- le PDF indique le critère de classement et le taux utilisé ;
- le PDF et l'Excel ajoutent les colonnes Rang, Total équiv. USD et Part.

En ordre alphabétique, les exports gardent leur format d'origine.

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
