<?php

namespace App\Livewire\Agent;


use Livewire\Component;
use App\Models\AgentAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AgentDashboard extends Component
{
    public $today;
    public $user_id;
    public $isShowTransaction = false;
    public $transactions = [];

    public function mount()
    {
        $user = Auth::user();
    }

    protected function applyDateFilter($query, $filter)
    {
        $now = now();

        return match ($filter) {
            'day' => $query->whereDate('created_at', $now->toDateString()),
            'month' => $query->whereMonth('created_at', $now->month)
                        ->whereYear('created_at', $now->year),
            'year' => $query->whereYear('created_at', $now->year),
            default => $query,
        };
    }

    public function showTransactions($userId, $filter = 'day')
    {
        $this->user_id = $userId;
        $this->isShowTransaction = true;

        $query = Transaction::where('user_id', $this->user_id);
        $query = $this->applyDateFilter($query, $filter);

        $this->transactions = $query->orderByDesc('created_at')->get();
    }

    public function placeholder()
    {
        return view('livewire.placeholder');
    }

    public function render()
    {
        $user = Auth::user();

        if ($user->can('afficher-caisse-agent')) {
            // Récupère tous les utilisateurs ayant des comptes liés
            $agentAccounts = User::whereHas('agentAccounts')
                ->with(['agentAccounts' => function ($query) {
                    $query->orderBy('currency');
                }])
                ->get();
        } else {
            // Récupère uniquement le recouvreur connecté avec ses comptes
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
}
