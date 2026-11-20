<?php

namespace App\Livewire\Credit;

use App\Models\Repayment;
use App\Models\CompanyInformation;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Livewire\WithPagination;

class CreditOverview extends Component
{
    public $perPage = 10;

    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    private function aggregateDetailedTotals($query)
    {
        $principalRemaining = 'GREATEST(COALESCE(repayments.principal_amount, repayments.expected_amount) - COALESCE(repayments.paid_principal, 0), 0)';
        $interestRemaining = 'GREATEST(COALESCE(repayments.interest_amount, 0) - COALESCE(repayments.paid_interest, 0), 0)';
        $penaltyRemaining = 'GREATEST(COALESCE(repayments.penalty, 0) - COALESCE(repayments.paid_penalty, 0), 0)';

        return (clone $query)
            ->join('credits', 'repayments.credit_id', '=', 'credits.id')
            ->select('credits.currency')
            ->selectRaw("SUM({$principalRemaining}) as capital")
            ->selectRaw("SUM({$interestRemaining}) as interest")
            ->selectRaw("SUM({$penaltyRemaining}) as penalty")
            ->selectRaw("SUM({$principalRemaining} + {$interestRemaining} + {$penaltyRemaining}) as total")
            ->groupBy('credits.currency')
            ->get()
            ->mapWithKeys(function ($row) {
                return [
                    $row->currency => [
                        'capital' => (float) $row->capital,
                        'interest' => (float) $row->interest,
                        'penalty' => (float) $row->penalty,
                        'total' => (float) $row->total,
                    ],
                ];
            });
    }

    /**
     * Centralise le calcul détaillé des totaux (Principal, Intérêt, Pénalité) par devise
     * pour éviter la duplication de code entre l'export PDF et les propriétés de la page.
     */
    private function calculateDetailedTotals($repayments)
    {
        // 1. Charger tous les remboursements du même crédit pour recalculer correctement le dégressif
        $creditIds = $repayments->pluck('credit_id')->unique();

        // On récupère tout l'historique utile de ces crédits, trié par date d'échéance
        $allRepaymentsForCredits = Repayment::with('credit')
            ->whereIn('credit_id', $creditIds)
            ->orderBy('due_date', 'asc')
            ->get()
            ->groupBy('credit_id');

        $totalsPerCurrency = [];

        // 2. Simuler l'amortissement pour chaque crédit impliqué
        foreach ($allRepaymentsForCredits as $creditId => $creditRepayments) {
            $firstRepayment = $creditRepayments->first();
            if (!$firstRepayment || !$firstRepayment->credit) continue;

            $credit = $firstRepayment->credit;
            $currency = $credit->currency;
            $remainingCapital = floatval($credit->amount);

            if (!isset($totalsPerCurrency[$currency])) {
                $totalsPerCurrency[$currency] = ['capital' => 0, 'interest' => 0, 'penalty' => 0, 'total' => 0];
            }

            foreach ($creditRepayments as $r) {
                // Calcul de l'intérêt selon le type de crédit
                if ($credit->credit_type === 'degressif') {
                    $interest = round($remainingCapital * (floatval($credit->interest_rate) / 100), 2);
                } else {
                    $interest = round(floatval($credit->amount) * (floatval($credit->interest_rate) / 100), 2);
                }

                $capital = round(floatval($r->expected_amount) - $interest, 2);
                $remainingCapital = round($remainingCapital - $capital, 2);

                // IMPORTANT : On accumule le montant UNIQUEMENT si cette échéance fait partie de la sélection d'origine (en retard ou à venir)
                if ($repayments->contains('id', $r->id)) {
                    $totalsPerCurrency[$currency]['capital'] += $capital;
                    $totalsPerCurrency[$currency]['interest'] += $interest;
                    $totalsPerCurrency[$currency]['penalty'] += floatval($r->penalty);
                    $totalsPerCurrency[$currency]['total'] += floatval($r->total_due - $r->paid_amount);
                }
            }
        }

        return collect($totalsPerCurrency);
    }

