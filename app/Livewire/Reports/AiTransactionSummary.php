<?php

namespace App\Livewire\Reports;


use Livewire\Component;
use App\Models\Transaction;
use App\Services\AIReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AiTransactionSummary extends Component
{
    public $filterType = 'jour'; // jour, semaine, mois, periode
    public $startDate;
    public $endDate;
    public $summaryDeposits;
    public $summaryWithdrawals;
    public $summaryCredits;
    public $summaryGlobal;
    public $loading = false;

    protected $queryString = ['filterType', 'startDate', 'endDate'];

    public function mount()
    {
        $this->generateSummary();
    }

    public function updated($field)
    {
        if (in_array($field, ['filterType', 'startDate', 'endDate'])) {
            $this->generateSummary();
        }
    }

    public function getDateRange()
    {
        $today = Carbon::today();

        return match ($this->filterType) {
            'jour' => [$today, $today],
            'semaine' => [$today->startOfWeek(), $today->endOfWeek()],
            'mois' => [$today->startOfMonth(), $today->endOfMonth()],
            'periode' => [$this->startDate, $this->endDate],
            default => [$today, $today],
        };
    }

    public function generateSummary()
    {
        $this->loading = true;

        [$start, $end] = $this->getDateRange();

        $ai = new AIReportService();

        // Types définis
        $depositTypes = ['dépôt', 'mise_quotidienne'];
        $withdrawalTypes = ['retrait', 'retrait_carte_adhesion'];

        // --- Dépôts ---
        $deposits = Transaction::whereIn('type', $depositTypes)
            ->where('account_id', '!=', null)
            ->whereBetween(DB::raw('DATE(created_at)'), [$start, $end])
            ->selectRaw('account_id, currency, SUM(amount) as total_amount')
            ->groupBy('account_id', 'currency')
            ->get();

        // --- Retraits ---
        $withdrawals = Transaction::whereIn('type', $withdrawalTypes)
            ->where('account_id', '!=', null)
            ->whereBetween(DB::raw('DATE(created_at)'), [$start, $end])
            ->selectRaw('account_id, currency, SUM(amount) as total_amount')
            ->groupBy('account_id', 'currency')
            ->get();

        // --- Crédits ---
        $credits = Transaction::where('type', 'octroi_de_credit')
            ->whereBetween(DB::raw('DATE(created_at)'), [$start, $end])
            ->selectRaw('account_id, currency, SUM(amount) as total_amount')
            ->groupBy('account_id', 'currency')
            ->get();

        // --- Génération IA ---
        $this->summaryDeposits = $ai->summarizeTransactions($deposits, 'depots');
        $this->summaryWithdrawals = $ai->summarizeTransactions($withdrawals, 'retraits');
        $this->summaryCredits = $ai->summarizeTransactions($credits, 'credits');
        $this->summaryGlobal = $ai->summarizeGlobal($deposits, $withdrawals, $credits);

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.reports.ai-transaction-summary');
    }
}

