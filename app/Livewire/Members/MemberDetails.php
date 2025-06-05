<?php

namespace App\Livewire\Members;

use App\Models\Account;
use App\Models\AgentAccount;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class MemberDetails extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $memberId;
    public $search = '';
    public $perPage = 10;

    public $currency;
    public $amount = 0;
    public $description = '';

    protected $rules = [
        'memberId' => 'required|exists:users,id',
        'currency' => 'required|in:USD,CDF',
        'amount' => 'required|numeric|min:0.01',
    ];


    public function mount($id)
    {
        $user = Auth::user();
        if (!$user->isRecouvreur() && !$user->isAdmin()) {
            abort(403, 'Accès interdit');
        }

        $this->memberId = $id;
    }

    //Make Deposit to customer Account
    public function submit()
    {
        $this->validate();

        $user = User::find($this->memberId);

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
        $transaction = Transaction::create([
            'account_id' => $account->id,
            'user_id' =>Auth::id(),
            'type' => 'dépôt',
            'currency' => $this->currency,
            'amount' => $this->amount,
            'balance_after' => $account->balance,
            'description' => $this->description ?: "DEPOT du compte " .$user->code.
                " Client: ".$user->name." ".$user->postnom." par " . Auth::user()->name,
        ]);

        $this->reset(['amount', 'description']);
        $this->dispatch('closeModal', name: 'modalDepositMembre');
        $this->dispatch('$refresh');
        notyf()->success( 'Dépôt effectué avec succès !');
        // Redirection vers le reçu
        $this->dispatch('facture-validee', url: route('receipt.generate', ['id' => $transaction->id]));
    }

    public function submitRetrait()
    {
        $this->validate();

        $user = User::find($this->memberId);

        // Récupérer ou créer le compte du membre
        $account = Account::firstOrCreate(
            ['user_id' => $user->id, 'currency' => $this->currency],
            ['balance' => 0]
        );

        if ($account->balance < $this->amount) {
            notyf()->error( 'Le solde est insuffisant.');
            return;
        }

        // Retirer le montant
        $account->balance -= $this->amount;
        $account->save();

        // Enregistrer la transaction
        $transaction = Transaction::create([
            'account_id' => $account->id,
            'user_id' => Auth::id(),
            'type' => 'retrait',
            'currency' => $this->currency,
            'amount' => $this->amount,
            'balance_after' => $account->balance,
            'description' => $this->description ?: "RETRAIT du compte " .$user->code.
                " Client: ".$user->name." ".$user->postnom." par " . Auth::user()->name,
        ]);

        $this->dispatch('closeModal', name: 'modalRetraitMembre');
        notyf()->success( 'Retrait effectué avec succès !');
        $this->dispatch('$refresh');
        $this->reset(['amount', 'description']);

        $this->dispatch('facture-validee', url: route('receipt.generate', ['id' => $transaction->id]));

    }

    public function closeDepositModal()
    {
        $this->dispatch('closeModal', name: 'modalDepositMembre');
    }

    public function closeRetraitModal()
    {
        $this->dispatch('closeModal', name: 'modalRetraitMembre');
    }

    public function openDepositModal()
    {
        $this->dispatch('openModal', name: 'modalDepositMembre');
    }
    public function openRetraitModal()
    {
        $this->dispatch('openModal', name: 'modalRetraitMembre');
    }

    public function placeholder()
    {
        return view('livewire.placeholder');
    }

    public function render()
    {
        $member = User::findOrFail($this->memberId);

        $accountIds = $member->accounts->pluck('id')->toArray();

        $transactions = Transaction::whereIn('account_id', $accountIds)
            ->when($this->search, function ($query) {
                $searchTerm = "%{$this->search}%";
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('type', 'like', $searchTerm)
                    ->orWhere('currency', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.members.member-details', compact('member', 'transactions'));
    }

}
