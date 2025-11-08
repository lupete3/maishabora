<?php

namespace App\Livewire\Credit;

use App\Models\Repayment;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class CreditOverview extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    // Paiements en retard
    public function getOverdueCreditsProperty()
    {
        return Repayment::with(['credit.user.accounts'])
            ->where('repayments.due_date', '<', now())
            ->where('repayments.is_paid', false)
            ->latest('repayments.due_date')
            ->paginate(5);
    }

    // Paiements à venir (7 jours)
    public function getUpcomingCreditsProperty()
    {
        return Repayment::with(['credit.user.accounts'])
            ->whereBetween('repayments.due_date', [now(), now()->addDays(7)])
            ->where('repayments.is_paid', false)
            ->orderBy('repayments.due_date', 'asc')
            ->paginate(5);
    }

    // Totaux par devise
    public function getTotalsByCurrencyProperty()
    {
        $now = now();

        // Totaux des crédits en retard
        $overdueTotals = Repayment::join('credits', 'repayments.credit_id', '=', 'credits.id')
            ->select('credits.currency', DB::raw('SUM(repayments.total_due) as total'))
            ->where('repayments.due_date', '<', $now)
            ->where('repayments.is_paid', false)
            ->groupBy('credits.currency')
            ->pluck('total', 'credits.currency');

        // Totaux des crédits à venir (7 jours)
        $upcomingTotals = Repayment::join('credits', 'repayments.credit_id', '=', 'credits.id')
            ->select('credits.currency', DB::raw('SUM(repayments.total_due) as total'))
            ->whereBetween('repayments.due_date', [$now, $now->copy()->addDays(7)])
            ->where('repayments.is_paid', false)
            ->groupBy('credits.currency')
            ->pluck('total', 'credits.currency');

        return [
            'overdue' => $overdueTotals,
            'upcoming' => $upcomingTotals,
        ];
    }

    public function render()
    {
        $overdueCredits = $this->overdueCredits;
        $upcomingCredits = $this->upcomingCredits;
        $totals = $this->totalsByCurrency;

        return view('livewire.credit.credit-overview', compact(
            'overdueCredits',
            'upcomingCredits',
            'totals'
        ));
    }
}