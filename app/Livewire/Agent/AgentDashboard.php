<?php

namespace App\Livewire\Agent;

use App\Exports\AgentTransactionsExport;
use Livewire\Component;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class AgentDashboard extends Component
{
    public $today;
    public $user_id;
    public $isShowTransaction = false;
    public $transactions = [];
    public $transactionCount;
    public $totalByCurrency;
    public $selectedAgent;
    public $showConfirmModal = false;
    public $filter = 'day';

    protected $queryString = ['filter'];

    public $startDate;
    public $endDate;
    public $periodLabel;


    public function mount()
    {
        $user = Auth::user();
    }

    public function applyCustomFilter()
    {
        if (!$this->startDate || !$this->endDate) {
            return;
        }

        $this->showTransactions($this->selectedAgent, 'custom', $this->startDate, $this->endDate);
    }

    protected function applyDateFilter($query)
    {
        $now = now();

        switch ($this->filter) {

            case 'custom':
                if ($this->startDate && $this->endDate) {
                    $start = Carbon::parse($this->startDate)->startOfDay();
                    $end   = Carbon::parse($this->endDate)->endOfDay();
                    $this->periodLabel = "Du " . $start->format('d/m/Y') . " au " . $end->format('d/m/Y');
                    return $query->whereBetween('created_at', [$start, $end]);
                }
                return $query;

            case 'day':
                $this->periodLabel = "Aujourd'hui";
                return $query->whereDate('created_at', $now);

            case 'week':
                $start = $now->startOfWeek();
                $end   = $now->endOfWeek();
                $this->periodLabel = "Semaine";
                return $query->whereBetween('created_at', [$start, $end]);

            case 'month':
                $this->periodLabel = "Mois";
                return $query->whereMonth('created_at', $now->month);

            case 'year':
                $this->periodLabel = "Année";
                return $query->whereYear('created_at', $now->year);

            default:
                $this->periodLabel = "Toutes";
                return $query;
        }
    }

    public function showTransactions($userId, $filter = 'day', $start = null, $end = null)
    {
        $this->user_id = $userId;
        $this->filter = $filter;
        $this->startDate = $start;
        $this->endDate = $end;
        $this->isShowTransaction = true;

        $query = Transaction::where('user_id', $this->user_id);

        // APPLIQUER FILTRE
        $query = $this->applyDateFilter($query);

        $this->transactions = $query->orderByDesc('created_at')->get();
        $this->transactionCount = $this->transactions->count();

        $this->totalByCurrency = $this->transactions
            ->groupBy('currency')
            ->map(fn($group) => $group->sum('amount'));
    }

    public function placeholder()
    {
        return view('livewire.placeholder');
    }

    public function render()
    {
        $user = Auth::user();

        if ($user->can('afficher-caisse-agent')) {
            $agentAccounts = User::whereHas('agentAccounts')
                ->with(['agentAccounts' => function ($query) {
                    $query->orderBy('currency');
                }])
                ->get();
        } else {
            $agentAccounts = User::where('id', $user->id)
                ->with(['agentAccounts' => function ($query) {
                    $query->orderBy('currency');
                }])
                ->get();
        }

        return view('livewire.agent.agent-dashboard', [
            'agentAccounts' => $agentAccounts
        ]);
    }

    public function showIntervalModal($agentId)
    {
        $this->selectedAgent = $agentId;
        $this->filter = 'custom';
        $this->isShowTransaction = true;
        $this->dispatch('openModal', name: 'accountModal');
    }

    public function exportPDF()
    {
        $query = Transaction::where('user_id', $this->user_id);
        $query = $this->applyDateFilter($query);

        if ($this->filter === 'year') {
            return Excel::download(
                new AgentTransactionsExport($query->orderByDesc('created_at')),
                'transactions_' . $this->user_id . '.xlsx'
            );
        }

        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $totalByCurrency = (clone $query)
            ->selectRaw('currency, sum(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');

        $transactionCount = $query->count();
        $transactions = $query->orderByDesc('created_at')->get();
        $agentInfo = User::find($this->user_id);

        $agentAccounts = User::where('id', $this->user_id)
            ->with(['agentAccounts' => fn($q) => $q->orderBy('currency')])
            ->get();

        $pdf = Pdf::loadView('pdf.agent-transactions', [
            'user' => $agentInfo,
            'agentAccounts' => $agentAccounts,
            'transactions' => $transactions,
            'filter' => $this->filter,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'totalByCurrency' => $totalByCurrency,
            'transactionCount' => $transactionCount,
            'periodLabel' => $this->periodLabel
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'transactions_' . $this->user_id . '.pdf');
    }
}
