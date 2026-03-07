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

    public function render()
    {
        // Calcul des statistiques réactives
        foreach (['USD', 'CDF'] as $curr) {
            $baseQuery = Credit::where('currency', $curr);
            $this->applyFilters($baseQuery);

            $this->totalCreditsCount[$curr] = $baseQuery->count();
            $this->totalCreditsValue[$curr] = $baseQuery->sum('amount');

            $inProgressQuery = Credit::where('currency', $curr)->where('is_paid', false);
            $this->applyFilters($inProgressQuery);
            $this->creditsInProgressCount[$curr] = $inProgressQuery->count();
            $this->creditsInProgressValue[$curr] = $inProgressQuery->sum('amount');

            $overdueCreditIds = Repayment::where('due_date', '<', now())
                ->where('is_paid', false)
                ->whereHas('credit', function ($q) use ($curr) {
                    $q->where('currency', $curr);
                    $this->applyFilters($q);
                })
                ->distinct()
                ->pluck('credit_id');

            $this->overdueCreditsCount[$curr] = $overdueCreditIds->count();
            $this->overdueCreditsValue[$curr] = Credit::whereIn('id', $overdueCreditIds)->sum('amount');

            $penaltyQuery = Repayment::whereHas('credit', function ($q) use ($curr) {
                $q->where('currency', $curr);
                $this->applyFilters($q);
            })->where('penalty', '>', 0);

            $this->totalPenalties[$curr] = $penaltyQuery->sum('penalty');
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
