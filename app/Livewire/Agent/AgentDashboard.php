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
        if (!$user->isCaissier() && !$user->isAdmin()) {
            return redirect(route('dashboard'));
        }

    }

    public function showTransactions($userId, $filter = 'day')
    {
        $this->user_id = $userId;
        $this->isShowTransaction = true;

        $query = Transaction::where('user_id', $this->user_id);

        if ($filter === 'day') {
            $query->whereDate('created_at', now()->toDateString());
        } elseif ($filter === 'month') {
            $query->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
        } elseif ($filter === 'year') {
            $query->whereYear('created_at', now()->year);
        }

        $this->transactions = $query->orderByDesc('created_at')->get();
    }

    public function placeholder()
    {
        return view('livewire.placeholder');
    }

    public function render()
    {
        $agentAccounts = User::whereHas('agentAccounts')
            ->with(['agentAccounts' => function ($query) {
                $query->orderBy('currency'); // Facultatif, juste pour l'ordre
            }])
            ->get();

        return view('livewire.agent.agent-dashboard', ['agentAccounts' => $agentAccounts]);
    }
}
