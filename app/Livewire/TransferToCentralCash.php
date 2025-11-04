<?php

namespace App\Livewire;

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
    public $currency = '';
    public $amount = '';
    public $currencies = ['USD', 'CDF'];

    // ✅ Nouveau : contrôle d’affichage du modal
    public $showConfirmation = false;

    protected $rules = [
        'currency' => 'required|in:USD,CDF',
        'amount' => 'required|numeric|min:0.01',
    ];

    protected $messages = [
        'currency.required' => 'Veuillez choisir une devise.',
        'currency.in' => 'Devise invalide.',
        'amount.required' => 'Le montant est obligatoire.',
        'amount.numeric' => 'Le montant doit être un nombre.',
        'amount.min' => 'Le montant doit être supérieur à 0.',
    ];

    public function mount()
    {
        Gate::authorize('ajouter-transfert-caisse', User::class);
    }

    public function updated($property)
    {
        $this->validateOnly($property);
    }

    public function submit()
    {
        $this->validate();

        $agentAccount = AgentAccount::where('user_id', Auth::id())
            ->where('currency', $this->currency)
            ->first();

        if ($agentAccount && $agentAccount->balance < $this->amount) {
            $this->addError('amount', "Solde insuffisant dans votre caisse.");
            return;
        }

        // ✅ Active le modal de prévisualisation
        $this->showConfirmation = true;
    }

    public function confirmSubmit()
    {
        $this->validate();

        $agentAccount = AgentAccount::firstOrCreate(
            ['user_id' => Auth::id(), 'currency' => $this->currency],
            ['balance' => 0]
        );

        if ($agentAccount->balance < $this->amount) {
            $this->addError('amount', "Solde insuffisant dans votre caisse.");
            return;
        }

        $mainCash = MainCashRegister::firstOrCreate(
            ['currency' => $this->currency],
            ['balance' => 0]
        );

        // ✅ Mise à jour des soldes
        $agentAccount->decrement('balance', $this->amount);
        $mainCash->increment('balance', $this->amount);

        // ✅ Enregistrement du transfert
        $transfer = Transfert::create([
            'from_agent_account_id' => $agentAccount->id,
            'to_main_cash_register_id' => $mainCash->id,
            'currency' => $this->currency,
            'amount' => $this->amount,
        ]);

        Transaction::create([
            'agent_account_id' => $agentAccount->id,
            'user_id' => Auth::id(),
            'type' => 'virement vers caisse centrale',
            'currency' => $this->currency,
            'amount' => $this->amount,
            'balance_after' => $agentAccount->balance,
            'description' => "Virement de {$this->amount} {$this->currency} du compte de " . Auth::user()->name . " vers la caisse centrale. #REF{$transfer->id}",
        ]);

        UserLogHelper::log_user_activity(
            action: 'virement_caisse_centrale',
            description: "Virement de {$this->amount} {$this->currency} du compte de " . Auth::user()->name . " vers la caisse centrale. #REF{$transfer->id}"
        );

        notyf()->success('Virement effectué avec succès !');

        // ✅ Ferme le modal et réinitialise
        $this->reset(['amount', 'currency', 'showConfirmation']);

        $this->redirect(route('transfer.receipt.generate', ['id' => $transfer->id]), navigate: false);
    }

    public function render()
    {
        $agentAccounts = AgentAccount::where('user_id', Auth::id())->get();
        return view('livewire.transfer-to-central-cash', compact('agentAccounts'));
    }
}
