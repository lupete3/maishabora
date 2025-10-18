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

        // Récupérer les transactions du jour
        $deposits = Transaction::where('type', 'depot')->whereDate('created_at', $today)->get();
        $withdrawals = Transaction::where('type', 'retrait')->whereDate('created_at', $today)->get();
        $credits = Credit::whereDate('created_at', $today)->get();

        // Générer les résumés
        $summaryDeposits = $this->ai->summarizeTransactions($deposits, 'depots');
        $summaryWithdrawals = $this->ai->summarizeTransactions($withdrawals, 'retraits');
        $summaryCredits = $this->ai->summarizeTransactions($credits, 'credits');

        return view('reports.daily-summary', compact(
            'summaryDeposits',
            'summaryWithdrawals',
            'summaryCredits',
            'today'
        ));
    }
}
