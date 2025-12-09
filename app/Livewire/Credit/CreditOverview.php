<?php

namespace App\Livewire\Credit;

use App\Models\Repayment;
use Livewire\Component;
use Livewire\WithPagination;

class CreditOverview extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';
    
    //Paiements en retard
    public function getOverdueCreditsProperty()
    {
        return Repayment::with(['credit.user'])
            ->where('due_date', '<', now())
            ->where('is_paid', false)
            ->latest()
            ->paginate(5);
    }

    //Paiements à venir
    public function getUpcomingCreditsProperty()
    {
        return Repayment::with(['credit.user'])
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->where('is_paid', false)
            ->orderBy('due_date', 'asc')
            ->paginate(5);
    }

    // NOUVEAU: Calcule le total dû en retard par devise
    public function getOverdueTotalsProperty()
    {
        return Repayment::where('due_date', '<', now())
            ->where('is_paid', false)
            ->with('credit')
            ->get()
            ->groupBy('credit.currency') // Regroupe par la devise du crédit
            ->map(function ($items, $currency) {
                return $items->sum('total_due'); // Calcule la somme de total_due pour chaque devise
            });
    }

    // NOUVEAU: Calcule le total dû à venir par devise
    public function getUpcomingTotalsProperty()
    {
        return Repayment::whereBetween('due_date', [now(), now()->addDays(7)])
            ->where('is_paid', false)
            ->with('credit')
            ->get()
            ->groupBy('credit.currency') // Regroupe par la devise du crédit
            ->map(function ($items, $currency) {
                return $items->sum('total_due'); // Calcule la somme de total_due pour chaque devise
            });
    }

    public function render()
    {
        $overdueCredits = $this->overdueCredits;
        $upcomingCredits = $this->upcomingCredits;
        // NOUVEAU: Récupérer les totaux
        $overdueTotals = $this->overdueTotals;
        $upcomingTotals = $this->upcomingTotals;

        return view('livewire.credit.credit-overview', compact(
            'overdueCredits',
            'upcomingCredits',
            // NOUVEAU: Passer les totaux à la vue
            'overdueTotals',
            'upcomingTotals',
        ));
    }
}