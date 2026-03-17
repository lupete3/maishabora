<?php

namespace App\Livewire\Agent;

use App\Exports\AgentTransactionsExport;
use Livewire\Component;
use App\Models\AgentAccount;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class AgentDashboard extends Component
{
    public $today;
    public $user_id;
    public $isShowTransaction = false;
    /** @var \Illuminate\Support\Collection|array */
    public $transactions;
    public $transactionCount;
    public $totalByCurrency;
    public $selectedAgent;
    public $showConfirmModal = false;
    public $filter = 'day';

    protected $queryString = ['filter'];

    public $startDate;
    public $endDate;
    public $periodLabel;

    // Propriétés pour la modification du solde
    public $editingAccountId;
    public $newBalance;
    public $modificationReason;
    public $openModifyBalance = false;

    // Propriétés pour la suppression de transaction
    public $transactionToDeleteId;
    public $openDeleteTransaction = false;
    public $deleteReason;

    // Propriétés pour la modification de transaction
    public $editingTransactionId;
    public $editAmount;
    public $editDescription;
    public $editBalanceAfter;
    public $openEditTransaction = false;


    public function mount()
    {
        // Initialization if needed
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
                    $end = Carbon::parse($this->endDate)->endOfDay();
                    $this->periodLabel = "Du " . $start->format('d/m/Y') . " au " . $end->format('d/m/Y');
                    return $query->whereBetween('created_at', [$start, $end]);
                }
                return $query;

            case 'day':
                $this->periodLabel = "Aujourd'hui";
                return $query->whereDate('created_at', $now);

            case 'week':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
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
                ->with([
                    'agentAccounts' => function ($query) {
                        $query->orderBy('currency');
                    }
                ])
                ->get();
        } else {
            $agentAccounts = User::where('id', $user->id)
                ->with([
                    'agentAccounts' => function ($query) {
                        $query->orderBy('currency');
                    }
                ])
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

    public function confirmUpdateBalance($accountId)
    {
        Gate::authorize('modifier-solde-compte', User::class);
        $account = \App\Models\AgentAccount::findOrFail($accountId);
        $this->editingAccountId = $accountId;
        $this->newBalance = $account->balance;
        $this->modificationReason = '';
        $this->openModifyBalance = true;
    }

    public function updateBalance()
    {
        Gate::authorize('modifier-solde-compte', User::class);

        $this->validate([
            'newBalance' => 'required|numeric|min:0',
            'modificationReason' => 'required|string|min:5',
        ]);

        DB::beginTransaction();
        try {
            $account = \App\Models\AgentAccount::findOrFail($this->editingAccountId);
            $oldBalance = $account->balance;
            $account->balance = $this->newBalance;
            $account->save();

            // Créer une transaction de rectification pour l'agent
            Transaction::create([
                'user_id' => $account->user_id,
                'type' => 'rectification_solde_agent',
                'currency' => $account->currency,
                'amount' => abs($this->newBalance - $oldBalance),
                'balance_after' => $account->balance,
                'description' => "RECTIFICATION MANUELLE DU SOLDE AGENT: " . ($this->newBalance >= $oldBalance ? "+" : "-") . " " . abs($this->newBalance - $oldBalance) . " " . $account->currency . ". Raison: " . $this->modificationReason
            ]);

            \App\Helpers\UserLogHelper::log_user_activity(
                "Modification_solde_agent",
                "Modification manuelle du solde agent {$account->currency} de {$account->user->name}. Ancien: {$oldBalance}, Nouveau: {$this->newBalance}. Raison: {$this->modificationReason}"
            );

            DB::commit();
            $this->openModifyBalance = false;
            $this->dispatch('notyf', type: 'success', message: 'Solde agent mis à jour avec succès !');

            // Recharger les transactions si visibles
            if ($this->isShowTransaction && $this->user_id == $account->user_id) {
                $this->showTransactions($this->user_id, $this->filter, $this->startDate, $this->endDate);
            }
        } catch (Throwable $th) {
            DB::rollBack();
            report($th);
            $this->dispatch('notyf', type: 'error', message: 'Erreur lors de la mise à jour du solde.');
        }
    }

    public function closeModifyBalanceModal()
    {
        $this->openModifyBalance = false;
    }

    public function confirmDeleteTransaction($transactionId)
    {
        Gate::authorize('modifier-solde-compte', User::class);
        $this->transactionToDeleteId = $transactionId;
        $this->deleteReason = '';
        $this->openDeleteTransaction = true;
    }

    public function deleteTransaction()
    {
        Gate::authorize('modifier-solde-compte', User::class);

        $this->validate([
            'deleteReason' => 'required|string|min:5',
        ]);

        $transaction = Transaction::findOrFail($this->transactionToDeleteId);
        $agentAccount = AgentAccount::where('id', $transaction->agent_account_id)->first();

        if (!$agentAccount) {
            $this->dispatch('notyf', type: 'error', message: 'Cette transaction n\'appartient pas à un compte agent.');
            return;
        }

        DB::beginTransaction();
        try {
            $type = $transaction->type;
            $amount = $transaction->amount;
            $currency = $transaction->currency;

            // 3. LOG AND DELETE
            \App\Helpers\UserLogHelper::log_user_activity(
                "Suppression_transaction",
                "Suppression de la transaction #{$transaction->id} ({$type}) de l'agent {$agentAccount->user->name}. Montant: {$amount} {$currency}. Raison: {$this->deleteReason}"
            );

            $transaction->delete();

            DB::commit();
            $this->openDeleteTransaction = false;
            $this->dispatch('notyf', type: 'success', message: 'Transaction supprimée et soldes ajustés !');

            // Refresh transactions
            $this->showTransactions($this->user_id, $this->filter, $this->startDate, $this->endDate);

        } catch (Throwable $th) {
            DB::rollBack();
            report($th);
            $this->dispatch('notyf', type: 'error', message: 'Erreur lors de la suppression : ' . $th->getMessage());
        }
    }

    public function closeDeleteModal()
    {
        $this->openDeleteTransaction = false;
    }

    public function confirmEditTransaction($transactionId)
    {
        Gate::authorize('modifier-solde-compte', User::class);
        $transaction = Transaction::findOrFail($transactionId);

        $this->editingTransactionId = $transactionId;
        $this->editAmount = $transaction->amount;
        $this->editDescription = $transaction->description;
        $this->editBalanceAfter = $transaction->balance_after;
        $this->openEditTransaction = true;
    }

    public function updateTransaction()
    {
        Gate::authorize('modifier-solde-compte', User::class);

        $this->validate([
            'editAmount' => 'required|numeric|min:0.01',
            'editDescription' => 'required|string|min:5',
            'editBalanceAfter' => 'required|numeric',
        ]);

        $transaction = Transaction::findOrFail($this->editingTransactionId);
        $agentAccount = AgentAccount::find($transaction->agent_account_id);

        if (!$agentAccount) {
            $this->dispatch('notyf', type: 'error', message: 'Cette transaction n\'appartient pas à un compte agent.');
            return;
        }

        DB::beginTransaction();
        try {
            $oldAmount = (float) $transaction->amount;
            $newAmount = (float) $this->editAmount;
            $diff = $newAmount - $oldAmount;

            // Update transaction
            $transaction->update([
                'amount' => $newAmount,
                'description' => $this->editDescription,
                'balance_after' => $this->editBalanceAfter
            ]);

            \App\Helpers\UserLogHelper::log_user_activity(
                "Modification_transaction_agent",
                "Modification de la transaction #{$transaction->id} ({$transaction->type}). Ancien montant: {$oldAmount}, Nouveau: {$newAmount}, Nouveau Solde Après: {$this->editBalanceAfter}. Raison: {$this->editDescription}"
            );

            DB::commit();
            $this->openEditTransaction = false;
            $this->dispatch('notyf', type: 'success', message: 'Transaction mise à jour et solde ajusté !');

            // Refresh transactions
            $this->showTransactions($this->user_id, $this->filter, $this->startDate, $this->endDate);

        } catch (Throwable $th) {
            DB::rollBack();
            report($th);
            $this->dispatch('notyf', type: 'error', message: 'Erreur lors de la modification : ' . $th->getMessage());
        }
    }

    public function closeEditModal()
    {
        $this->openEditTransaction = false;
    }
}
