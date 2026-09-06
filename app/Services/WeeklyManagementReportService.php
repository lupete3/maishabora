<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\Repayment;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class WeeklyManagementReportService
{
    private const ACCOUNT_COMMISSION_CARNET = 97;
    private const ACCOUNT_RETENU_MISE = 195;
    private const ACCOUNT_ADHESION_MEMBER = 951;
    private const ACCOUNT_CHARGES = 452;

    private const DEPOSIT_TYPES = ['dépôt', 'mise_quotidienne'];
    private const WITHDRAWAL_TYPES = ['retrait', 'retrait_carte_adhesion'];
    private const CURRENCIES = ['CDF', 'USD'];

    public function build(?string $startDate = null, ?string $endDate = null): array
    {
        [$start, $end] = $this->resolvePeriod($startDate, $endDate);
        $previousStart = $start->copy()->subDays(7);
        $previousEnd = $end->copy()->subDays(7);

        $current = $this->periodData($start, $end);
        $previous = $this->periodData($previousStart, $previousEnd);

        return [
            'period' => [
                'start' => $start,
                'end' => $end,
                'label' => $this->periodLabel($start, $end),
                'business_days' => $this->businessDays($start, $end),
            ],
            'comparison_period' => [
                'start' => $previousStart,
                'end' => $previousEnd,
                'label' => $this->periodLabel($previousStart, $previousEnd),
                'business_days' => $this->businessDays($previousStart, $previousEnd),
            ],
            'current' => $current,
            'previous' => $previous,
            'variations' => $this->variations($current, $previous),
        ];
    }

    private function periodData(Carbon $start, Carbon $end): array
    {
        return [
            'new_clients' => $this->newClients($start, $end),
            'membership_cards' => $this->membershipCards($start, $end),
            'retenu_mise' => $this->agentAccountTotals(self::ACCOUNT_RETENU_MISE, $start, $end),
            'deposits_withdrawals' => $this->depositsWithdrawals($start, $end),
            'granted_credits' => $this->grantedCredits($start, $end),
            'overdue_credits' => $this->overdueCredits($end),
            'repayments' => $this->repayments($start, $end),
            'adhesion_member' => $this->agentAccountTotals(self::ACCOUNT_ADHESION_MEMBER, $start, $end),
            'charges' => $this->agentAccountTotals(self::ACCOUNT_CHARGES, $start, $end),
        ];
    }

    private function resolvePeriod(?string $startDate, ?string $endDate): array
    {
        if ($startDate && $endDate) {
            return [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ];
        }

        $end = now()->previous(Carbon::FRIDAY)->endOfDay();
        $start = $end->copy()->subDays(6)->startOfDay();

        return [$start, $end];
    }

    private function newClients(Carbon $start, Carbon $end): array
    {
        $clients = User::query()
            ->where('role', 'membre')
            ->whereBetween('created_at', [$start, $end])
            ->get(['id', 'name', 'postnom', 'prenom', 'sexe', 'created_at']);

        $men = $clients->filter(fn (User $user) => $this->isMale($user->sexe))->count();
        $women = $clients->filter(fn (User $user) => $this->isFemale($user->sexe))->count();
        $target = $this->businessDays($start, $end) * 5;

        return [
            'men' => $men,
            'women' => $women,
            'total' => $clients->count(),
            'target' => $target,
            'target_rate' => $target > 0 ? round($clients->count() * 100 / $target, 1) : 0,
        ];
    }

    private function membershipCards(Carbon $start, Carbon $end): array
    {
        $cardSales = Transaction::query()
            ->where('user_id', self::ACCOUNT_COMMISSION_CARNET)
            ->whereBetween('created_at', [$start, $end])
            ->get(['id', 'currency', 'amount', 'created_at']);

        return [
            'count' => $this->countsByCurrency($cardSales, 'currency'),
            'price_total' => $this->sumByCurrency($cardSales, 'amount'),
        ];
    }

    private function agentAccountTotals(int $accountId, Carbon $start, Carbon $end): array
    {
        return $this->moneyTotals(
            Transaction::query()
                ->where('user_id', $accountId)
                ->whereBetween('created_at', [$start, $end])
        );
    }

    private function depositsWithdrawals(Carbon $start, Carbon $end): array
    {
        $memberTransactions = Transaction::query()
            ->whereHas('user', fn (Builder $query) => $query->where('role', 'membre'))
            ->whereBetween('created_at', [$start, $end]);

        $deposits = $this->moneyTotals(
            (clone $memberTransactions)->whereIn('type', self::DEPOSIT_TYPES)
        );
        $withdrawals = $this->moneyTotals(
            (clone $memberTransactions)->whereIn('type', self::WITHDRAWAL_TYPES)
        );

        return [
            'deposits' => $deposits,
            'withdrawals' => $withdrawals,
            'net' => $this->subtractMoney($deposits, $withdrawals),
        ];
    }

    private function grantedCredits(Carbon $start, Carbon $end): array
    {
        $credits = Credit::query()
            ->with('user:id,code,name,postnom,prenom')
            ->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('start_date')
            ->get();

        return [
            'items' => $credits,
            'count' => $this->countsByCurrency($credits, 'currency'),
            'amount_total' => $this->sumByCurrency($credits, 'amount'),
            'fees_total' => $this->sumByCurrency($credits, 'frais_credit'),
            'mutuelle_total' => $this->sumByCurrency($credits, 'mutuelle'),
        ];
    }

    private function overdueCredits(Carbon $asOf): array
    {
        $credits = Credit::query()
            ->with(['user:id,code,name,postnom,prenom,telephone', 'repayments'])
            ->where('is_paid', false)
            ->whereHas('repayments', function (Builder $query) use ($asOf) {
                $query->where('is_paid', false)
                    ->whereDate('due_date', '<', $asOf->toDateString());
            })
            ->get();

        $details = $credits->map(function (Credit $credit) use ($asOf) {
            $overdueRepayments = $credit->repayments
                ->where('is_paid', false)
                ->filter(fn (Repayment $repayment) => Carbon::parse($repayment->due_date)->lt($asOf));

            $maxDaysLate = (int) ($overdueRepayments
                ->map(fn (Repayment $repayment) => Carbon::parse($repayment->due_date)->diffInDays($asOf))
                ->max() ?? 0);

            return [
                'credit' => $credit,
                'days_late' => $maxDaysLate,
                'overdue_installments' => $overdueRepayments->count(),
                'remaining_principal' => $overdueRepayments->sum(fn (Repayment $repayment) => max(0, (float) ($repayment->principal_amount ?? $repayment->expected_amount) - (float) $repayment->paid_principal)),
                'remaining_interest' => $overdueRepayments->sum(fn (Repayment $repayment) => max(0, (float) ($repayment->interest_amount ?? 0) - (float) $repayment->paid_interest)),
                'remaining_penalty' => $overdueRepayments->sum(fn (Repayment $repayment) => max(0, (float) $repayment->penalty - (float) $repayment->paid_penalty)),
            ];
        })->values();

        return [
            'items' => $details,
            'count' => $this->countDetailsByCurrency($details),
            'over_30_count' => $this->countDetailsByCurrency($details->filter(fn (array $row) => $row['days_late'] > 30)),
            'remaining_principal' => $this->sumDetailsByCurrency($details, 'remaining_principal'),
            'remaining_interest' => $this->sumDetailsByCurrency($details, 'remaining_interest'),
            'remaining_penalty' => $this->sumDetailsByCurrency($details, 'remaining_penalty'),
        ];
    }

    private function repayments(Carbon $start, Carbon $end): array
    {
        $repayments = Repayment::query()
            ->with('credit:id,currency,user_id')
            ->where('is_paid', true)
            ->whereBetween('paid_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        return [
            'count' => $this->countRepaymentsByCurrency($repayments),
            'paid_total' => $this->sumRepaymentsByCurrency($repayments, 'paid_amount'),
            'principal_total' => $this->sumRepaymentsByCurrency($repayments, 'paid_principal'),
            'interest_total' => $this->sumRepaymentsByCurrency($repayments, 'paid_interest'),
            'penalty_total' => $this->sumRepaymentsByCurrency($repayments, 'paid_penalty'),
        ];
    }

    private function moneyTotals(Builder $query): array
    {
        $totals = $query
            ->selectRaw('currency, SUM(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->map(fn ($value) => (float) $value)
            ->toArray();

        return $this->withCurrencies($totals);
    }

    private function sumByCurrency(Collection $rows, string $field): array
    {
        $totals = [];
        foreach (self::CURRENCIES as $currency) {
            $totals[$currency] = (float) $rows
                ->where('currency', $currency)
                ->sum(fn ($row) => (float) ($row->{$field} ?? 0));
        }

        return $totals;
    }

    private function countsByCurrency(Collection $rows, string $field): array
    {
        $counts = [];
        foreach (self::CURRENCIES as $currency) {
            $counts[$currency] = $rows->where($field, $currency)->count();
        }

        return $counts;
    }

    private function countRepaymentsByCurrency(Collection $repayments): array
    {
        $counts = ['CDF' => 0, 'USD' => 0];
        foreach ($repayments as $repayment) {
            $currency = $repayment->credit->currency ?? null;
            if (isset($counts[$currency])) {
                $counts[$currency]++;
            }
        }

        return $counts;
    }

    private function sumRepaymentsByCurrency(Collection $repayments, string $field): array
    {
        $totals = ['CDF' => 0.0, 'USD' => 0.0];
        foreach ($repayments as $repayment) {
            $currency = $repayment->credit->currency ?? null;
            if (isset($totals[$currency])) {
                $totals[$currency] += (float) ($repayment->{$field} ?? 0);
            }
        }

        return $totals;
    }

    private function countDetailsByCurrency(Collection $details): array
    {
        $counts = ['CDF' => 0, 'USD' => 0];
        foreach ($details as $detail) {
            $currency = $detail['credit']->currency ?? null;
            if (isset($counts[$currency])) {
                $counts[$currency]++;
            }
        }

        return $counts;
    }

    private function sumDetailsByCurrency(Collection $details, string $field): array
    {
        $totals = ['CDF' => 0.0, 'USD' => 0.0];
        foreach ($details as $detail) {
            $currency = $detail['credit']->currency ?? null;
            if (isset($totals[$currency])) {
                $totals[$currency] += (float) ($detail[$field] ?? 0);
            }
        }

        return $totals;
    }

    private function subtractMoney(array $left, array $right): array
    {
        return [
            'CDF' => ($left['CDF'] ?? 0) - ($right['CDF'] ?? 0),
            'USD' => ($left['USD'] ?? 0) - ($right['USD'] ?? 0),
        ];
    }

    private function withCurrencies(array $totals): array
    {
        return [
            'CDF' => (float) ($totals['CDF'] ?? 0),
            'USD' => (float) ($totals['USD'] ?? 0),
        ];
    }

    private function variations(array $current, array $previous): array
    {
        return [
            'new_clients_total' => $this->percentChange($current['new_clients']['total'], $previous['new_clients']['total']),
            'new_clients_men' => $this->percentChange($current['new_clients']['men'], $previous['new_clients']['men']),
            'new_clients_women' => $this->percentChange($current['new_clients']['women'], $previous['new_clients']['women']),
            'membership_cards_count' => $this->currencyPercentChange($current['membership_cards']['count'], $previous['membership_cards']['count']),
            'membership_cards_price_total' => $this->currencyPercentChange($current['membership_cards']['price_total'], $previous['membership_cards']['price_total']),
            'retenu_mise' => $this->currencyPercentChange($current['retenu_mise'], $previous['retenu_mise']),
            'deposits' => $this->currencyPercentChange($current['deposits_withdrawals']['deposits'], $previous['deposits_withdrawals']['deposits']),
            'withdrawals' => $this->currencyPercentChange($current['deposits_withdrawals']['withdrawals'], $previous['deposits_withdrawals']['withdrawals']),
            'net' => $this->currencyPercentChange($current['deposits_withdrawals']['net'], $previous['deposits_withdrawals']['net']),
            'granted_credits' => $this->currencyPercentChange($current['granted_credits']['amount_total'], $previous['granted_credits']['amount_total']),
            'repayments' => $this->currencyPercentChange($current['repayments']['paid_total'], $previous['repayments']['paid_total']),
            'adhesion_member' => $this->currencyPercentChange($current['adhesion_member'], $previous['adhesion_member']),
            'charges' => $this->currencyPercentChange($current['charges'], $previous['charges']),
        ];
    }

    private function currencyPercentChange(array $current, array $previous): array
    {
        return [
            'CDF' => $this->percentChange($current['CDF'] ?? 0, $previous['CDF'] ?? 0),
            'USD' => $this->percentChange($current['USD'] ?? 0, $previous['USD'] ?? 0),
        ];
    }

    private function percentChange(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0 ? 0.0 : null;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    private function businessDays(Carbon $start, Carbon $end): int
    {
        $days = 0;
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($cursor->lte($last)) {
            if (!$cursor->isSunday()) {
                $days++;
            }
            $cursor->addDay();
        }

        return $days;
    }

    private function periodLabel(Carbon $start, Carbon $end): string
    {
        return 'Du ' . $start->format('d/m/Y') . ' au ' . $end->format('d/m/Y');
    }

    private function isMale(?string $value): bool
    {
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['m', 'male', 'masculin', 'homme', 'h'], true);
    }

    private function isFemale(?string $value): bool
    {
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['f', 'female', 'feminin', 'féminin', 'femme'], true);
    }
}
