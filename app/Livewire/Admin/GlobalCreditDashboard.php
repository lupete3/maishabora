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

    public $totalCredits;
    public $creditsInProgress;
    public $overdueCreditsCount;
    public $totalPenalties;
    public $cashRegisters = [];

    public function mount()
    {
        // Vérifier que seul un agent de terrain peut accéder
        Gate::authorize('afficher-tableaudebord-admin', User::class);

        // Caisse centrale
        $this->cashRegisters = MainCashRegister::all();

        // Statistiques générales
        $this->totalCredits = Credit::count();
        $this->creditsInProgress = Credit::where('is_paid', false)->count();
        // $this->totalPenalties = Repayment::whereHas('credit')->where('penalty', '>', 0)->sum('penalty');
        $this->totalPenalties = [
            'CDF' => Repayment::whereHas('credit', fn($q) => $q->where('currency', 'CDF'))
                            ->where('penalty', '>', 0)
                            ->sum('penalty'),

            'USD' => Repayment::whereHas('credit', fn($q) => $q->where('currency', 'USD'))
                            ->where('penalty', '>', 0)
                            ->sum('penalty'),
        ];


        // Crédits en retard
        $this->overdueCreditsCount = Repayment::where('due_date', '<', now())
            ->where('is_paid', false)
            ->distinct()
            ->count('credit_id');
    }

    public function getOverdueCreditsProperty()
    {
        return Repayment::with(['credit.user'])
            ->where('due_date', '<', now())
            ->where('is_paid', false)
            ->latest()
            ->paginate(5);
    }

    public function render()
    {
        $overdueCredits = $this->overdueCredits;

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
            'overdueCredits',
            'creditsMonths',
            'creditsCounts',
            'currencyLabels',
            'currencyCounts',
            'repaymentMonths',
            'repaymentAmounts',
        ));
    }

}
