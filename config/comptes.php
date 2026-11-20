<?php

/*
|--------------------------------------------------------------------------
| Comptes des membres
|--------------------------------------------------------------------------
|
| dormant_after_months : un compte sans aucune opération depuis ce nombre de
| mois est considéré comme « dormant » dans le rapport /comptes-membres.
| L'argent d'un compte dormant reste dû au client : il est toujours compté
| dans « ce que nous devons aux clients ».
|
*/

return [
    'dormant_after_months' => (int) env('COMPTES_DORMANT_APRES_MOIS', 12),
];
