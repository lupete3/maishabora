<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Credit;
use App\Models\Repayment;
use App\Models\MainCashRegister;
use App\Models\User;
use App\Services\CreditStatsService;
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
    public $totalToRepayValue = [];
    public $totalRepaidValue = [];
    public $remainingBalanceValue = [];
    public $recoveryRate = [];
    public $overdueRate = [];
    public $inProgressRate = [];
    public $penaltyWeight = [];
    public $debtRatio = [];
    public $interestMargin = [];
    public $cashRegisters = [];

    // Filtres
    public $search = '';
    public $dateStart;
    public $dateEnd;
    public $currency = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'dateStart' => ['except' => ''],
        'dateEnd' => ['except' => ''],
        'currency' => ['except' => 'all'],
    ];

    public function mount()
    {
        Gate::authorize('afficher-tableaudebord-admin', User::class);
        $this->cashRegisters = MainCashRegister::all();
    }

    public function updated($property)
    {
        if (in_array($property, ['search', 'dateStart', 'dateEnd', 'currency'])) {
            $this->resetPage();
        }
    }

    public function render(CreditStatsService $statsService)
    {
        // Calcul des statistiques via le service
        $stats = $statsService->getGlobalStats([
            'search' => $this->search,
            'dateStart' => $this->dateStart,
            'dateEnd' => $this->dateEnd,
            'currency' => $this->currency,
        ]);

        foreach (['USD', 'CDF'] as $curr) {
            $s = $stats[$curr];
            $this->totalCreditsCount[$curr] = $s['totalCreditsCount'];
            $this->totalCreditsValue[$curr] = $s['totalCreditsValue'];
            $this->creditsInProgressCount[$curr] = $s['creditsInProgressCount'];
            $this->creditsInProgressValue[$curr] = $s['creditsInProgressValue'];
            $this->overdueCreditsCount[$curr] = $s['overdueCreditsCount'];
            $this->overdueCreditsValue[$curr] = $s['overdueCreditsValue'];
            $this->totalPenalties[$curr] = $s['totalPenalties'];
            $this->totalToRepayValue[$curr] = $s['totalToRepayValue'];
            $this->totalRepaidValue[$curr] = $s['totalRepaidValue'];
            $this->remainingBalanceValue[$curr] = $s['remainingBalanceValue'];
            $this->recoveryRate[$curr] = $s['recoveryRate'];
            $this->overdueRate[$curr] = $s['overdueRate'];
            $this->inProgressRate[$curr] = $s['inProgressRate'];
            $this->penaltyWeight[$curr] = $s['penaltyWeight'];
            $this->debtRatio[$curr] = $s['debtRatio'];
            $this->interestMargin[$curr] = $s['interestMargin'];
        }

        $this->totalCredits = array_sum($this->totalCreditsCount);
        $this->creditsInProgress = array_sum($this->creditsInProgressCount);

        $creditsQuery = Credit::with(['user', 'repayments'])->where('is_paid', false)->latest();
        $this->applyFilters($creditsQuery);
        $credits = $creditsQuery->paginate(10);

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

    private function applyFilters($query)
    {
        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('postnom', 'like', '%' . $this->search . '%')
                    ->orWhere('code', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->currency !== 'all') {
            $query->where('currency', $this->currency);
        }

        if ($this->dateStart && $this->dateEnd) {
            $query->whereBetween('created_at', [
                \Carbon\Carbon::parse($this->dateStart)->startOfDay(),
                \Carbon\Carbon::parse($this->dateEnd)->endOfDay()
            ]);
        }
    }

}
