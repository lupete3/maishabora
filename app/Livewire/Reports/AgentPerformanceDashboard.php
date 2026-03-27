<?php

namespace App\Livewire\Reports;

use App\Models\MembershipCard;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class AgentPerformanceDashboard extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $filterAgent = '';
    public $filterDateFrom;
    public $filterDateTo;
    public $filterCurrency = 'all';
    public $marginPercent = 10; // Simulation de marge par défaut

    protected $queryString = [
        'filterAgent' => ['except' => ''],
        'filterDateFrom' => ['except' => ''],
        'filterDateTo' => ['except' => ''],
        'filterCurrency' => ['except' => 'all'],
    ];

    public function updatedMarginPercent($value)
    {
        if (!is_numeric($value) || $value === '') {
            $this->marginPercent = 0;
        }
    }

    public function mount()
    {
        $this->filterDateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->filterDateTo = Carbon::now()->format('Y-m-d');
    }

    public function updated($property)
    {
        if (in_array($property, ['filterAgent', 'filterDateFrom', 'filterDateTo', 'filterCurrency'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $agentsQuery = User::whereIn('role', ['recouvreur', 'caissier', 'receptionniste', 'comptable'])
            ->orderBy('name');

        $agentsList = (clone $agentsQuery)->get(['id', 'name', 'postnom']);

        $performanceData = $this->calculatePerformance($agentsQuery);

        return view('livewire.reports.agent-performance-dashboard', [
            'agents' => $agentsList,
            'performance' => $performanceData['agents'], // Paginated list
            'totals' => $performanceData['totals'],
        ]);
    }

    private function calculatePerformance($query)
    {
        // Apply filters to the query
        if ($this->filterAgent) {
            $query->where('id', $this->filterAgent);
        }

        // Clone query for totals BEFORE pagination
        $totalQuery = clone $query;
        $agentIds = $totalQuery->pluck('id');

        $totals = $this->getGlobalTotals($agentIds);

        // Paginate for the table
        $agents = $query->paginate(15);

        foreach ($agents as $agent) {
            $agent->metrics = $this->getAgentMetrics($agent->id);
        }

        return [
            'agents' => $agents,
            'totals' => $totals
        ];
    }

    private function getGlobalTotals($agentIds)
    {
        $dateFrom = $this->filterDateFrom;
        $dateTo = $this->filterDateTo;

        // Base queries
        $cardsBase = MembershipCard::whereIn('user_id', $agentIds)
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo]);

        $collectionsBase = Transaction::whereIn('user_id', $agentIds)
            ->where('type', 'mise_quotidienne')
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo]);

        if ($this->filterCurrency !== 'all') {
            $collectionsBase->where('currency', $this->filterCurrency);
        }

        // Apply heuristic for cards: Price >= 100 is CDF, < 100 is USD
        $cards = (clone $cardsBase)->get();
        $card_revenue_usd = $cards->where('price', '<', 100)->sum('price');
        $card_revenue_cdf = $cards->where('price', '>=', 100)->sum('price');

        // Apply heuristic for retained (profits): subscription_amount >= 100 is CDF, < 100 is USD
        $retainedCards = $cards->where('first_mise_retained', true);
        $retained_usd = $retainedCards->where('subscription_amount', '<', 100)->sum('subscription_amount');
        $retained_cdf = $retainedCards->where('subscription_amount', '>=', 100)->sum('subscription_amount');

        return [
            'cards' => $cards->count(),
            'card_revenue_usd' => $card_revenue_usd,
            'card_revenue_cdf' => $card_revenue_cdf,
            'retained_usd' => $retained_usd,
            'retained_cdf' => $retained_cdf,
            'collection_usd' => (clone $collectionsBase)->where('currency', 'USD')->sum('amount'),
            'collection_cdf' => (clone $collectionsBase)->where('currency', 'CDF')->sum('amount'),
        ];
    }

    private function getAgentMetrics($agentId)
    {
        $dateFrom = $this->filterDateFrom;
        $dateTo = $this->filterDateTo;

        $cards = MembershipCard::where('user_id', $agentId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
            ->get();

        // Cards totals with heuristic
        $cardCount = $cards->count();
        $cardRevenueUsd = $cards->where('price', '<', 100)->sum('price');
        $cardRevenueCdf = $cards->where('price', '>=', 100)->sum('price');

        // Retained totals with heuristic
        $retainedUsd = $cards->where('first_mise_retained', true)->where('subscription_amount', '<', 100)->sum('subscription_amount');
        $retainedCdf = $cards->where('first_mise_retained', true)->where('subscription_amount', '>=', 100)->sum('subscription_amount');

        // Total Collections (trusting transactions currency)
        $collections = Transaction::where('user_id', $agentId)
            ->where('type', 'mise_quotidienne')
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo]);

        if ($this->filterCurrency !== 'all') {
            $collections->where('currency', $this->filterCurrency);
        }

        return [
            'card_count' => $cardCount,
            'card_revenue_usd' => $cardRevenueUsd,
            'card_revenue_cdf' => $cardRevenueCdf,
            'retained_usd' => $retainedUsd,
            'retained_cdf' => $retainedCdf,
            'collection_usd' => (clone $collections)->where('currency', 'USD')->sum('amount'),
            'collection_cdf' => (clone $collections)->where('currency', 'CDF')->sum('amount'),
        ];
    }

    public function exportPdf()
    {
        $query = User::whereIn('role', ['recouvreur', 'caissier', 'receptionniste', 'comptable']);

        if ($this->filterAgent) {
            $query->where('id', $this->filterAgent);
        }

        $agents = $query->orderBy('name')->get();
        $totals = $this->getGlobalTotals($agents->pluck('id'));

        foreach ($agents as $agent) {
            $agent->metrics = $this->getAgentMetrics($agent->id);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.agent-performance', [
            'agents' => $agents,
            'totals' => $totals,
            'marginPercent' => $this->marginPercent,
            'filterDateFrom' => $this->filterDateFrom,
            'filterDateTo' => $this->filterDateTo,
            'filterCurrency' => $this->filterCurrency,
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'performance-agents-' . now()->format('Y-m-d') . '.pdf');
    }
}
