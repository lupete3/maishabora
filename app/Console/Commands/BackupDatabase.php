<?php

namespace App\Console\Commands;

use App\Services\AutomaticBackupService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Crée une sauvegarde de la base sur le serveur (storage/app/private/backups).
 *
 *   php artisan backup:database                  sauvegarde immédiate
 *   php artisan backup:database --si-necessaire  seulement si la dernière a plus de 20 h
 *                                                (utilisé par l'adresse cron, appelable toutes les heures)
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:database
                            {--si-necessaire : Ne rien faire si une sauvegarde récente existe déjà}
                            {--origine=commande : Origine indiquée dans le journal}';

    protected $description = 'Sauvegarde la base de données sur le serveur (conservation limitée)';

    public function handle(AutomaticBackupService $service): int
    {
        if ($this->option('si-necessaire') && ! $service->estNecessaire()) {
            $this->info(sprintf('Sauvegarde récente déjà présente (il y a %.1f h) : rien à faire.', $service->ageDerniereHeures()));

            return self::SUCCESS;
        }

        try {
            $resultat = $service->creer($this->option('origine'));
        } catch (Throwable $e) {
            $this->error('Échec de la sauvegarde : ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Sauvegarde créée : %s (%s Ko, %d tables, %d lignes, %.1f s). %d ancienne(s) supprimée(s).',
            $resultat['nom'], number_format($resultat['taille'] / 1024, 0, ',', ' '),
            $resultat['tables'], $resultat['lignes'], $resultat['duree'], $resultat['supprimees']));

        return self::SUCCESS;
    }
}
