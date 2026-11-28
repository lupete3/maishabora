<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Contrôle (lecture seule) : carnets qui ont des mises payées mais dont le retenu de la première
 * mise n'a pas été fait. Ne modifie aucune donnée.
 *
 *   php artisan carnets:controle-retenus                 (premier versement depuis le 12/10/2026)
 *   php artisan carnets:controle-retenus --depuis=2025-11-28
 */
class CheckFirstMiseRetention extends Command
{
    protected $signature = 'carnets:controle-retenus {--depuis=2026-10-12 : date du premier versement à partir de laquelle contrôler}';

    protected $description = 'Liste les carnets payés sans retenu de la première mise (lecture seule)';

    public function handle(): int
    {
        $depuis = $this->option('depuis');

        $carnets = DB::table('membership_cards as c')
            ->join('users as m', 'm.id', '=', 'c.member_id')
            ->whereNull('c.cancelled_at')
            ->where('c.card_type', 'epargne')
            ->where('c.first_mise_retained', false)
            ->selectRaw('c.id, c.code, c.currency, c.subscription_amount, m.code AS client_code,
                TRIM(CONCAT(m.name, " ", COALESCE(m.postnom, ""))) AS client,
                (SELECT MIN(d.updated_at) FROM daily_contributions d WHERE d.membership_card_id = c.id AND d.is_paid = 1) AS premier_versement,
                (SELECT COUNT(*) FROM daily_contributions d WHERE d.membership_card_id = c.id AND d.is_paid = 1) AS mises_payees')
            ->havingRaw('premier_versement >= ?', [$depuis])
            ->orderBy('premier_versement')
            ->get();

        if ($carnets->isEmpty()) {
            $this->info("Aucun carnet payé sans retenu depuis le {$depuis}.");

            return self::SUCCESS;
        }

        $this->warn("{$carnets->count()} carnet(s) payé(s) sans retenu de la première mise depuis le {$depuis} :");
        $this->table(
            ['Carnet', 'Code', 'Client', 'Devise', 'Retenu manquant', '1er versement', 'Mises payées'],
            $carnets->map(fn ($c) => [
                "#{$c->id}", $c->code, "{$c->client_code} {$c->client}", $c->currency,
                number_format((float) $c->subscription_amount, 2, ',', ' '), $c->premier_versement, $c->mises_payees,
            ])->all()
        );

        foreach ($carnets->groupBy('currency') as $devise => $groupe) {
            $this->line("Total non retenu {$devise} : " . number_format($groupe->sum('subscription_amount'), 2, ',', ' '));
        }

        return self::SUCCESS;
    }
}
