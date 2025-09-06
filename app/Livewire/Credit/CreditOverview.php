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

    public function render()
    {
        $overdueCredits = $this->overdueCredits;
        $upcomingCredits = $this->upcomingCredits;

        return view('livewire.credit.credit-overview', compact(

            'overdueCredits',
            'upcomingCredits',
        ));
    }
}
