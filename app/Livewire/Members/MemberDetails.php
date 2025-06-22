<?php

namespace App\Livewire\Members;

use App\Models\Account;
use App\Models\AgentAccount;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;


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
        $this->memberId = $id;
    }

    //Make Deposit to customer Account
    public function submit()
    {
        Gate::authorize('depotMembers', User::class);

        $this->validate();

        DB::beginTransaction();
        try {
            $user = User::findOrFail($this->memberId);

            // Récupération ou création du compte du membre
            $account = Account::firstOrCreate(
                ['user_id' => $user->id, 'currency' => $this->currency],
                ['balance' => 0]
            );

            // Récupération de la caisse de l'agent
            $agentAccount = AgentAccount::firstOrCreate(
                ['user_id' => Auth::id(), 'currency' => $this->currency],
                ['balance' => 0]
            );

            // Mise à jour des soldes
            $account->balance += $this->amount;
            $agentAccount->balance += $this->amount;

            $account->save();
            $agentAccount->save();

            // Création de la transaction
            $transaction = Transaction::create([
                'account_id'     => $account->id,
                'user_id'        => Auth::id(),
                'type'           => 'dépôt',
                'currency'       => $this->currency,
                'amount'         => $this->amount,
                'balance_after'  => $account->balance,
                'description'    => $this->description ?: "DEPOT du compte " . $user->code . " Client: " . $user->name . " " . $user->postnom . " par " . Auth::user()->name,
            ]);

            // Finalisation de la transaction
            DB::commit();

            $this->reset(['amount', 'description']);
            $this->dispatch('closeModal', name: 'modalDepositMembre');
            $this->dispatch('$refresh');
            notyf()->success('Dépôt effectué avec succès !');
            $this->dispatch('facture-validee', url: route('receipt.generate', ['id' => $transaction->id]));

        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);
            notyf()->error('Une erreur est survenue lors du dépôt. Veuillez réessayer plus tard.');
        }
    }

    public function submitRetrait()
    {
        Gate::authorize('depotMembers', User::class);

        $this->validate();

        DB::beginTransaction();
        try {
            $user = User::findOrFail($this->memberId);

            // Récupération ou création du compte du membre
            $account = Account::firstOrCreate(
                ['user_id' => $user->id, 'currency' => $this->currency],
                ['balance' => 0]
            );

            if ($account->balance < $this->amount) {
                DB::rollBack();
                notyf()->error('Le solde du compte est insuffisant.');
                return;
            }

            // Récupération de la caisse de l'agent
            $agentAccount = AgentAccount::firstOrCreate(
                ['user_id' => Auth::id(), 'currency' => $this->currency],
                ['balance' => 0]
            );

            if ($agentAccount->balance < $this->amount) {
                DB::rollBack();
                notyf()->error('Le solde de la caisse est insuffisant.');
                return;
            }

            // Débit du compte du membre
            $account->balance -= $this->amount;
            $account->save();

            // Débit du compte de l'agent
            $agentAccount->balance -= $this->amount;
            $agentAccount->save();

            // Création de la transaction
            $transaction = Transaction::create([
                'account_id' => $account->id,
                'user_id' => Auth::id(),
                'type' => 'retrait',
                'currency' => $this->currency,
                'amount' => $this->amount,
                'balance_after' => $account->balance,
                'description' => $this->description ?: "RETRAIT du compte " . $user->code . " Client: " . $user->name . " " . $user->postnom . " par " . Auth::user()->name,
            ]);

            DB::commit();

            $this->reset(['amount', 'description']);
            $this->dispatch('closeModal', name: 'modalRetraitMembre');
            $this->dispatch('$refresh');
            notyf()->success('Retrait effectué avec succès !');
            $this->dispatch('facture-validee', url: route('receipt.generate', ['id' => $transaction->id]));

        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);
            notyf()->error('Une erreur est survenue lors du retrait. Veuillez réessayer plus tard.');
        }
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
            })->where('user_id', $this->memberId)
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.members.member-details', compact('member', 'transactions'));
    }

}
