<?php

namespace App\Livewire\Repports;

use Livewire\Component;
use App\Models\User;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Livewire\WithPagination;

class AgentTransactionsReport extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';


    public $agentId = '';
    public $currency = '';
    public $period = 'day';
    public $dateStart;
    public $dateEnd;

    public function updated($field)
    {
        if (in_array($field, ['agentId', 'currency', 'period', 'dateStart', 'dateEnd'])) {
            $this->resetPage();
        }
    }

    public function getTransactionsProperty()
    {
        $query = Transaction::query();

        if ($this->agentId) {
            $query->where('user_id', $this->agentId);
        }

        if ($this->currency) {
            $query->where('currency', $this->currency);
        }

        if ($this->period === 'interval' && $this->dateStart && $this->dateEnd) {
            $query->whereBetween('created_at', [$this->dateStart, $this->dateEnd]);
        } else {
            $query = $this->applyPeriodFilter($query);
        }

        return $query->latest()->paginate(20);
    }

    public function applyPeriodFilter($query)
    {
        $now = now();
        return match ($this->period) {
            'day' => $query->whereDate('created_at', $now->toDateString()),
            'week' => $query->whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()]),
            'month' => $query->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year),
            'year' => $query->whereYear('created_at', $now->year),
            default => $query,
        };
    }

    public function getTotalsProperty()
    {
        $baseQuery = $this->transactions;

        // Total global
        $total = $baseQuery->sum('amount');

        return [
            'total' => $total,
        ];
    }


    public function exportPdf()
    {
        // Récupérer l’agent sélectionné
        $agent = User::find($this->agentId);

        // Appliquer les filtres comme dans la méthode render()
        $query = Transaction::query();

        if ($this->agentId) {
            $query->where('user_id', $this->agentId);
        }

        if ($this->currency) {
            $query->where('currency', $this->currency);
        }

        // Appliquer la période
        $startDate = null;
        $endDate = null;

        if ($this->period === 'day') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($this->period === 'week') {
            $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($this->period === 'month') {
            $query->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year);
        } elseif ($this->period === 'year') {
            $query->whereYear('created_at', Carbon::now()->year);
        } elseif ($this->period === 'custom' && $this->start_date && $this->end_date) {
            $startDate = Carbon::parse($this->start_date)->startOfDay();
            $endDate = Carbon::parse($this->end_date)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        // Calcul des totaux
        $totalDeposits = $transactions->where('type', 'deposit')->sum('amount');
        $totalWithdrawals = $transactions->where('type', 'withdrawal')->sum('amount');
        $totalTransactions = $transactions->sum('amount');

        // Générer le PDF
        $pdf = Pdf::loadView('pdf.agent-report', [
            'agent' => $agent,
            'transactions' => $transactions,
            'currency' => $this->currency,
            'period' => $this->period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalDeposits' => $totalDeposits,
            'totalWithdrawals' => $totalWithdrawals,
            'totalTransactions' => $totalTransactions,
        ])->setPaper('A4', 'portrait');

        // Télécharger
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'rapport_transactions_agent.pdf');
    }

    public function render()
    {
        return view('livewire.repports.agent-transactions-report', [
            'agents' => User::whereHas('agentAccounts')->get(),
            'transactions' => $this->transactions,
            'totals' => $this->totals,
        ]);
    }
}

