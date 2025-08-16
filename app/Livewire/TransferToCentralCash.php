<?php

namespace App\Livewire;

// app/Http/Livewire/TransferToCentralCash.php

use App\Helpers\UserLogHelper;
use Livewire\Component;
use App\Models\AgentAccount;
use App\Models\MainCashRegister;
use App\Models\Transaction;
use App\Models\Transfert;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TransferToCentralCash extends Component
{
    public $currency;
    public $amount = 0;
    public $currencies = ['USD', 'CDF'];

    protected $rules = [
        'currency' => 'required|in:USD,CDF',
        'amount' => 'required|numeric|min:0.01',
    ];

    public function mount()
    {
        Gate::authorize('ajouter-transfert-caisse', User::class);
    }

    public function submit()
    {
        $this->validate();

        // Récupérer la caisse de l'agent
        $agentAccount = AgentAccount::firstOrCreate(
            ['user_id' => Auth::id(), 'currency' => $this->currency],
            ['balance' => 0]
        );

        if ($agentAccount->balance < $this->amount) {
            $this->addError('amount', "Solde insuffisant dans votre caisse.");
            return;
        }

        // Récupérer ou créer la caisse centrale
        $mainCash = MainCashRegister::firstOrCreate(
            ['currency' => $this->currency],
            ['balance' => 0]
        );

        // Mise à jour des soldes
        $agentAccount->balance -= $this->amount;
        $mainCash->balance += $this->amount;

        $agentAccount->save();
        $mainCash->save();

        // Enregistrer le virement
        $transfer = Transfert::create([
            'from_agent_account_id' => $agentAccount->id,
            'to_main_cash_register_id' => $mainCash->id,
            'currency' => $this->currency,
            'amount' => $this->amount,
        ]);

        // Enregistrer la transaction pour l'agent
        Transaction::create([
            'agent_account_id' => $agentAccount->id,
            'user_id' => Auth::id(),
            'type' => 'virement vers caisse centrale',
            'currency' => $this->currency,
            'amount' => $this->amount,
            'balance_after' => $agentAccount->balance,
            'description' => "Virement de ".$this->amount." ".$this->currency." du compte de ".Auth::user()->name." vers la caisse centrale. #REF".$transfer->id,
        ]);

        UserLogHelper::log_user_activity(
            action: 'virement_caisse_centrale',
            description: "Virement de {$this->amount} {$this->currency} du compte de ".Auth::user()->name." vers la caisse centrale. #REF{$transfer->id}"
        );

        notyf()->success( 'Virement effectué avec succès !');

        $this->reset(['amount']);
        $this->redirect(route('transfer.receipt.generate', ['id' => $transfer->id]), navigate: false);

    }

    public function placeholder()
    {
        return view('livewire.placeholder');
    }


    public function render()
    {
        $agentAccounts = AgentAccount::where('user_id', Auth::id())->get();
        return view('livewire.transfer-to-central-cash', compact('agentAccounts'));
    }
}
