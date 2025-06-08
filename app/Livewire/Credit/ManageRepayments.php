<?php

namespace App\Livewire\Credit;

use Livewire\Component;
use App\Models\User;
use App\Models\Credit;
use App\Models\Repayment;
use App\Models\Account;
use App\Models\AgentAccount;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class ManageRepayments extends Component
{
    public $member_id;
    public $credit_id;
    public $selectedCredit = null;

    public $members = [];
    public $credits = [];
    public string $search = '';
    public array $results = [];

    protected $rules = [
        'member_id' => 'required|exists:users,id',
        'credit_id' => 'required|exists:credits,id',
    ];

    public function mount()
    {
        $user = Auth::user();
        if (!$user->isCaissier() && !$user->isAdmin()) {
            return redirect(route('dashboard'));
        }
        $this->members = User::where('role', 'membre')->get();
    }
    
    public function updatedSearch()
    {
        $query = trim($this->search);
        if ($query !== '') {
            $this->results = User::query()
                ->where(function($q) use ($query) {
                    $q->where('code', 'like', "%{$query}%")
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
            $this->search = "{$user->name} {$user->postnom}";
            $this->results = [];
            $this->reset(['credit_id', 'selectedCredit']);

            $this->credits = Credit::where('user_id', $user->id)
                ->where('is_paid', false)
                ->with('repayments')
                ->get();
            $this->dispatch('userSelected', $user->id);
        }
    }

    public function updatedCreditId()
    {
        if ($this->credit_id) {
            $this->selectedCredit = Credit::with('repayments')->find($this->credit_id);
        }
    }

    public function payRepayment($repaymentId)
    {        
        $repayment = Repayment::findOrFail($repaymentId);

        if ($repayment->is_paid) return;

        $credit = $repayment->credit;
        $member = $credit->user;

        // Récupérer le compte du membre
        $account = Account::firstOrCreate([
            'user_id' => $member->id,
            'currency' => $credit->currency
        ], ['balance' => 0]);

        if ($account->balance < $repayment->total_due) {
            notyf()->error(message: __('Solde insuffisant pour effectuer ce remboursement.'));
            return;
        }

        // Mise à jour des soldes
        $account->balance -= $repayment->total_due;
        $account->save();

        // Marquer comme payé
        $repayment->paid_date = now();
        $repayment->paid_amount = $repayment->total_due;
        $repayment->is_paid = true;
        $repayment->save();

        // Vérifier si tout est remboursé
        if (!$repayment->credit->repayments->where('is_paid', false)->count()) {
            $repayment->credit->is_paid = true;
            $repayment->credit->save();
        }

        // Récupérer la caisse de l'agent
        $agentAccount = AgentAccount::firstOrCreate(
            ['user_id' => Auth::id(), 'currency' => $credit->currency],
            ['balance' => 0]
        );

        // Mise à jour des soldes
        $agentAccount->balance += $repayment->total_due;
        $agentAccount->save();

        // Enregistrer la transaction
        Transaction::create([
            'account_id' => $account->id,
            'user_id' => Auth::user()->id,
            'type' => 'remboursement_de_credit',
            'currency' => $credit->currency,
            'amount' => $repayment->total_due,
            'balance_after' => $account->balance,
            'description' => "Remboursement échéance n°{$repayment->id} pour le crédit ID {$credit->id}",
        ]);

        notyf()->success(message: __('Échéance remboursée avec succès !'));

        $this->updatedCreditId(); // Rafraîchir
    }

    public function render()
    {
        return view('livewire.credit.manage-repayments');
    }
}
