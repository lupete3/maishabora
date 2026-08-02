<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\Repayment;
use Carbon\Carbon;

class CreditStatsService
{
    /**
     * Get global credit statistics for USD and CDF.
     */
    public function getGlobalStats(array $filters = [])
    {
        $stats = [];

        foreach (['USD', 'CDF'] as $curr) {
            $baseQuery = Credit::where('currency', $curr);
            $this->applyFilters($baseQuery, $filters);

            $totalCreditsCount = (clone $baseQuery)->count();
            $totalCreditsValue = (float) (clone $baseQuery)->sum('amount');

            $inProgressQuery = Credit::where('currency', $curr)->where('is_paid', false);
            $this->applyFilters($inProgressQuery, $filters);
            $creditsInProgressCount = (clone $inProgressQuery)->count();
            $creditsInProgressValue = (float) (clone $inProgressQuery)->sum('amount');

            $overdueCreditsQuery = Credit::where('currency', $curr)
                ->where('is_paid', false)
                ->whereHas('repayments', function ($q) {
                    $q->where('due_date', '<', Carbon::now())
                        ->where('is_paid', false);
                });
            $this->applyFilters($overdueCreditsQuery, $filters);

            $overdueCreditsCount = (clone $overdueCreditsQuery)->count();
            $overdueCreditsValue = (float) (clone $overdueCreditsQuery)->sum('amount');

            $penaltyQuery = Repayment::whereHas('credit', function ($q) use ($curr, $filters) {
                $q->where('currency', $curr);
                $this->applyFilters($q, $filters);
            })->where('penalty', '>', 0);

            $totalPenalties = (float) $penaltyQuery->sum('penalty');

            $repaymentBaseQuery = Repayment::whereHas('credit', function ($q) use ($curr, $filters) {
                $q->where('currency', $curr);
                $this->applyFilters($q, $filters);
            });

            $totalToRepayValue = (float) (clone $repaymentBaseQuery)->selectRaw(
                'SUM(COALESCE(principal_amount, expected_amount) + COALESCE(interest_amount, 0) + COALESCE(penalty, 0)) as total'
            )->value('total');

            $totalRepaidValue = (float) (clone $repaymentBaseQuery)->selectRaw(
                'SUM(CASE WHEN (COALESCE(paid_principal, 0) + COALESCE(paid_interest, 0) + COALESCE(paid_penalty, 0)) > 0 THEN (COALESCE(paid_principal, 0) + COALESCE(paid_interest, 0) + COALESCE(paid_penalty, 0)) ELSE COALESCE(paid_amount, 0) END) as total'
            )->value('total');

            $remainingBalanceValue = max(0, $totalToRepayValue - $totalRepaidValue);

            $recoveryRate = $totalToRepayValue > 0 ? ($totalRepaidValue / $totalToRepayValue) * 100 : 0;
            $overdueRate = $creditsInProgressCount > 0 ? ($overdueCreditsCount / $creditsInProgressCount) * 100 : 0;
            $inProgressRate = $totalCreditsCount > 0 ? ($creditsInProgressCount / $totalCreditsCount) * 100 : 0;
            $penaltyWeight = $totalRepaidValue > 0 ? ($totalPenalties / $totalRepaidValue) * 100 : 0;
            $debtRatio = $totalToRepayValue > 0 ? ($remainingBalanceValue / $totalToRepayValue) * 100 : 0;
            $interestMargin = $totalCreditsValue > 0 ? (($totalToRepayValue - $totalCreditsValue) / $totalCreditsValue) * 100 : 0;

            $stats[$curr] = [
                'totalCreditsCount' => $totalCreditsCount,
                'totalCreditsValue' => $totalCreditsValue,
                'creditsInProgressCount' => $creditsInProgressCount,
                'creditsInProgressValue' => $creditsInProgressValue,
                'overdueCreditsCount' => $overdueCreditsCount,
                'overdueCreditsValue' => $overdueCreditsValue,
                'totalPenalties' => $totalPenalties,
                'totalToRepayValue' => $totalToRepayValue,
                'totalRepaidValue' => $totalRepaidValue,
                'remainingBalanceValue' => $remainingBalanceValue,
                'recoveryRate' => (float) $recoveryRate,
                'overdueRate' => (float) $overdueRate,
                'inProgressRate' => (float) $inProgressRate,
                'penaltyWeight' => (float) $penaltyWeight,
                'debtRatio' => (float) $debtRatio,
                'interestMargin' => (float) $interestMargin,
            ];
        }

        return $stats;
    }

    private function applyFilters($query, array $filters)
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('postnom', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        if (!empty($filters['currency']) && $filters['currency'] !== 'all') {
            $query->where('currency', $filters['currency']);
        }

        if (!empty($filters['dateStart']) && !empty($filters['dateEnd'])) {
            $query->whereBetween('created_at', [
                Carbon::parse($filters['dateStart'])->startOfDay(),
                Carbon::parse($filters['dateEnd'])->endOfDay()
            ]);
        }
    }
}
