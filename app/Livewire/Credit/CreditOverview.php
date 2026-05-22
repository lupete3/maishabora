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
     * Exporte les échéances en retard en PDF avec requêtes hautement optimisées
     */
    public function exportOverduePDF()
    {
        // Prévenir les timeouts et débordements de mémoire pour les grands volumes
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        // Optimisation de la requête : sélection stricte des colonnes nécessaires
        $overdueCredits = Repayment::with([
            'credit:id,user_id,currency',
            'credit.user:id,code,name,postnom',
            'credit.user.accounts:id,user_id,currency,type,balance'
        ])
            ->where('due_date', '<', now())
            ->where('is_paid', false)
            ->latest()
            ->get();

        // Calcul des totaux en mémoire pour éviter des requêtes SQL supplémentaires
        $overdueTotals = $overdueCredits->groupBy('credit.currency')
            ->map(function ($items) {
                return $items->sum('total_due');
            });

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

    /**
     * Exporte les échéances des 7 prochains jours en PDF avec requêtes hautement optimisées
     */
    public function exportUpcomingPDF()
    {
        // Prévenir les timeouts et débordements de mémoire pour les grands volumes
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        // Optimisation de la requête : sélection stricte des colonnes nécessaires
        $upcomingCredits = Repayment::with([
            'credit:id,user_id,currency',
            'credit.user:id,code,name,postnom',
            'credit.user.accounts:id,user_id,currency,type,balance'
        ])
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->where('is_paid', false)
            ->orderBy('due_date', 'asc')
            ->get();

        // Calcul des totaux en mémoire pour éviter des requêtes SQL supplémentaires
        $upcomingTotals = $upcomingCredits->groupBy('credit.currency')
            ->map(function ($items) {
                return $items->sum('total_due');
            });

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

    // Paiements en retard (pour affichage paginé dans l'interface web)
    public function getOverdueCreditsProperty()
    {
        return Repayment::with(['credit.user.accounts'])
            ->where('due_date', '<', now())
            ->where('is_paid', false)
            ->latest()
            ->paginate(5, pageName: 'pageOverdue');
    }

    // Paiements à venir (pour affichage paginé dans l'interface web)
    public function getUpcomingCreditsProperty()
    {
        return Repayment::with(['credit.user.accounts'])
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->where('is_paid', false)
            ->orderBy('due_date', 'asc')
            ->paginate(5, pageName: 'pageUpcoming');
    }

    // NOUVEAU: Calcule le total dû en retard par devise
    public function getOverdueTotalsProperty()
    {
        return Repayment::where('due_date', '<', now())
            ->where('is_paid', false)
            ->with('credit')
            ->get()
            ->groupBy('credit.currency')
            ->map(function ($items, $currency) {
                return $items->sum('total_due');
            });
    }

    // NOUVEAU: Calcule le total dû à venir par devise
    public function getUpcomingTotalsProperty()
    {
        return Repayment::whereBetween('due_date', [now(), now()->addDays(7)])
            ->where('is_paid', false)
            ->with('credit')
            ->get()
            ->groupBy('credit.currency')
            ->map(function ($items, $currency) {
                return $items->sum('total_due');
            });
    }

    public function render()
    {
        $overdueCredits = $this->overdueCredits;
        $upcomingCredits = $this->upcomingCredits;
        $overdueTotals = $this->overdueTotals;
        $upcomingTotals = $this->upcomingTotals;

        return view('livewire.credit.credit-overview', compact(
            'overdueCredits',
            'upcomingCredits',
            'overdueTotals',
            'upcomingTotals',
        ));
    }
}