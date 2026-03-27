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
        $query = Transaction::query()->with('user');

        if ($this->agentId) {
            $query->where('user_id', $this->agentId);
        }

        if ($this->currency) {
            $query->where('currency', $this->currency);
        }

        $query = $this->applyPeriodFilter($query);

        return $query->latest()->paginate(20);
    }

    public function applyPeriodFilter($query)
    {
        $now = now();
        return match ($this->period) {
            'day' => $query->whereDate('created_at', $now),
            'week' => $query->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]),
            'month' => $query->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year),
            'year' => $query->whereYear('created_at', $now->year),
            'interval' => ($this->dateStart && $this->dateEnd)
            ? $query->whereBetween('created_at', [Carbon::parse($this->dateStart)->startOfDay(), Carbon::parse($this->dateEnd)->endOfDay()])
            : $query,
            default => $query,
        };
    }

    public function getTotalsProperty()
    {
        $query = Transaction::query();

        if ($this->agentId) {
            $query->where('user_id', $this->agentId);
        }

        if ($this->currency) {
            $query->where('currency', $this->currency);
        }

        $query = $this->applyPeriodFilter($query);

        return $query->selectRaw('currency, sum(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');
    }


    public function exportPdf()
    {
        $agent = $this->agentId ? User::find($this->agentId) : null;

        $query = Transaction::query()->with('user');
        if ($this->agentId) {
            $query->where('user_id', $this->agentId);
        }
        if ($this->currency) {
            $query->where('currency', $this->currency);
        }
        $query = $this->applyPeriodFilter($query);

        $transactions = $query->latest()->get();
        $totalByCurrency = $this->totals;

        $pdf = Pdf::loadView('pdf.agent-report', [
            'agent' => $agent,
            'transactions' => $transactions,
            'currency' => $this->currency,
            'period' => $this->period,
            'dateStart' => $this->dateStart,
            'dateEnd' => $this->dateEnd,
            'totalByCurrency' => $totalByCurrency,
            'isCentralized' => true
        ])->setPaper('A4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'rapport_general_transactions_agents.pdf');
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