    public function exportOverduePDF()
    {
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $overdueCredits = Repayment::with([
            'credit:id,user_id,currency,credit_type,amount,interest_rate', // Ajout des colonnes de calcul
            'credit.user:id,code,name,postnom',
            'credit.user.accounts:id,user_id,currency,type,balance'
        ])
            ->where('due_date', '<', now())
            ->where('is_paid', false)
            ->tap(fn ($query) => $this->orderByLeastLate($query))
            ->get();

        // Calcul des totaux détaillés
        $overdueTotals = $this->calculateDetailedTotals($overdueCredits);

        $company = CompanyInformation::getActiveOrDefault();

        $pdf = Pdf::loadView('pdf.credit-overdue-export', compact(
            'overdueCredits',
            'overdueTotals',
            'company'
        ))->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'credits_en_retard_' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportUpcomingPDF()
    {
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $upcomingCredits = Repayment::with([
            'credit:id,user_id,currency,credit_type,amount,interest_rate', // Ajout des colonnes de calcul
            'credit.user:id,code,name,postnom',
            'credit.user.accounts:id,user_id,currency,type,balance'
        ])
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->where('is_paid', false)
            ->orderBy('due_date', 'asc')
            ->get();

        // Calcul des totaux détaillés
        $upcomingTotals = $this->calculateDetailedTotals($upcomingCredits);

        $company = CompanyInformation::getActiveOrDefault();

        $pdf = Pdf::loadView('pdf.credit-upcoming-export', compact(
            'upcomingCredits',
            'upcomingTotals',
            'company'
        ))->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'credits_a_venir_' . now()->format('Y-m-d') . '.pdf');
    }

    public function getOverdueCreditsProperty()
    {
        return Repayment::with(['credit.user.accounts'])
            ->where('due_date', '<', now())
            ->where('is_paid', false)
            ->tap(fn ($query) => $this->orderByLeastLate($query))
            ->paginate($this->perPage, pageName: 'pageOverdue');
    }

    /**
     * Retards les plus récents en premier : échéance du jour (0 jour), puis 1 jour,
     * 20 jours, 100 jours… pour savoir qui recouvrer en priorité avant que le retard
     * ne s'installe. L'id départage les échéances du même jour (ordre stable entre pages).
     */
    private function orderByLeastLate($query)
    {
        return $query->orderByDesc('due_date')->orderByDesc('id');
    }

    /** Jours entiers de retard : échéance aujourd'hui = 0, hier = 1. */
    public static function daysLate($dueDate): int
    {
        return max(0, (int) \Carbon\Carbon::parse($dueDate)->startOfDay()->diffInDays(today(), false));
    }

    /** Jours entiers restants avant l'échéance : demain = 1. */
    public static function daysRemaining($dueDate): int
    {
        return max(0, (int) today()->diffInDays(\Carbon\Carbon::parse($dueDate)->startOfDay(), false));
    }

    public function getUpcomingCreditsProperty()
    {
        return Repayment::with(['credit.user.accounts'])
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->where('is_paid', false)
            ->orderBy('due_date', 'asc')
            ->paginate($this->perPage, pageName: 'pageUpcoming');
    }

    // Propriété calculée pour la vue Web (Retard)
    public function getOverdueTotalsProperty()
    {
        $overdueCredits = Repayment::query()
            ->where('repayments.due_date', '<', now())
            ->where('repayments.is_paid', false);

        return $this->aggregateDetailedTotals($overdueCredits);
    }

    // Propriété calculée pour la vue Web (À venir)
    public function getUpcomingTotalsProperty()
    {
        $upcomingCredits = Repayment::query()
            ->whereBetween('repayments.due_date', [now(), now()->addDays(7)])
            ->where('repayments.is_paid', false);

        return $this->aggregateDetailedTotals($upcomingCredits);
    }

    public function render()
    {
        return view('livewire.credit.credit-overview', [
            'overdueCredits' => $this->overdueCredits,
            'upcomingCredits' => $this->upcomingCredits,
            'overdueTotals' => $this->overdueTotals,
            'upcomingTotals' => $this->upcomingTotals,
        ]);
    }
}
