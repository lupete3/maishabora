<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Credit;
use App\Models\Repayment;
use App\Models\MainCashRegister;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\WithPagination;

class GlobalCreditDashboard extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $totalCredits = 0;
    public $creditsInProgress = 0;
    public $totalCreditsCount = [];
    public $totalCreditsValue = [];
    public $creditsInProgressCount = [];
    public $creditsInProgressValue = [];
    public $overdueCreditsCount = [];
    public $overdueCreditsValue = [];
    public $totalPenalties = [];
    public $cashRegisters = [];

    public function mount()
    {
        // Vérifier que seul un agent de terrain peut accéder
        Gate::authorize('afficher-tableaudebord-admin', User::class);

        // Caisse centrale
        $this->cashRegisters = MainCashRegister::all();

        // Statistiques par devise
        foreach (['USD', 'CDF'] as $curr) {
            // Totaux
            $totalQuery = Credit::where('currency', $curr);
            $this->totalCreditsCount[$curr] = $totalQuery->count();
            $this->totalCreditsValue[$curr] = $totalQuery->sum('amount');

            // En cours
            $inProgressQuery = Credit::where('currency', $curr)->where('is_paid', false);
            $this->creditsInProgressCount[$curr] = $inProgressQuery->count();
            $this->creditsInProgressValue[$curr] = $inProgressQuery->sum('amount');

            // En retard (Crédits ayant au moins une échéance non payée et dépassée)
            $overdueCreditIds = Repayment::where('due_date', '<', now())
                ->where('is_paid', false)
                ->whereHas('credit', fn($q) => $q->where('currency', $curr))
                ->distinct()
                ->pluck('credit_id');

            $this->overdueCreditsCount[$curr] = $overdueCreditIds->count();
            $this->overdueCreditsValue[$curr] = Credit::whereIn('id', $overdueCreditIds)->sum('amount');

            // Pénalités
            $this->totalPenalties[$curr] = Repayment::whereHas('credit', fn($q) => $q->where('currency', $curr))
                ->where('penalty', '>', 0)
                ->sum('penalty');
        }

        // Totaux globaux (toutes devises confondues)
        $this->totalCredits = array_sum($this->totalCreditsCount);
        $this->creditsInProgress = array_sum($this->creditsInProgressCount);
    }

    public function render()
    {
        $credits = Credit::with(['user', 'repayments'])->where('is_paid', false)->latest()->paginate(10);

        // Crédits par mois
        $creditsByMonthData = Credit::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $creditsMonths = $creditsByMonthData->pluck('month')->toArray();
        $creditsCounts = $creditsByMonthData->pluck('count')->toArray();

        // Crédits par devise
        $creditsByCurrencyData = Credit::selectRaw('currency, COUNT(*) as count')
            ->groupBy('currency')
            ->get();

        $currencyLabels = $creditsByCurrencyData->pluck('currency')->toArray();
        $currencyCounts = $creditsByCurrencyData->pluck('count')->toArray();

        // Remboursements par mois
        $repaymentsByMonthData = Repayment::where('is_paid', true)
            ->selectRaw('DATE_FORMAT(paid_date, "%Y-%m") as month, SUM(expected_amount) as amount')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $repaymentMonths = $repaymentsByMonthData->pluck('month')->toArray();
        $repaymentAmounts = $repaymentsByMonthData->pluck('amount')->map(fn($a) => round($a, 2))->toArray();

        return view('livewire.admin.global-credit-dashboard', compact(
            'credits',
            'creditsMonths',
            'creditsCounts',
            'currencyLabels',
            'currencyCounts',
            'repaymentMonths',
            'repaymentAmounts',
        ));
    }

}
