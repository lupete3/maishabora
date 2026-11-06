<?php

/*
|--------------------------------------------------------------------------
| Module Objectifs — catalogue des indicateurs
|--------------------------------------------------------------------------
|
| Chaque indicateur décrit :
|  - label / description : affichage
|  - group               : regroupement à l'écran (voir 'groupes')
|  - unit                : count (nombre), amount (montant, par devise), percent (%)
|  - scopes              : agent (objectif propre à un agent) et/ou global (entreprise)
|  - direction           : up = plus c'est haut mieux c'est ; down = plafond à ne pas dépasser
|  - nature              : flux (cumul sur la période, progression attendue au prorata du temps)
|                          stock (situation à la fin de la période / à date)
|
| Le calcul de chaque indicateur est fait dans App\Services\ObjectifProgressService
| (méthode portant le même nom en camelCase). L'activation se règle dans l'écran
| de configuration, sans modifier ce fichier.
|
*/

return [

    'devises' => ['CDF', 'USD'],

    // Types de périodes proposés
    'periodes' => [
        'week'     => 'Semaine',
        'month'    => 'Mois',
        'quarter'  => 'Trimestre',
        'semester' => 'Semestre',
        'year'     => 'Année',
    ],

    // Statut : réalisé / attendu à date (en %)
    'seuils' => [
        'vigilance' => 80, // >= 80 % de l'attendu : vigilance (orange) ; en dessous : en retard (rouge)
        'tolerance_plafond' => 20, // indicateurs "down" : dépassement toléré (%) avant rouge
    ],

    'groupes' => [
        'activite'  => ['label' => 'Activité',                 'icon' => 'bx-pulse'],
        'collecte'  => ['label' => 'Collecte & épargne',       'icon' => 'bx-wallet'],
        'clientele' => ['label' => 'Clientèle',                'icon' => 'bx-group'],
        'carnets'   => ['label' => 'Carnets',                  'icon' => 'bx-book'],
        'credit'    => ['label' => 'Crédit',                   'icon' => 'bx-credit-card'],
        'qualite'   => ['label' => 'Qualité du portefeuille',  'icon' => 'bx-shield-quarter'],
    ],

    'indicateurs' => [

        // ---------------- Activité ----------------
        'operations_nombre' => [
            'label' => "Nombre d'opérations",
            'description' => 'Mises, dépôts, retraits, ventes et retraits de carnets enregistrés par l\'agent.',
            'group' => 'activite', 'unit' => 'count', 'scopes' => ['agent', 'global'],
            'direction' => 'up', 'nature' => 'flux',
        ],

        // ---------------- Collecte & épargne ----------------
        'mises_nombre' => [
            'label' => 'Mises quotidiennes (nombre)',
            'description' => 'Nombre de mises quotidiennes collectées.',
            'group' => 'collecte', 'unit' => 'count', 'scopes' => ['agent', 'global'],
            'direction' => 'up', 'nature' => 'flux',
        ],
        'mises_montant' => [
            'label' => 'Mises quotidiennes (montant)',
            'description' => 'Montant des mises quotidiennes collectées.',
            'group' => 'collecte', 'unit' => 'amount', 'scopes' => ['agent', 'global'],
            'direction' => 'up', 'nature' => 'flux',
        ],
        'depots_nombre' => [
            'label' => 'Dépôts (nombre)',
            'description' => 'Nombre de dépôts sur les comptes des membres.',
            'group' => 'collecte', 'unit' => 'count', 'scopes' => ['agent', 'global'],
            'direction' => 'up', 'nature' => 'flux',
        ],
        'depots_montant' => [
            'label' => 'Dépôts (montant)',
            'description' => 'Montant des dépôts sur les comptes des membres.',
            'group' => 'collecte', 'unit' => 'amount', 'scopes' => ['agent', 'global'],
            'direction' => 'up', 'nature' => 'flux',
        ],
        'collecte_totale' => [
            'label' => 'Collecte totale',
            'description' => 'Mises quotidiennes + dépôts.',
            'group' => 'collecte', 'unit' => 'amount', 'scopes' => ['agent', 'global'],
            'direction' => 'up', 'nature' => 'flux',
        ],
        'retraits_montant' => [
            'label' => 'Retraits (plafond)',
            'description' => 'Retraits et retraits de carnets servis. Objectif = montant à ne pas dépasser.',
            'group' => 'collecte', 'unit' => 'amount', 'scopes' => ['agent', 'global'],
            'direction' => 'down', 'nature' => 'flux',
        ],
        'epargne_nette' => [
            'label' => 'Épargne nette collectée',
            'description' => 'Collecte totale − retraits : ce qui reste réellement dans l\'institution.',
            'group' => 'collecte', 'unit' => 'amount', 'scopes' => ['agent', 'global'],
            'direction' => 'up', 'nature' => 'flux',
        ],
        'encours_epargne' => [
            'label' => "Encours d'épargne",
            'description' => 'Solde total des comptes des membres (du portefeuille de l\'agent ou de toute l\'entreprise), à date.',
            'group' => 'collecte', 'unit' => 'amount', 'scopes' => ['agent', 'global'],
            'direction' => 'up', 'nature' => 'stock',
        ],

        // ---------------- Clientèle ----------------
        'clients_sensibilises' => [
            'label' => 'Clients sensibilisés (nouveaux)',
            'description' => 'Nouveaux membres enregistrés et rattachés à l\'agent sur la période.',
            'group' => 'clientele', 'unit' => 'count', 'scopes' => ['agent', 'global'],
            'direction' => 'up', 'nature' => 'flux',
        ],
        'clients_actifs' => [
            'label' => 'Clients actifs',
            'description' => 'Membres du portefeuille ayant fait au moins une mise ou un dépôt sur la période.',
            'group' => 'clientele', 'unit' => 'count', 'scopes' => ['agent', 'global'],
            'direction' => 'up', 'nature' => 'stock',
        ],
        'taux_activite' => [
            'label' => "Taux d'activité du portefeuille",
            'description' => 'Clients actifs ÷ clients du portefeuille.',
            'group' => 'clientele', 'unit' => 'percent', 'scopes' => ['agent', 'global'],
            'direction' => 'up', 'nature' => 'stock',
        ],
        'portefeuille_clients' => [
            'label' => 'Taille du portefeuille',
            'description' => 'Nombre total de membres rattachés, à la fin de la période.',
            'group' => 'clientele', 'unit' => 'count', 'scopes' => ['agent', 'global'],
            'direction' => 'up', 'nature' => 'stock',
        ],

        // ---------------- Carnets ----------------
        'carnets_vendus' => [
            'label' => 'Carnets vendus',
            'description' => 'Carnets affectés à l\'agent sur la période (hors carnets annulés).',
            'group' => 'carnets', 'unit' => 'count', 'scopes' => ['agent', 'global'],
            'direction' => 'up', 'nature' => 'flux',
        ],
        'carnets_montant' => [
            'label' => 'Ventes de carnets (montant)',
            'description' => 'Chiffre d\'affaires des carnets vendus.',
            'group' => 'carnets', 'unit' => 'amount', 'scopes' => ['agent', 'global'],
            'direction' => 'up', 'nature' => 'flux',
        ],

        // ---------------- Crédit ----------------
        'credits_nombre' => [
            'label' => 'Crédits octroyés (nombre)',
            'description' => 'Nombre de crédits décaissés sur la période.',
            'group' => 'credit', 'unit' => 'count', 'scopes' => ['global', 'agent'],
            'direction' => 'up', 'nature' => 'flux',
        ],
        'credits_montant' => [
            'label' => 'Crédits octroyés (montant)',
            'description' => 'Montant décaissé sur la période.',
            'group' => 'credit', 'unit' => 'amount', 'scopes' => ['global', 'agent'],
            'direction' => 'up', 'nature' => 'flux',
        ],
        'beneficiaires' => [
            'label' => 'Bénéficiaires de crédit',
            'description' => 'Membres distincts ayant reçu un crédit sur la période.',
            'group' => 'credit', 'unit' => 'count', 'scopes' => ['global', 'agent'],
            'direction' => 'up', 'nature' => 'flux',
        ],
        'nouveaux_beneficiaires' => [
            'label' => 'Nouveaux bénéficiaires',
            'description' => 'Membres recevant leur tout premier crédit sur la période.',
            'group' => 'credit', 'unit' => 'count', 'scopes' => ['global', 'agent'],
            'direction' => 'up', 'nature' => 'flux',
        ],
        'remboursements_capital' => [
            'label' => 'Remboursements (capital)',
            'description' => 'Capital remboursé sur la période.',
            'group' => 'credit', 'unit' => 'amount', 'scopes' => ['global', 'agent'],
            'direction' => 'up', 'nature' => 'flux',
        ],
        'interets_encaisses' => [
            'label' => 'Intérêts encaissés',
            'description' => 'Intérêts payés sur la période.',
            'group' => 'credit', 'unit' => 'amount', 'scopes' => ['global', 'agent'],
            'direction' => 'up', 'nature' => 'flux',
        ],
        'penalites_encaissees' => [
            'label' => 'Pénalités encaissées',
            'description' => 'Pénalités de retard payées sur la période.',
            'group' => 'credit', 'unit' => 'amount', 'scopes' => ['global', 'agent'],
            'direction' => 'up', 'nature' => 'flux',
        ],
        'frais_credit' => [
            'label' => 'Frais de dossier crédit',
            'description' => 'Frais prélevés sur les crédits octroyés (montant × taux de frais).',
            'group' => 'credit', 'unit' => 'amount', 'scopes' => ['global', 'agent'],
            'direction' => 'up', 'nature' => 'flux',
        ],
        'encours_credit' => [
            'label' => 'Encours de crédit',
            'description' => 'Capital restant dû sur les crédits non soldés, à date.',
            'group' => 'credit', 'unit' => 'amount', 'scopes' => ['global', 'agent'],
            'direction' => 'up', 'nature' => 'stock',
        ],

        // ---------------- Qualité du portefeuille ----------------
        'taux_remboursement' => [
            'label' => 'Taux de remboursement',
            'description' => 'Montant payé ÷ montant dû, pour les échéances tombées sur la période.',
            'group' => 'qualite', 'unit' => 'percent', 'scopes' => ['global', 'agent'],
            'direction' => 'up', 'nature' => 'stock',
        ],
        'par_30' => [
            'label' => 'PAR 30 (plafond)',
            'description' => 'Portefeuille à risque : encours des crédits ayant une échéance impayée depuis plus de 30 jours ÷ encours total.',
            'group' => 'qualite', 'unit' => 'percent', 'scopes' => ['global', 'agent'],
            'direction' => 'down', 'nature' => 'stock',
        ],
        'credits_en_retard' => [
            'label' => 'Crédits en retard (plafond)',
            'description' => 'Nombre de crédits ayant au moins une échéance impayée échue.',
            'group' => 'qualite', 'unit' => 'count', 'scopes' => ['global', 'agent'],
            'direction' => 'down', 'nature' => 'stock',
        ],
    ],
];
