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

    // Variables publiques utilisées dans Blade
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

    // Constantes pour éviter les "magic strings"
    const DEPOSIT_TYPE_NORMAL = 'normal';
    const DEPOSIT_TYPE_CARD = 'carte';
    const TRANSACTION_TYPE_DEPOSIT = 'dépôt';
    const TRANSACTION_TYPE_WITHDRAWAL = 'retrait';
    const TRANSACTION_TYPE_DAILY_CONTRIBUTION = 'mise_quotidienne';
    const TRANSACTION_TYPE_CARD_WITHDRAWAL = 'retrait_carte_adhesion';
    const RETAINED_ACCOUNT_USER_ID = 195;

    public function mount($id)
    {
        Gate::authorize('afficher-client', User::class);
        $this->memberId = $id;
        $this->loadMemberCards();
    }

    /**
     * Charge les cartes du membre
     */
    private function loadMemberCards()
    {
        $this->cards = MembershipCard::where('member_id', $this->memberId)
            ->where('is_active', true)
            ->with(['contributions'])
            ->get();

        $this->allCards = MembershipCard::where('member_id', $this->memberId)
            ->with(['contributions'])
            ->latest()
            ->get();
    }

    // Gestion des modales
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
        match($this->operation_type) {
            self::DEPOSIT_TYPE_NORMAL => $this->submit(),
            self::DEPOSIT_TYPE_CARD => $this->contribute(),
            default => null
        };
    }

    public function showConfirmRetraitNormal()
    {
        if ($this->operation_type === self::DEPOSIT_TYPE_CARD) {
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
        match($this->operation_type) {
            self::DEPOSIT_TYPE_NORMAL => $this->submitRetrait(),
            self::DEPOSIT_TYPE_CARD => $this->submitRetraitCarte(),
            default => null
        };
    }

    /**
     * Effectue un dépôt sur le compte du membre
     */
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
            $account = $this->getOrCreateAccount($user->id, $this->currency);
            $agentAccount = $this->getOrCreateAgentAccount($this->currency);

            // Mise à jour des soldes
            $account->balance += $this->amount;
            $agentAccount->balance += $this->amount;
            $account->save();
            $agentAccount->save();

            // Création des transactions
            $this->createTransaction(
                null,
                Auth::id(),
                self::TRANSACTION_TYPE_DEPOSIT,
                $this->currency,
                $this->amount,
                $agentAccount->balance,
                $this->getDepositDescription($user, true)
            );

            $transaction = $this->createTransaction(
                $account->id,
                $user->id,
                self::TRANSACTION_TYPE_DEPOSIT,
                $this->currency,
                $this->amount,
                $account->balance,
                $this->getDepositDescription($user, false)
            );

            UserLogHelper::log_user_activity(
                action: self::TRANSACTION_TYPE_DEPOSIT,
                description: "Dépôt de {$this->amount} {$this->currency} sur le compte de {$user->name} {$user->postnom} ({$user->code})",
            );

            DB::commit();
            $this->afterTransactionSuccess($transaction, 'modalDepositMembre', 'Dépôt effectué avec succès !');

        } catch (\Throwable $th) {
            $this->handleTransactionError($th, 'dépôt');
        }
    }

    /**
     * Effectue une contribution sur une carte
     */
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
            $dailyAmount = $card->subscription_amount;
            $numberOfDaysToPay = floor($this->amount / $dailyAmount);

            if ($numberOfDaysToPay <= 0) {
                notyf()->error("Le montant doit être au moins égal à {$dailyAmount}.");
                return;
            }

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

            // Marquer les contributions comme payées
            $contributionsToPay->each->update(['is_paid' => true]);

            $totalPaid = $contributionsToPay->count() * $dailyAmount;

            if ($this->amount > $totalPaid) {
                notyf()->info("Le montant saisi ({$this->amount}) dépasse le reste dû ({$totalPaid}). Montant ajusté automatiquement.");
            }

            // Mettre à jour les comptes
            $account = $this->getOrCreateAccount($card->member_id, $card->currency);
            $agentAccount = $this->getOrCreateAgentAccount($card->currency);

            $account->balance += $totalPaid;
            $agentAccount->balance += $totalPaid;
            $account->save();
            $agentAccount->save();

            // Créer les transactions
            $this->createTransaction(
                null,
                Auth::id(),
                self::TRANSACTION_TYPE_DAILY_CONTRIBUTION,
                $card->currency,
                $totalPaid,
                $agentAccount->balance,
                $this->getContributionDescription($card, $contributionsToPay->count(), true)
            );

            $transaction = $this->createTransaction(
                $account->id,
                $card->member_id,
                self::TRANSACTION_TYPE_DAILY_CONTRIBUTION,
                $card->currency,
                $totalPaid,
                $account->balance,
                $this->getContributionDescription($card, $contributionsToPay->count(), false)
            );

             // --------------------------------------------------------
            // COMMISSION AGENT : première mise dans ce carnet
            // --------------------------------------------------------
            $firstEverContribution = $card->contributions()
                ->where('is_paid', true)
                ->orderBy('contribution_date', 'asc')
                ->first();

            // Si c'est la toute première mise payée
            if ($firstEverContribution && $firstEverContribution->id == $contributionsToPay->first()->id) {

                $commissionAmount = $dailyAmount; // La première mise vaut commission

                // Créditer le compte du membre et de l'agent
                $account = $this->getOrCreateAccount($card->member_id, $card->currency);
                $account->balance -= $commissionAmount;
                $account->save();

                $commissionAccount = $this->getOrCreateAgentAccount($card->currency, self::RETAINED_ACCOUNT_USER_ID);
                $commissionAccount->balance += $commissionAmount;
                $commissionAccount->save();

                $card->first_mise_retained = true;
                $card->save();

                $this->createTransaction(
                    null,
                    self::RETAINED_ACCOUNT_USER_ID,
                    'depot',
                    $card->currency,
                    $commissionAmount,
                    $commissionAccount->balance,
                    $this->getCardRetainedDescription($card)
                );

            }

            UserLogHelper::log_user_activity(
                action: self::TRANSACTION_TYPE_DAILY_CONTRIBUTION,
                description: "Paiement de {$contributionsToPay->count()} mises pour la carte #{$card->id} du membre {$card->member->name} {$card->member->postnom} ({$card->member->code})",
            );

            DB::commit();
            $this->afterTransactionSuccess($transaction, 'modalDepositMembre', "Paiement de {$contributionsToPay->count()} mise(s) effectué(s) avec succès !");

        } catch (\Throwable $th) {
            $this->handleTransactionError($th, 'dépôt');
        }
    }

    /**
     * Effectue un retrait du compte du membre
     */
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
            $account = $this->getOrCreateAccount($user->id, $this->currency);
            $agentAccount = $this->getOrCreateAgentAccount($this->currency);
            $retainedAccount = $this->getOrCreateAgentAccount($this->currency, self::RETAINED_ACCOUNT_USER_ID);

            $totalAmount = $this->amount + $this->a_retenir;

            if ($account->balance < $totalAmount) {
                DB::rollBack();
                notyf()->error('Le solde du compte est insuffisant.');
                return;
            }

            if ($agentAccount->balance < $this->amount) {
                DB::rollBack();
                notyf()->error('Le solde de la caisse est insuffisant.');
                return;
            }

            // Mettre à jour les soldes
            $account->balance -= $totalAmount;
            $agentAccount->balance -= $this->amount;
            $retainedAccount->balance += $this->a_retenir;

            $account->save();
            $agentAccount->save();
            $retainedAccount->save();

            // Créer les transactions
            $this->createTransaction(
                null,
                Auth::id(),
                self::TRANSACTION_TYPE_WITHDRAWAL,
                $this->currency,
                $this->amount,
                $agentAccount->balance,
                $this->getWithdrawalDescription($user, true)
            );

            $transaction = $this->createTransaction(
                $account->id,
                $user->id,
                self::TRANSACTION_TYPE_WITHDRAWAL,
                $this->currency,
                $this->amount,
                $account->balance,
                $this->getWithdrawalDescription($user, false)
            );

            if ($this->a_retenir > 0) {
                $this->createTransaction(
                    null,
                    self::RETAINED_ACCOUNT_USER_ID,
                    'depot',
                    $this->currency,
                    $this->a_retenir,
                    $retainedAccount->balance,
                    $this->getRetainedDescription($user)
                );
            }

            UserLogHelper::log_user_activity(
                action: self::TRANSACTION_TYPE_WITHDRAWAL,
                description: "Retrait de {$this->amount} {$this->currency} du compte de {$user->name} {$user->postnom} ({$user->code}), retenu de {$this->a_retenir} {$this->currency}",
            );

            DB::commit();
            $this->afterTransactionSuccess($transaction, 'modalRetraitMembre', 'Retrait effectué avec succès !');

        } catch (\Throwable $th) {
            $this->handleTransactionError($th, 'retrait');
        }
    }

    /**
     * Effectue un retrait de carte
     */
    public function submitRetraitCarte()
    {
        Gate::authorize('retrait-compte-membre', User::class);

        $this->validate([
            'card_id' => 'required|exists:membership_cards,id',
        ]);

        DB::beginTransaction();
        try {
            $card = MembershipCard::findOrFail($this->card_id);

            if (!$card->is_active) {
                notyf()->error('Retrait déjà effectué.');
                return;
            }

            if ($card->first_mise_retained === false) {
                $toRetain = $card->subscription_amount;
                $total = $card->contributions->where('is_paid', true)->sum('amount');

            }else {
                $toRetain = 0;
                $total = $card->contributions->where('is_paid', true)->sum('amount') - $card->subscription_amount;
            }
            
            if ($total < $toRetain) {
                notyf()->error('Cette carte ne peut pas être retirée car le solde est insuffisant.');
                return;
            }

            $account = Account::where('user_id', $card->member_id)
                ->where('currency', $card->currency)
                ->lockForUpdate()
                ->firstOrFail();

            if ($account->balance < $total) {
                DB::rollBack();
                notyf()->error('Le solde du compte est insuffisant.');
                return;
            }

            $agentAccount = $this->getOrCreateAgentAccount($card->currency);
            $retainedAccount = $this->getOrCreateAgentAccount($card->currency, self::RETAINED_ACCOUNT_USER_ID);

            if ($agentAccount->balance < ($total - $toRetain)) {
                DB::rollBack();
                notyf()->error('Le solde de la caisse est insuffisant.');
                return;
            }

            // Mettre à jour les soldes
            $account->balance -= $total;
            $agentAccount->balance -= ($total - $toRetain);
            $retainedAccount->balance += $toRetain;

            $account->save();
            $agentAccount->save();
            $retainedAccount->save();

            // Marquer la carte comme inactive
            $card->is_active = false;
            $card->save();

            // Créer les transactions
            $transaction = $this->createTransaction(
                $account->id,
                $card->member_id,
                self::TRANSACTION_TYPE_CARD_WITHDRAWAL,
                $card->currency,
                $total - $toRetain,
                $account->balance,
                $this->getCardWithdrawalDescription($card, true)
            );

            $this->createTransaction(
                null,
                Auth::user()->id,
                self::TRANSACTION_TYPE_CARD_WITHDRAWAL,
                $card->currency,
                $total - $toRetain,
                $agentAccount->balance,
                $this->getCardWithdrawalDescription($card, false)
            );

            $this->createTransaction(
                null,
                self::RETAINED_ACCOUNT_USER_ID,
                'depot',
                $card->currency,
                $toRetain,
                $retainedAccount->balance,
                $this->getCardRetainedDescription($card)
            );

            UserLogHelper::log_user_activity(
                action: self::TRANSACTION_TYPE_CARD_WITHDRAWAL,
                description: "Retrait de la carte #{$card->id} du membre {$card->member->name} {$card->member->postnom} ({$card->member->code}), montant total {$total} {$card->currency}, retenu de {$toRetain} {$card->currency}",
            );

            DB::commit();
            $this->afterTransactionSuccess($transaction, 'modalRetraitMembre', 'Retrait effectué avec succès !');

        } catch (\Throwable $th) {
            $this->handleTransactionError($th, 'retrait');
        }
    }

    /**
     * Met à jour la carte sélectionnée et le montant
     */
    public function updatedCardId()
    {
        $this->selectedCard = MembershipCard::find($this->card_id);
        $this->amount = $this->selectedCard->subscription_amount ?? 0;
    }

    /**
     * Met à jour le type d'opération
     */
    public function updatedType()
    {
        $this->operation_type = $this->type;
    }

    // Gestion des modales
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

    /**
     * Affiche la vue avec les données du membre
     */
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

    /**
     * Réinitialise les champs de saisie
     */
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

    /**
     * Obtient ou crée un compte utilisateur
     */
    private function getOrCreateAccount($userId, $currency)
    {
        return Account::firstOrCreate(
            ['user_id' => $userId, 'currency' => $currency],
            ['balance' => 0]
        );
    }

    /**
     * Obtient ou crée un compte agent
     */
    private function getOrCreateAgentAccount($currency, $userId = null)
    {
        $userId = $userId ?? Auth::id();
        return AgentAccount::firstOrCreate(
            ['user_id' => $userId, 'currency' => $currency],
            ['balance' => 0]
        );
    }

    /**
     * Crée une transaction
     */
    private function createTransaction($accountId, $userId, $type, $currency, $amount, $balanceAfter, $description)
    {
        return Transaction::create([
            'account_id' => $accountId,
            'user_id' => $userId,
            'type' => $type,
            'currency' => $currency,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'description' => $description,
        ]);
    }

    /**
     * Génère la description pour un dépôt
     */
    private function getDepositDescription($user, $isAgent = false)
    {
        $action = $isAgent ? 'DEPOT du compte' : 'DEPOT dans votre compte';
        $defaultDescription = "{$action} {$user->code} Client: {$user->name} {$user->postnom} par " . Auth::user()->name . " " . Auth::user()->postnom;
        return $this->description ?: $defaultDescription;
    }

    /**
     * Génère la description pour une contribution
     */
    private function getContributionDescription($card, $count, $isAgent = false)
    {
        $authUser = Auth::user();
        $clientInfo = "{$card->member->name} {$card->member->postnom}";
        $action = $isAgent ? "Paiement groupé de {$count} mises sur la carte #{$card->id} pour le client: {$clientInfo} par" : "Paiement groupé de {$count} mises sur la carte #{$card->id} pour le client: {$clientInfo} par";
        return "{$action} {$authUser->name} {$authUser->postnom}";
    }

    /**
     * Génère la description pour un retrait
     */
    private function getWithdrawalDescription($user, $isAgent = false)
    {
        $action = $isAgent ? 'RETRAIT du compte' : 'RETRAIT dans votre compte';
        $defaultDescription = "{$action} {$user->code} Client: {$user->name} {$user->postnom} Retenu de {$this->a_retenir} {$this->currency} par " . Auth::user()->name . " " . Auth::user()->postnom;
        return $this->description ?: $defaultDescription;
    }

    /**
     * Génère la description pour un montant retenu
     */
    private function getRetainedDescription($user)
    {
        $defaultDescription = "Entree Retenu du compte {$user->code} Client: {$user->name} {$user->postnom} par " . Auth::user()->name . " " . Auth::user()->postnom;
        return $this->description ?: $defaultDescription;
    }

    /**
     * Génère la description pour un retrait de carte
     */
    private function getCardWithdrawalDescription($card, $isAgent = false)
    {
        $authUser = Auth::user();
        $clientInfo = "{$card->member->code} {$card->member->name} {$card->member->postnom}";
        $action = $isAgent ? "Retrait carnet #{$card->id}" : "Retrait carnet #{$card->id}";
        return "{$action} Client: {$clientInfo} Retenu de {$card->subscription_amount} {$card->currency} par {$authUser->name} {$authUser->postnom}";
    }

    /**
     * Génère la description pour un montant retenu de carte
     */
    private function getCardRetainedDescription($card)
    {
        $authUser = Auth::user();
        $clientInfo = "{$card->member->code} Client: {$card->member->name} {$card->member->postnom}";
        return "Entree Retenu de la carte #{$card->id} du compte {$clientInfo} par {$authUser->name} {$authUser->postnom}";
    }

    /**
     * Actions à effectuer après une transaction réussie
     */
    private function afterTransactionSuccess($transaction, $modalName, $successMessage)
    {
        $this->reset(['amount', 'description']);
        $this->dispatch('closeModal', name: $modalName);
        $this->dispatch('$refresh');
        notyf()->success($successMessage);
        $this->resetInputFields();
        $this->dispatch('facture-validee', url: route('receipt.generate', ['id' => $transaction->id]));
    }

    /**
     * Gère les erreurs de transaction
     */
    private function handleTransactionError($th, $operationType)
    {
        DB::rollBack();
        report($th);
        $errorMessage = $operationType === 'dépôt' 
            ? 'Une erreur est survenue lors du dépôt. Veuillez réessayer plus tard.'
            : 'Une erreur est survenue lors du retrait. Veuillez réessayer plus tard.';
        notyf()->error($errorMessage);
    }
}