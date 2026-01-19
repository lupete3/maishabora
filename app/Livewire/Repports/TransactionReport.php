<?php

namespace App\Livewire\Repports;

use App\Models\Transaction;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DepositsExport;
use App\Exports\WithdrawalsExport;

class TransactionReport extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $filterType = 'today';
    public $startDate;
    public $endDate;
    public $currency = '';
    public $user_id = '';
    public $results = [];
    public $search;

    public function updatedFilterType()
    {
        if ($this->filterType !== 'custom') {
            $this->startDate = null;
            $this->endDate = null;
        }
    }

    public function getFilteredQuery()
    {
        $query = Transaction::with('user')
            ->whereHas('user', fn($q) => $q->where('role', 'membre'))
            ->whereIn('type', ['dépôt', 'mise_quotidienne', 'retrait', 'retrait_carte_adhesion']);

        // Filtres temporels
        switch ($this->filterType) {
            case 'today':
                $query->whereDate('created_at', Carbon::today());
                break;
            case 'week':
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month);
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
            case 'custom':
                if ($this->startDate && $this->endDate) {
                    $query->whereBetween('created_at', [$this->startDate, $this->endDate]);
                }
                break;
        }

        // Filtre devise
        if ($this->currency !== '') {
            $query->where('currency', $this->currency);
        }

        // Filtre utilisateur
        if ($this->user_id !== '') {
            $query->where('user_id', $this->user_id);
        }

        return $query;
    }

    public function updatedSearch()
    {
        $query = trim($this->search);
        if ($query !== '') {
            $this->results = User::query()
                ->where(function ($q) use ($query) {
                    $q->where('role', 'membre')
                        ->where('code', 'like', "%{$query}%")
                        ->orWhere('name', 'like', "%{$query}%")
                        ->orWhere('postnom', 'like', "%{$query}%")
                        ->orWhere('prenom', 'like', "%{$query}%")
                        ->orWhere('telephone', 'like', "%{$query}%");
                })
                ->limit(10)
                ->get(['id', 'code', 'name', 'postnom', 'prenom'])
                ->toArray();
        } else {
            $this->results = [];
        }
    }

    public function selectResult(int $id)
    {
        $user = User::find($id);
        if ($user) {
            $this->search = "{$user->name} {$user->postnom} {$user->prenom}";
            $this->results = [];

            $this->user_id = $user->id;
            $this->dispatch('userSelected', $user->id);
        }
    }

    public function render()
    {
        $query = $this->getFilteredQuery();

        // Calcul des dépôts (dépôt + mise_quotidienne)
        $deposits = (clone $query)->whereIn('type', ['dépôt', 'mise_quotidienne'])->sum('amount');

        // Calcul des retraits (retrait + retrait_carte_adhesion)
        $withdrawals = (clone $query)->whereIn('type', ['retrait', 'retrait_carte_adhesion'])->sum('amount');

        // Récupération des transactions paginées
        $transactions = $query->latest()->paginate(10);

        $members = User::where('role', 'membre')->orderBy('name', 'ASC')->get();

        return view('livewire.repports.transaction-report', [
            'deposits' => $deposits,
            'withdrawals' => $withdrawals,
            'transactions' => $transactions,
            'members' => $members,
        ]);
    }

    // Export PDF des dépôts
    public function exportDepositsPdf()
    {
        $query = $this->getFilteredQuery();
        $depositsData = (clone $query)->whereIn('type', ['dépôt', 'mise_quotidienne'])->latest()->get();
        $total = $depositsData->sum('amount');

        $pdf = Pdf::loadView('pdf.deposits-report', [
            'deposits' => $depositsData,
            'total' => $total,
            'filterType' => $this->filterType,
            'currency' => $this->currency,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ])->setPaper('A4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, "rapport_depots_" . now()->format('Ymd_His') . ".pdf");
    }

    // Export Excel des dépôts
    public function exportDepositsExcel()
    {
        $query = $this->getFilteredQuery();
        $depositsData = (clone $query)->whereIn('type', ['dépôt', 'mise_quotidienne'])->latest()->get();
        $total = $depositsData->sum('amount');

        return Excel::download(
            new DepositsExport($depositsData, $total),
            'rapport_depots_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    // Export PDF des retraits
    public function exportWithdrawalsPdf()
    {
        $query = $this->getFilteredQuery();
        $withdrawalsData = (clone $query)->whereIn('type', ['retrait', 'retrait_carte_adhesion'])->latest()->get();
        $total = $withdrawalsData->sum('amount');

        $pdf = Pdf::loadView('pdf.withdrawals-report', [
            'withdrawals' => $withdrawalsData,
            'total' => $total,
            'filterType' => $this->filterType,
            'currency' => $this->currency,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ])->setPaper('A4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, "rapport_retraits_" . now()->format('Ymd_His') . ".pdf");
    }

    // Export Excel des retraits
    public function exportWithdrawalsExcel()
    {
        $query = $this->getFilteredQuery();
        $withdrawalsData = (clone $query)->whereIn('type', ['retrait', 'retrait_carte_adhesion'])->latest()->get();
        $total = $withdrawalsData->sum('amount');

        return Excel::download(
            new WithdrawalsExport($withdrawalsData, $total),
            'rapport_retraits_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}