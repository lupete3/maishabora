<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Models\Account;
use App\Models\AgentAccount;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class DepositForMember extends Component
{
    public $member_id;
    public $currency;
    public $amount = 0;
    public $description = '';
    public $members = [];

    protected $rules = [
        'member_id' => 'required|exists:users,id',
        'currency' => 'required|in:USD,CDF',
        'amount' => 'required|numeric|min:0.01',
    ];

    public function mount()
    {
        // Vérifier que seul un agent de terrain peut accéder
        $user = Auth::user();
        if (!$user->isRecouvreur() && !$user->isAdmin()) {
            abort(403, 'Accès interdit');
        }

        // Charger les membres enregistrés par cet agent
        $this->members = User::where('role', 'membre')->get();
    }

    public function submit()
    {
        $this->validate();

        $user = User::find($this->member_id);

        // Récupérer ou créer le compte du membre
        $account = Account::firstOrCreate(
            ['user_id' => $user->id, 'currency' => $this->currency],
            ['balance' => 0]
        );

        // Récupérer la caisse de l'agent
        $agentAccount = AgentAccount::firstOrCreate(
            ['user_id' => Auth::id(), 'currency' => $this->currency],
            ['balance' => 0]
        );

        // Mise à jour des soldes
        $account->balance += $this->amount;
        $agentAccount->balance += $this->amount;

        $account->save();
        $agentAccount->save();

        // Enregistrer la transaction pour le compte du membre
        Transaction::create([
            'account_id' => $account->id,
            'user_id' =>Auth::id(),
            'type' => 'dépôt',
            'currency' => $this->currency,
            'amount' => $this->amount,
            'balance_after' => $account->balance,
            'description' => $this->description ?: "Dépôt effectué par " . Auth::user()->name,
        ]);

        notyf()->success( 'Dépôt effectué avec succès !');

        $this->reset(['amount', 'description']);
    }

    public function render()
    {

        return view('livewire.admin.deposit-for-member', [
            'members' => $this->members,
        ]);
    }
}
