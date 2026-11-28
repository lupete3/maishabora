<?php

namespace App\Console\Commands;

use App\Models\SystemLog;
use App\Services\FirstMiseRetentionAudit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Régularisation des carnets RETIRÉS dont le retenu a été pris au retrait : met seulement
 * first_mise_retained à 1, pour que les rapports des agents comptent ce retenu.
 * Aucun mouvement d'argent. Simulation par défaut ; écriture uniquement avec --appliquer.
 *
 * Les carnets ACTIFS ne sont jamais modifiés : avec le marqueur à 1, le retrait ne prélèverait
 * plus le retenu manquant.
 *
 *   php artisan carnets:regulariser-retenus              (simulation, n'écrit rien)
 *   php artisan carnets:regulariser-retenus --appliquer
 */
class RegularizeFirstMiseRetention extends Command
{
    protected $signature = 'carnets:regulariser-retenus
        {--depuis=2026-10-12 : date du premier versement à partir de laquelle régulariser}
        {--appliquer : écrire réellement (sinon simulation)}';

    protected $description = 'Marque « retenu fait » les carnets retirés dont le retenu a été pris au retrait (simulation par défaut)';

    public function handle(FirstMiseRetentionAudit $audit): int
    {
        $carnets = $audit->carnets($this->option('depuis'))
            ->where('statut', FirstMiseRetentionAudit::RETIRE_RETENU_PRIS)
            ->values();

        if ($carnets->isEmpty()) {
            $this->info('Aucun carnet à régulariser.');

            return self::SUCCESS;
        }

        $this->table(
            ['Carnet', 'Code', 'Client', 'Devise', 'Retenu'],
            $carnets->map(fn ($c) => ["#{$c->id}", $c->code, $c->client, $c->currency, number_format((float) $c->subscription_amount, 2, ',', ' ')])->all()
        );

        if (! $this->option('appliquer')) {
            $this->warn("SIMULATION : {$carnets->count()} carnet(s) seraient marqués « retenu fait ». Rien n'a été modifié.");
            $this->line('Pour appliquer : php artisan carnets:regulariser-retenus --appliquer');

            return self::SUCCESS;
        }

        $ids = $carnets->pluck('id')->all();

        // Requête directe : ne touche que ce champ, PAS updated_at (date de retrait des carnets retirés)
        $modifies = DB::table('membership_cards')
            ->whereIn('id', $ids)
            ->where('is_active', false)
            ->where('first_mise_retained', false)
            ->update(['first_mise_retained' => true]);

        SystemLog::create([
            'level' => 'info',
            'message' => "Régularisation des retenus de première mise : {$modifies} carnet(s) retiré(s) marqués « retenu fait » (retenu pris au retrait) : #" . implode(', #', $ids),
        ]);

        $this->info("{$modifies} carnet(s) régularisé(s). Opération enregistrée dans les Logs du système.");

        return self::SUCCESS;
    }
}
