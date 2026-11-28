<?php

namespace App\Console\Commands;

use App\Services\FirstMiseRetentionAudit;
use Illuminate\Console\Command;

/**
 * Contrôle (lecture seule) : carnets qui ont des mises payées mais dont le retenu de la première
 * mise n'a pas été fait, classés selon ce qu'il reste à faire. Ne modifie aucune donnée.
 *
 *   php artisan carnets:controle-retenus                 (premier versement depuis le 12/10/2026)
 *   php artisan carnets:controle-retenus --depuis=2025-11-28
 */
class CheckFirstMiseRetention extends Command
{
    protected $signature = 'carnets:controle-retenus {--depuis=2026-10-12 : date du premier versement à partir de laquelle contrôler}';

    protected $description = 'Liste les carnets payés sans retenu de la première mise, par groupe (lecture seule)';

    public function handle(FirstMiseRetentionAudit $audit): int
    {
        $depuis = $this->option('depuis');
        $carnets = $audit->carnets($depuis);

        if ($carnets->isEmpty()) {
            $this->info("Aucun carnet payé sans retenu depuis le {$depuis}.");

            return self::SUCCESS;
        }

        $this->warn("{$carnets->count()} carnet(s) payé(s) sans retenu de la première mise depuis le {$depuis}.");

        foreach (FirstMiseRetentionAudit::LIBELLES as $statut => $libelle) {
            $groupe = $carnets->where('statut', $statut);
            $this->newLine();
            $this->line("<options=bold>{$libelle} : {$groupe->count()} carnet(s)</>");

            if ($groupe->isEmpty()) {
                continue;
            }

            $this->table(
                ['Carnet', 'Code', 'Client', 'Devise', 'Retenu', '1er versement', 'Mises payées'],
                $groupe->map(fn ($c) => [
                    "#{$c->id}", $c->code, $c->client, $c->currency,
                    number_format((float) $c->subscription_amount, 2, ',', ' '), $c->premier_versement, $c->mises_payees,
                ])->all()
            );

            foreach ($groupe->groupBy('currency') as $devise => $parDevise) {
                $this->line("  Total {$devise} : " . number_format($parDevise->sum('subscription_amount'), 2, ',', ' '));
            }
        }

        $this->newLine();
        $this->line('Correction des rapports (carnets retirés avec retenu pris) : php artisan carnets:regulariser-retenus (simulation), puis --appliquer.');

        return self::SUCCESS;
    }
}
