<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AgentReportController extends Controller
{
    public function index()
    {
        return view('reports.agent-performance-index');
    }

    public function export(Request $request)
    {
        $agentId = $request->query('agent');
        $dateFrom = $request->query('from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->query('to', now()->format('Y-m-d'));
        $currency = $request->query('currency', 'all');
        $marginPercent = $request->query('margin', 10);

        $query = \App\Models\User::where('role', '!=', 'membre');
        if ($agentId) {
            $query->where('id', $agentId);
        }
        $agents = $query->get();

        $data = [];
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
            $metrics = $this->getMetrics($agent->id, $dateFrom, $dateTo, $currency);
            $agent->metrics = $metrics;
            $data[] = $agent;

            $totals['cards'] += $metrics['card_count'];
            $totals['card_revenue_usd'] += $metrics['card_revenue_usd'];
            $totals['card_revenue_cdf'] += $metrics['card_revenue_cdf'];
            $totals['retained_usd'] += $metrics['retained_usd'];
            $totals['retained_cdf'] += $metrics['retained_cdf'];
            $totals['collection_usd'] += $metrics['collection_usd'];
            $totals['collection_cdf'] += $metrics['collection_cdf'];
        }

        $pdfData = [
            'agents' => $data,
            'totals' => $totals,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'currency' => $currency,
            'marginPercent' => $marginPercent,
            'generatedAt' => now()->format('d/m/Y H:i')
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.agent-performance', $pdfData)->setPaper('a4', 'landscape');

        return $pdf->stream('performance_agents_' . now()->format('Ymd_His') . '.pdf');
    }

    private function getMetrics($agentId, $dateFrom, $dateTo, $currencyFilter)
    {
        // Cards
        $cards = \App\Models\MembershipCard::where('user_id', $agentId)
            ->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(created_at)'), [$dateFrom, $dateTo]);

        $cardCount = (clone $cards)->count();
        $cardRevenueUsd = (clone $cards)->where('currency', 'USD')->sum('price');
        $cardRevenueCdf = (clone $cards)->where('currency', 'CDF')->sum('price');

        // Retained (First Mises)
        $retainedUsd = (clone $cards)->where('first_mise_retained', true)->where('currency', 'USD')->sum('subscription_amount');
        $retainedCdf = (clone $cards)->where('first_mise_retained', true)->where('currency', 'CDF')->sum('subscription_amount');

        // Collections
        $collQuery = \App\Models\Transaction::where('user_id', $agentId)
            ->where('type', 'mise_quotidienne')
            ->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(created_at)'), [$dateFrom, $dateTo]);

        if ($currencyFilter !== 'all') {
            $collQuery->where('currency', $currencyFilter);
        }

        $collectionUsd = (clone $collQuery)->where('currency', 'USD')->sum('amount');
        $collectionCdf = (clone $collQuery)->where('currency', 'CDF')->sum('amount');

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
