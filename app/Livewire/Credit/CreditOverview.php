<?php

namespace App\Livewire\Credit;

use App\Models\Repayment;
use App\Models\CompanyInformation;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Livewire\WithPagination;

class CreditOverview extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

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
                    $totalsPerCurrency[$currency]['total'] += floatval($r->total_due);
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
            ->latest()
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
            ->latest()
            ->paginate(10, pageName: 'pageOverdue');
    }

    public function getUpcomingCreditsProperty()
    {
        return Repayment::with(['credit.user.accounts'])
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->where('is_paid', false)
            ->orderBy('due_date', 'asc')
            ->paginate(10, pageName: 'pageUpcoming');
    }

    // Propriété calculée pour la vue Web (Retard)
    public function getOverdueTotalsProperty()
    {
        $overdueCredits = Repayment::with('credit')
            ->where('due_date', '<', now())
            ->where('is_paid', false)
            ->get();

        return $this->calculateDetailedTotals($overdueCredits);
    }

    // Propriété calculée pour la vue Web (À venir)
    public function getUpcomingTotalsProperty()
    {
        $upcomingCredits = Repayment::with('credit')
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->where('is_paid', false)
            ->get();

        return $this->calculateDetailedTotals($upcomingCredits);
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
