<?php

namespace App\Livewire\Members;

use App\Helpers\UserLogHelper;
use App\Models\Account;
use App\Models\AgentAccount;
use App\Models\MembershipCard;
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
    public $description = '';

    public $card_id;
    public $cards = [];
    public $allCards = [];
    public $selectedCard;
    public $contribution_date;
    public $amount = 0;
    public $a_retenir = 0;
    public $operation_type;

    public $type;

    public $cardDetail = [];

    public $openConfirmDepositNormal = false;
    public $openConfirmRetraitNormal = false;

    public function mount($id)
    {
        Gate::authorize('afficher-client', User::class);

        $this->memberId = $id;

        $this->cards = MembershipCard::where('member_id', $this->memberId)
            ->where('is_active', true)
            ->with(['contributions'])
            ->get();

        $this->allCards = MembershipCard::where('member_id', $this->memberId)
            ->with(['contributions'])
            ->latest()->get();
    }

    public function showConfirmDepositNormal()
    {
        $this->openConfirmDepositNormal = true;
    }

    public function closeDepositConfirmationModal()
    {
        $this->openConfirmDepositNormal = false;
    }

    public function makeDeposit()
    {
        $this->openConfirmDepositNormal = false;
        if ($this->operation_type == 'normal') {
            $this->submit();
            return;
        }elseif ($this->operation_type == 'carte') {
            $this->contribute();
            return;
        }
    }

    public function showConfirmRetraitNormal()
    {
        if ($this->operation_type == 'carte') {
            $this->cardDetail = MembershipCard::find($this->card_id);
        }
        $this->openConfirmRetraitNormal = true;
    }

    public function closeRetraitConfirmationModal()
    {
        $this->openConfirmRetraitNormal = false;
    }

    public function makeRetrait()
    {
        $this->openConfirmRetraitNormal = false;
        if ($this->operation_type == 'normal') {
            $this->submitRetrait();
            return;
        }elseif ($this->operation_type == 'carte') {
            $this->submitRetraitCarte();
            return;
        }
    }

    //Make Deposit to customer Account
    public function submit()
    {
        Gate::authorize('depot-compte-membre', User::class);

        $this->validate([
            'amount' => 'required|numeric|min:0.01',
            'memberId' => 'required|exists:users,id',
            'currency' => 'required|in:USD,CDF',
        ]);

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
                'account_id'     => null,
                'user_id'        => Auth::id(),
                'type'           => 'dépôt',
                'currency'       => $this->currency,
                'amount'         => $this->amount,
                'balance_after'  => $agentAccount->balance,
                'description'    => $this->description ?: "DEPOT du compte " . $user->code . " Client: " . $user->name . " " . $user->postnom . " par " . Auth::user()->name . " ". Auth::user()->postnom,
            ]);

            // Création de la transaction
            $transaction = Transaction::create([
                'account_id'     => $account->id,
                'user_id'        => $user->id,
                'type'           => 'dépôt',
                'currency'       => $this->currency,
                'amount'         => $this->amount,
                'balance_after'  => $account->balance,
                'description'    => $this->description ?: "DEPOT dans votre compte " . $user->code . " Client: " . $user->name . " " . $user->postnom . " par " . Auth::user()->name . " ". Auth::user()->postnom,
            ]);

            UserLogHelper::log_user_activity(
                action: 'dépôt',
                description: "Dépôt de {$this->amount} {$this->currency} sur le compte de {$user->name} {$user->postnom} ({$user->code})",
            );

            // Finalisation de la transaction
            DB::commit();

            $this->reset(['amount', 'description']);
            $this->dispatch('closeModal', name: 'modalDepositMembre');
            $this->dispatch('$refresh');
            notyf()->success('Dépôt effectué avec succès !');
            $this->resetInputFields();
            $this->dispatch('facture-validee', url: route('receipt.generate', ['id' => $transaction->id]));

        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);
            notyf()->error('Une erreur est survenue lors du dépôt. Veuillez réessayer plus tard.');
        }
    }

    public function updatedCardId()
    {
        $this->selectedCard = MembershipCard::find($this->card_id);
        $amount = $this->selectedCard;
        $this->amount = $amount->subscription_amount;
    }

    public function updatedType()
    {
        $this->operation_type = $this->type;
    }

    public function contribute()
    {
        Gate::authorize('depot-compte-membre', User::class);

        $this->validate([
            'card_id' => 'required|exists:membership_cards,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();
        try {
            $card = MembershipCard::findOrFail($this->card_id);

            // Montant d'une mise quotidienne
            $dailyAmount = $card->subscription_amount;

            // Nombre de mises à payer selon le montant entré
            $numberOfDaysToPay = floor($this->amount / $dailyAmount);

            if ($numberOfDaysToPay <= 0) {
                notyf()->error("Le montant doit être au moins égal à {$dailyAmount}.");
                return;
            }

            // Trouver les prochaines X contributions non payées
            $contributionsToPay = $card->contributions()
                ->where('is_paid', false)
                ->orderBy('contribution_date', 'asc')
                ->take($numberOfDaysToPay)
                ->get();

            if ($contributionsToPay->isEmpty()) {
                notyf()->error("Toutes les mises ont déjà été effectuées pour cette carte.");
                return;
            }

            if ($contributionsToPay->count() < $numberOfDaysToPay) {
                $remaining = $numberOfDaysToPay - $contributionsToPay->count();
                notyf()->warning("Seulement {$contributionsToPay->count()} mises restantes. Paiement partiel effectué.");
            }

            // Mettre à jour les mises sélectionnées
            foreach ($contributionsToPay as $contribution) {
                $contribution->is_paid = true;
                $contribution->save();
            }

            // Calculer le montant réel utilisé
            $totalPaid = $contributionsToPay->count() * $dailyAmount;

            // Si l'utilisateur a saisi plus que le reste dû, notifier et ajuster
            if ($this->amount > $totalPaid) {
                notyf()->info("Le montant saisi ({$this->amount}) dépasse le reste dû ({$totalPaid}). Montant ajusté automatiquement.");
            }

            // Créditer le compte du membre et de l'agent
            $account = Account::firstOrCreate(
                ['user_id' => $card->member_id, 'currency' => $card->currency],
                ['balance' => 0]
            );

            $agentAccount = AgentAccount::firstOrCreate(
                ['user_id' => Auth::id(), 'currency' => $card->currency],
                ['balance' => 0]
            );

            $agentAccount->balance += $totalPaid;
            $account->balance += $totalPaid;
            $agentAccount->save();
            $account->save();

            // Transaction agent
            Transaction::create([
                'account_id'     => null,
                'user_id'        => Auth::id(),
                'type'           => 'mise_quotidienne',
                'currency'       => $card->currency,
                'amount'         => $totalPaid, // ✅ montant réel
                'balance_after'  => $agentAccount->balance,
                'description'    => "Paiement groupé de {$contributionsToPay->count()} mises sur la carte #{$card->id}
                                    pour le client: {$card->member->name} {$card->member->postnom} par "
                                    . Auth::user()->name . " " . Auth::user()->postnom,
            ]);

            // Transaction membre
            $transaction = Transaction::create([
                'account_id'     => $account->id,
                'user_id'        => $card->member_id,
                'type'           => 'mise_quotidienne',
                'currency'       => $card->currency,
                'amount'         => $totalPaid, // ✅ montant réel
                'balance_after'  => $account->balance,
                'description'    => "Paiement groupé de {$contributionsToPay->count()} mises sur la carte #{$card->id}
                                    pour le client: {$card->member->name} {$card->member->postnom} par "
                                    . Auth::user()->name . " " . Auth::user()->postnom,
            ]);

            UserLogHelper::log_user_activity(
                action: 'mise_quotidienne',
                description: "Paiement de {$contributionsToPay->count()} mises pour la carte #{$card->id} du membre {$card->member->name} {$card->member->postnom} ({$card->member->code})",
            );

            DB::commit();

            $this->reset(['contribution_date', 'amount']);
            $this->dispatch('closeModal', name: 'modalDepositMembre');
            $this->dispatch('$refresh');
            notyf()->success("Paiement de {$contributionsToPay->count()} mise(s) effectué(s) avec succès !");
            $this->resetInputFields();
            $this->dispatch('facture-validee', url: route('receipt.generate', ['id' => $transaction->id]));

        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);
            notyf()->error('Une erreur est survenue lors du dépôt. Veuillez réessayer.');
        }
    }

    public function submitRetrait()
    {
        Gate::authorize('retrait-compte-membre', User::class);

        $this->validate([
            'amount' => 'required|numeric|min:0.01',
            'a_retenir' => 'required|numeric|min:0',
            'memberId' => 'required|exists:users,id',
            'currency' => 'required|in:USD,CDF',
        ]);

        DB::beginTransaction();
        try {
            $user = User::findOrFail($this->memberId);

            // Récupération ou création du compte du membre
            $account = Account::firstOrCreate(
                ['user_id' => $user->id, 'currency' => $this->currency],
                ['balance' => 0]
            );

            if ($account->balance < ($this->amount + $this->a_retenir)) {
                DB::rollBack();
                notyf()->error('Le solde du compte est insuffisant.');
                return;
            }

            // Récupération de la caisse de l'agent
            $agentAccount = AgentAccount::firstOrCreate(
                ['user_id' => Auth::id(), 'currency' => $this->currency],
                ['balance' => 0]
            );

            // Récupération de la caisse de l'agent
            $retenuMiseAccount = AgentAccount::firstOrCreate(
                ['user_id' => 195, 'currency' => $this->currency],
                ['balance' => 0]
            );

            if ($agentAccount->balance < $this->amount) {
                DB::rollBack();
                notyf()->error('Le solde de la caisse est insuffisant.');
                return;
            }

            // Débit du compte du membre
            $account->balance -= ($this->amount + $this->a_retenir);
            $agentAccount->balance -= $this->amount;
            if (
                $this->a_retenir > 0
            ) {
                $retenuMiseAccount->balance += $this->a_retenir;
                $retenuMiseAccount->save();
            }

            $account->save();
            $agentAccount->save();

            // Création de la transaction
            $transaction = Transaction::create([
                'account_id' => null,
                'user_id' => Auth::id(),
                'type' => 'retrait',
                'currency' => $this->currency,
                'amount' => $this->amount,
                'balance_after' => $agentAccount->balance,
                'description' => $this->description ?: "RETRAIT du compte " . $user->code . " Client: " . $user->name . " " . $user->postnom . " Retenu de ". $this->a_retenir. " ".$this->currency." par " . Auth::user()->name . " ". Auth::user()->postnom,
            ]);

            // Création de la transaction
            $transaction = Transaction::create([
                'account_id' => $account->id,
                'user_id' => $user->id,
                'type' => 'retrait',
                'currency' => $this->currency,
                'amount' => $this->amount,
                'balance_after' => $account->balance,
                'description' => $this->description ?: "RETRAIT dans votre compte " . $user->code . " Client: " . $user->name . " " . $user->postnom . " Retenu de ". $this->a_retenir. " ".$this->currency." par " . Auth::user()->name . " ". Auth::user()->postnom,
            ]);

            if (
                $this->a_retenir > 0
            ) {

                // Création de la transaction
                $retenuMiseAccount = Transaction::create([
                    'account_id' => null,
                    'user_id' => 195,
                    'type' => 'depot',
                    'currency' => $this->currency,
                    'amount' => $this->a_retenir,
                    'balance_after' => $retenuMiseAccount->balance,
                    'description' => $this->description ?: "Entree Retenu du compte " . $user->code . " Client: " . $user->name . " " . $user->postnom . " par " . Auth::user()->name . " ". Auth::user()->postnom,
                ]);

            }

            UserLogHelper::log_user_activity(
                action: 'retrait',
                description: "Retrait de {$this->amount} {$this->currency} du compte de {$user->name} {$user->postnom} ({$user->code}), retenu de {$this->a_retenir} {$this->currency}",
            );

            DB::commit();

            $this->reset(['amount', 'description']);
            $this->dispatch('closeModal', name: 'modalRetraitMembre');
            $this->dispatch('$refresh');
            notyf()->success('Retrait effectué avec succès !');
            $this->resetInputFields();
            $this->dispatch('facture-validee', url: route('receipt.generate', ['id' => $transaction->id]));

        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);
            notyf()->error('Une erreur est survenue lors du retrait. Veuillez réessayer plus tard.');
        }
    }

    public function submitRetraitCarte()
    {
        Gate::authorize('retrait-compte-membre', User::class);

        $this->validate([
            'card_id' => 'required|exists:membership_cards,id',
        ]);

        DB::beginTransaction();
        try {

            $card = MembershipCard::findOrFail($this->card_id);

            if ($card->is_active == 0) {
                notyf()->error( 'Retrait déjà effectué.');
                return;
            }

            $aretenir = $card->subscription_amount;

            // Retirer la mise totale
            $total = $card->contributions->where('is_paid', true)->sum('amount');

            if ($total < $aretenir) {
                notyf()->error( 'Cette carnet n\'est peut-être retirée car le solde est insuffisant.');
                return;
            }

            // Ajouter au compte du membre
            $account = Account::where('user_id', $card->member_id)
                ->where('currency', $card->currency)
                ->lockForUpdate()
                ->firstOrFail();

            if ($account->balance < $total) {
                DB::rollBack();
                notyf()->error('Le solde du compte est insuffisant.');
                return;
            }

            // Récupération de la caisse de l'agent
            $agentAccount = AgentAccount::firstOrCreate(
                ['user_id' => Auth::id(), 'currency' => $card->currency],
                ['balance' => 0]
            );

            // Récupération de la caisse de l'agent
            $retenuMiseAccount = AgentAccount::firstOrCreate(
                ['user_id' => 195, 'currency' => $card->currency],
                ['balance' => 0]
            );

            if ($agentAccount->balance < $total) {
                DB::rollBack();
                notyf()->error('Le solde de la caisse est insuffisant.');
                return;
            }

            $account->balance -= $total;
            $agentAccount->balance -= ($total - $aretenir);
            $retenuMiseAccount->balance += $aretenir;

            $account->save();
            $agentAccount->save();
            // Credite du compte retenu mise
            $retenuMiseAccount->save();

            // Marquer comme retiré
            $card->is_active = 0;
            $card->save();

            // Enregistrer la transaction
            $transaction = Transaction::create([
                'account_id' => $account->id,
                'user_id' => $card->member_id,
                'type' => 'retrait_carte_adhesion',
                'currency' => $card->currency,
                'amount' => $total - $aretenir,
                'balance_after' => $account->balance,
                'description' => $this->description ?: "Retrait carnet #{$card->id} " . $card->member->code ." ". $card->member->name . " " . $card->member->postnom . " Retenu de ". $aretenir. " ".$card->currency. " par " . Auth::user()->name . " ". Auth::user()->postnom,
            ]);

            // Enregistrer la transaction
            Transaction::create([
                'account_id' => NULL,
                'user_id' => Auth::user()->id,
                'type' => 'retrait_carte_adhesion',
                'currency' => $card->currency,
                'amount' => $total - $aretenir,
                'balance_after' => $agentAccount->balance,
                'description' => $this->description ?: "Retrait carnet #{$card->id} " . " Client: " . $card->member->code ." ". $card->member->name . " " . $card->member->postnom . " Retenu de ". $aretenir. " ".$card->currency. " par " . Auth::user()->name . " ". Auth::user()->postnom,

            ]);

            Transaction::create([
                'account_id' => null,
                'user_id' => 195,
                'type' => 'depot',
                'currency' => $card->currency,
                'amount' => $aretenir,
                'balance_after' => $retenuMiseAccount->balance,
                'description' => $this->description ?: "Entree Retenu de la carte #{$card->id} du compte " . $card->member->code . " Client: " . $card->member->name . " " . $card->member->postnom . " par " . Auth::user()->name . " ". Auth::user()->postnom,

            ]);

            UserLogHelper::log_user_activity(
                action: 'retrait_carte_adhesion',
                description: "Retrait de la carte #{$card->id} du membre {$card->member->name} {$card->member->postnom} ({$card->member->code}), montant total {$total} {$card->currency}, retenu de {$aretenir} {$card->currency}",
            );

            DB::commit();

            $this->reset(['type','amount', 'description']);
            $this->dispatch('closeModal', name: 'modalRetraitMembre');
            $this->dispatch('$refresh');
            notyf()->success('Retrait effectué avec succès !');
            $this->resetInputFields();
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
        $this->reset(['type']);

    }

    public function closeRetraitModal()
    {
        $this->dispatch('closeModal', name: 'modalRetraitMembre');
    }

    public function openDepositModal()
    {
        $this->type = '';
        $this->dispatch('openModal', name: 'modalDepositMembre');

    }

    public function openRetraitModal()
    {
        $this->dispatch('openModal', name: 'modalRetraitMembre');
    }

    public function openCardViewModal($cardId = null)
    {
        $this->cardDetail = MembershipCard::with(['contributions','member'])->find($cardId);

        $this->dispatch('openModal', name: 'modalCardDetails');

    }
    public function closeCardViewModal()
    {
        $this->dispatch('closeModal', name: 'modalCardDetails');
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

        return view('livewire.members.member-details',[
            'member' => $member,
            'transactions' => $transactions,
            'cards' => $this->cards
        ]);
    }

    public function resetInputFields()
    {
        $this->amount = 0;
        $this->currency = '';
        $this->description = '';
        $this->card_id = null;
        $this->selectedCard = null;
        $this->contribution_date = null;
        $this->operation_type = null;
        $this->type = null;
    }
}
