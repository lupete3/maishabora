<?php

namespace App\Console\Commands;

use App\Models\TermDeposit;
use App\Services\TermDepositService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SettleMaturedTermDeposits extends Command
{
    protected $signature = 'term-deposits:accrue-monthly-interest';

    protected $description = 'Vérifier les mensualités d’intérêts à capitaliser sur les dépôts à terme';

    public function handle(TermDepositService $termDepositService): int
    {
        $processed = 0;
        $failed = 0;

        TermDeposit::query()
            ->where('status', 'active')
            ->whereDate('matures_at', '<=', today())
            ->update(['status' => 'matured']);

        TermDeposit::query()
            ->whereIn('status', ['active', 'matured'])
            ->orderBy('id')
            ->chunkById(100, function ($termDeposits) use ($termDepositService, &$processed, &$failed) {
                foreach ($termDeposits as $termDeposit) {
                    try {
                        $termDepositService->accrueDailyInterest($termDeposit->id);
                        $processed++;
                    } catch (Throwable $exception) {
                        $failed++;
                        Log::error('Échec du calcul de la mensualité d’un dépôt à terme.', [
                            'term_deposit_id' => $termDeposit->id,
                            'exception' => $exception->getMessage(),
                        ]);
                        $this->error("Contrat #{$termDeposit->id}: calcul des intérêts échoué.");
                    }
                }
            });

        $this->info("Contrats vérifiés: {$processed}; échecs: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}