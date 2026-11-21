<?php

/*
|--------------------------------------------------------------------------
| Sauvegarde automatique de la base
|--------------------------------------------------------------------------
|
| Les sauvegardes sont rangées dans storage/app/private/<dossier> (non
| accessible par le web). Elles sont déclenchées par un service externe
| (cron-job.org) qui appelle /cron/sauvegarde/<jeton>.
|
| Les jetons des adresses cron se règlent dans le fichier .env, jamais dans
| le code (le dépôt Git ne doit pas contenir de secret).
|
*/

return [

    // Dossier (dans storage/app/private) qui contient les sauvegardes automatiques
    'dossier' => 'backups',

    // Nombre de jours de sauvegardes conservées ; les plus anciennes sont supprimées
    'retention_jours' => (int) env('SAUVEGARDE_RETENTION_JOURS', 7),

    // Une nouvelle sauvegarde n'est faite que si la dernière a plus de N heures
    // (l'adresse cron peut ainsi être appelée toutes les heures sans doublon)
    'intervalle_heures' => (int) env('SAUVEGARDE_INTERVALLE_HEURES', 20),

    // Alerte sur la page si la dernière sauvegarde a plus de N heures
    'alerte_apres_heures' => (int) env('SAUVEGARDE_ALERTE_HEURES', 26),

    // Espace disque libre minimal (Mo) pour lancer une sauvegarde
    'espace_minimal_mo' => (int) env('SAUVEGARDE_ESPACE_MINIMAL_MO', 200),

    // Jeton de l'adresse /cron/sauvegarde/<jeton> (obligatoire : sans jeton, l'adresse est désactivée)
    'jeton_cron' => env('CRON_SAUVEGARDE_TOKEN'),

    // Jeton de l'adresse existante /cron/executer-retards/<jeton>.
    // La valeur par défaut est l'ancien jeton, pour que la tâche cron-job.org actuelle continue de
    // fonctionner ; définir CRON_RETARDS_TOKEN dans .env puis mettre à jour l'adresse sur cron-job.org.
    'jeton_retards' => env('CRON_RETARDS_TOKEN', 'maishabora_cron_secret_7x9A2mP5vK3'),
];
