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
        $query = User::whereIn('role', ['recouvreur', 'caissier', 'receptionniste', 'comptable']);

        $agents = $query->orderBy('name')
            ->get(['id', 'name', 'postnom']);

        $performanceData = $this->calculatePerformance();

        return view('livewire.reports.agent-performance-dashboard', [
            'agents' => $agents,
            'performance' => $performanceData['agents'], // Paginated list
            'totals' => $performanceData['totals'],
        ]);
    }

    private function calculatePerformance()
    {
        $query = User::whereIn('role', ['recouvreur', 'caissier', 'receptionniste', 'comptable']);

        if ($this->filterAgent) {
            $query->where('id', $this->filterAgent);
        }

        $agents = $query->paginate(15);

        $totals = [
            'cards' => 0,
            'card_revenue_usd' => 0,
            'card_revenue_cdf' => 0,
            'retained_usd' => 0,
            'retained_cdf' => 0,
            'collection_usd' => 0,
            'collection_cdf' => 0,
        ];

        foreach ($agents as $agent) {
            // Metrics for this agent
            $agent->metrics = $this->getAgentMetrics($agent->id);

            // Accumulate totals (simplified as we don't have currency filter on totals yet)
            $totals['cards'] += $agent->metrics['card_count'];
            $totals['card_revenue_usd'] += $agent->metrics['card_revenue_usd'];
            $totals['card_revenue_cdf'] += $agent->metrics['card_revenue_cdf'];
            $totals['retained_usd'] += $agent->metrics['retained_usd'];
            $totals['retained_cdf'] += $agent->metrics['retained_cdf'];
            $totals['collection_usd'] += $agent->metrics['collection_usd'];
            $totals['collection_cdf'] += $agent->metrics['collection_cdf'];
        }

        return [
            'agents' => $agents,
            'totals' => $totals
        ];
    }

    private function getAgentMetrics($agentId)
    {
        $dateFrom = $this->filterDateFrom;
        $dateTo = $this->filterDateTo;

        // Cards created by agent
        $cards = MembershipCard::where('user_id', $agentId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo]);

        $cardCount = (clone $cards)->count();
        $cardRevenueUsd = (clone $cards)->where('currency', 'USD')->sum('price');
        $cardRevenueCdf = (clone $cards)->where('currency', 'CDF')->sum('price');

        // Retained first deposits
        // Based on the flag first_mise_retained and when it was set (updated_at of card or transaction?)
        // Let's use transactions for accuracy in date range
        $retainedQuery = Transaction::where('user_id', $agentId) // The agent who made the transaction
            ->where('type', 'mise_quotidienne')
            ->where('description', 'like', '%première mise retenue%') // Standard description in logic
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo]);

        // Actually, looking at MemberDetails.php:
        // 'description' => $this->getCardRetainedDescription($card)
        // Let's check getCardRetainedDescription

        $retainedUsd = Transaction::where('type', 'depot')
            ->where('description', 'like', "%Retenu première mise carnet #%")
            ->whereHas('paired', function ($q) use ($agentId) {
                // This might be complex, let's use a simpler approach based on descriptions if available
            })
            // Fallback: search for agent name in description if tagged or just sum all retained if agent filter is null
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo]);

        // RE-PLAN: Use MembershipCard directly for retained metrics if the flag is reliable
        $retainedUsd = (clone $cards)->where('first_mise_retained', true)->where('currency', 'USD')->sum('subscription_amount');
        $retainedCdf = (clone $cards)->where('first_mise_retained', true)->where('currency', 'CDF')->sum('subscription_amount');

        // Total Collections (mises quotidiennes)
        $collections = Transaction::where('user_id', $agentId)
            ->where('type', 'mise_quotidienne')
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo]);

        if ($this->filterCurrency !== 'all') {
            $collections->where('currency', $this->filterCurrency);
        }

        $collectionUsd = (clone $collections)->where('currency', 'USD')->sum('amount');
        $collectionCdf = (clone $collections)->where('currency', 'CDF')->sum('amount');

        return [
            'card_count' => $cardCount,
            'card_revenue_usd' => $cardRevenueUsd,
            'card_revenue_cdf' => $cardRevenueCdf,
            'retained_usd' => $retainedUsd,
            'retained_cdf' => $retainedCdf,
            'collection_usd' => $collectionUsd,
            'collection_cdf' => $collectionCdf,
        ];
    }
}
