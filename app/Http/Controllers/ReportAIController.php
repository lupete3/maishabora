<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Credit;
use App\Services\AIReportService;
use Illuminate\Http\Request;

class ReportAIController extends Controller
{
    protected $ai;

    public function __construct(AIReportService $ai)
    {
        $this->ai = $ai;
    }

    public function dailySummary()
    {
        $today = now()->toDateString();

        // --- DÉPÔTS ---
        $depositTypes = ['dépôt', 'mise_quotidienne'];
        $deposits = Transaction::whereIn('type', $depositTypes)
            ->whereDate('created_at', $today)
            ->selectRaw('account_id, currency, SUM(amount) as total_amount')
            ->groupBy('account_id', 'currency')
            ->get();

        // --- RETRAITS ---
        $withdrawalTypes = ['retrait', 'retrait_carte_adhesion'];
        $withdrawals = Transaction::whereIn('type', $withdrawalTypes)
            ->whereDate('created_at', $today)
            ->selectRaw('account_id, currency, SUM(amount) as total_amount')
            ->groupBy('account_id', 'currency')
            ->get();

        // --- CRÉDITS ---
        $credits = Transaction::where('type', 'crédit')
            ->whereDate('created_at', $today)
            ->selectRaw('account_id, currency, SUM(amount) as total_amount')
            ->groupBy('account_id', 'currency')
            ->get();

        // Générer les résumés IA
        $summaryDeposits = app(\App\Services\AIReportService::class)->summarizeTransactions($deposits, 'depots');
        $summaryWithdrawals = app(\App\Services\AIReportService::class)->summarizeTransactions($withdrawals, 'retraits');
        $summaryCredits = app(\App\Services\AIReportService::class)->summarizeTransactions($credits, 'credits');

        return view('reports.daily-summary', compact(
            'summaryDeposits',
            'summaryWithdrawals',
            'summaryCredits',
            'today'
        ));
    }

}
