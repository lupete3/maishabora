<?php

namespace App\Livewire\Members;

use App\Helpers\UserLogHelper;
use App\Models\Account;
use App\Models\AgentAccount;
use App\Models\MembershipCard;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Models\Notification;
use Illuminate\Validation\Rule;

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
    public $cardDetail;
    public $openConfirmDepositNormal = false;
    public $openConfirmRetraitNormal = false;

    // Filtres de date pour les transactions
    public $date_filter = '30_days';
    public $date_from;
    public $date_to;

    // Modification de solde
    public $editingAccountId;
    public $newBalance;
    public $modificationReason;
    public $openModifyBalance = false;

    // Modification de transaction
    public $editingTransactionId;
    public $editAmount;
    public $editBalanceAfter;
    public $editDescription;
    public $openEditTransaction = false;
    public $openConfirmDeleteTransaction = false;

    // Constantes pour éviter les "magic strings"
    const DEPOSIT_TYPE_NORMAL = 'normal';
    const DEPOSIT_TYPE_CARD = 'carte';
    const TRANSACTION_TYPE_DEPOSIT = 'dépôt';
    const TRANSACTION_TYPE_WITHDRAWAL = 'retrait';
    const TRANSACTION_TYPE_DAILY_CONTRIBUTION = 'mise_quotidienne';
    const TRANSACTION_TYPE_CARD_WITHDRAWAL = 'retrait_carte_adhesion';
    const RETAINED_ACCOUNT_USER_ID = 195;

    const CARD_MIGRATION_DATE = '2026-01-08';

    const MIN_BALANCE_USD = 5;
    const MIN_BALANCE_CDF = 5000;

    public function mount($id)
    {
        Gate::authorize('afficher-client', User::class);
        $this->memberId = $id;
        $this->loadMemberCards();
        $this->initializeDateFilter();
    }

    /**
     * Charge les cartes du membre
     */
    private function loadMemberCards()
    {
        $this->cards = MembershipCard::where('member_id', $this->memberId)
            ->where('is_active', true)
            ->where('card_type', 'epargne')
            ->with(['contributions'])
            ->get();

        $this->allCards = MembershipCard::where('member_id', $this->memberId)
            ->with(['contributions'])
            ->latest()
            ->get();
    }

    /*
     * Migre les comptes du membre vers la nouvelle structure
     */
    public function migrateAccounts()
    {
        Gate::authorize('migrer-comptes');

        $user = User::findOrFail($this->memberId);
        $migrated = false;

        foreach (['USD', 'CDF'] as $currency) {
            // Check/Create Current Account
            $current = Account::where('user_id', $user->id)
                ->where('currency', $currency)
                ->where('type', 'current')
                ->first();

            if (!$current) {
                // S'il existe un compte 'normal' ou null (ancien système), on le convertit ou on en crée un nouveau
                $legacy = Account::where('user_id', $user->id)
                    ->where('currency', $currency)
                    ->where(function ($q) {
                        $q->whereNull('type')->orWhere('type', 'normal');
                    })
                    ->first();

                if ($legacy) {
                    $legacy->type = 'current';
                    $legacy->save();
                    $migrated = true;
                } else {
                    Account::create([
                        'user_id' => $user->id,
                        'currency' => $currency,
                        'type' => 'current',
                        'balance' => 0
                    ]);
                    $migrated = true;
                }
            }

            // Check/Create Savings Account
            $savings = Account::firstOrCreate([
                'user_id' => $user->id,
                'currency' => $currency,
                'type' => 'savings'
            ], ['balance' => 0]);
        }

        if ($migrated) {
            notyf()->success('Comptes migrés avec succès.');
        } else {
            notyf()->info('Les comptes sont déjà à jour.');
        }

        $this->dispatch('$refresh');
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
        match ($this->operation_type) {
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
        match ($this->operation_type) {
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
            'amount' => [
                'required',
                'numeric',
                Rule::when(
                    $this->currency === 'CDF',
                    ['min:1000'],
                    ['min:0.1']
                ),
            ],
        ]);

        DB::beginTransaction();
        try {
            $user = User::findOrFail($this->memberId);
            $account = $this->getOrCreateAccount($user->id, $this->currency, 'current');
            $agentAccount = $this->getOrCreateAgentAccount($this->currency);

            // Mise à jour des soldes
            // $account->balance += $this->amount;
            // $agentAccount->balance += $this->amount;
            // $account->save();
            // $agentAccount->save();

            Account::where('id', $account->id)
                ->lockForUpdate()
                ->increment('balance', $this->amount);

            AgentAccount::where('id', $agentAccount->id)
                ->lockForUpdate()
                ->increment('balance', $this->amount);

            $account->refresh();
            $agentAccount->refresh();

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

            // ÉCRITURE COMPTABLE AUTOMATIQUE
            // try {
            //     $accountingService = app(\App\Services\AccountingService::class);
            //     $accountingService->recordDeposit($account, (float) $this->amount, $this->currency);
            // } catch (\Exception $e) {
            //     \Illuminate\Support\Facades\Log::error("Erreur comptable dépôt membre: " . $e->getMessage());
            // }

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

            $useCurrentAccount = $card->created_at->lt(Carbon::parse(self::CARD_MIGRATION_DATE));
            $accountType = $useCurrentAccount ? 'current' : 'savings';

            // Mettre à jour les comptes
            $account = $this->getOrCreateAccount($card->member_id, $card->currency, $accountType);
            $agentAccount = $this->getOrCreateAgentAccount($card->currency);

            // $account->balance += $totalPaid;
            // $agentAccount->balance += $totalPaid;
            // $account->save();
            // $agentAccount->save();

            Account::where('id', $account->id)
                ->lockForUpdate()
                ->increment('balance', $totalPaid);

            AgentAccount::where('id', $agentAccount->id)
                ->lockForUpdate()
                ->increment('balance', $totalPaid);

            $account->refresh();
            $agentAccount->refresh();

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
                $account = $this->getOrCreateAccount($card->member_id, $card->currency, $accountType);
                $commissionAccount = $this->getOrCreateAgentAccount($card->currency, self::RETAINED_ACCOUNT_USER_ID);

                // $account->balance -= $commissionAmount;
                // $account->save();

                // $commissionAccount->balance += $commissionAmount;
                // $commissionAccount->save();

                Account::where('id', $account->id)
                        ->decrement('balance', $commissionAmount);
                $account->refresh();

                AgentAccount::where('id', $commissionAccount->id)
                        ->increment('balance', $commissionAmount);
                $commissionAccount->refresh();

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

                // // Ajout d'une transaction visible pour le client justifiant la diminution du solde
                // $this->createTransaction(
                //     $account->id,
                //     $card->member_id,
                //     self::TRANSACTION_TYPE_WITHDRAWAL,
                //     $card->currency,
                //     $commissionAmount,
                //     $account->balance,
                //     "Retenue de la première mise (Frais d'adhésion) sur la carte #{$card->id}"
                // );

            }

            UserLogHelper::log_user_activity(
                action: self::TRANSACTION_TYPE_DAILY_CONTRIBUTION,
                description: "Paiement de {$contributionsToPay->count()} mises pour la carte #{$card->id} du membre {$card->member->name} {$card->member->postnom} ({$card->member->code})",
            );

            DB::commit();

            // ÉCRITURE COMPTABLE AUTOMATIQUE - COTISATION QUOTIDIENNE
            // try {
            //     $accountingService = app(\App\Services\AccountingService::class);
            //     $accountingService->recordDailyContribution($card, (float) $totalPaid, $card->currency);
            // } catch (\Exception $e) {
            //     \Illuminate\Support\Facades\Log::error("Erreur comptable contribution quotidienne: " . $e->getMessage());
            // }

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
            $account = $this->getOrCreateAccount($user->id, $this->currency, 'current');
            $agentAccount = $this->getOrCreateAgentAccount($this->currency);
            $retainedAccount = $this->getOrCreateAgentAccount($this->currency, self::RETAINED_ACCOUNT_USER_ID);

            $totalAmount = $this->amount + $this->a_retenir;
            $minBalance = ($this->currency === 'USD') ? self::MIN_BALANCE_USD : self::MIN_BALANCE_CDF;

            if (!$account->can_withdraw_all) {
                if (($account->balance - $totalAmount) < $minBalance) {
                    DB::rollBack();
                    notyf()->error("Opération impossible. Le solde minimum obligatoire est de {$minBalance} {$this->currency}.");
                    return;
                }
            }

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
            // $account->balance -= $totalAmount;
            // $agentAccount->balance -= $this->amount;
            // $retainedAccount->balance += $this->a_retenir;

            // $account->save();
            // $agentAccount->save();
            // $retainedAccount->save();

            Account::where('id', $account->id)
                ->lockForUpdate()
                ->decrement('balance', $totalAmount);

            AgentAccount::where('id', $agentAccount->id)
                ->lockForUpdate()
                ->decrement('balance', $this->amount);

            AgentAccount::where('id', $retainedAccount->id)
                ->lockForUpdate()
                ->increment('balance', $this->a_retenir);

            $account->refresh();
            $agentAccount->refresh();
            $retainedAccount->refresh();

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

            // Notifier les utilisateurs concernés
            $usersToNotify = User::role(['Admin', 'Caissier', 'SUPER IT', 'Comptable'])->get();
            $notificationMessage = "Un retrait de " . number_format($this->amount, 2) . " {$this->currency} a été effectué pour le membre {$user->name} {$user->postnom} ({$user->code}) par " . Auth::user()->name . "." . Auth::user()->postnom . ".";

            foreach ($usersToNotify as $notifyUser) {
                Notification::create([
                    'user_id' => $notifyUser->id,
                    'title' => 'Retrait effectué',
                    'message' => $notificationMessage,
                    'read' => false,
                ]);
            }

            DB::commit();

            // ÉCRITURE COMPTABLE AUTOMATIQUE - RETRAIT
            // try {
            //     $accountingService = app(\App\Services\AccountingService::class);
            //     $accountingService->recordWithdrawal($account, (float) $this->amount, $this->currency);
            // } catch (\Exception $e) {
            //     \Illuminate\Support\Facades\Log::error("Erreur comptable retrait membre: " . $e->getMessage());
            // }

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

            if (!$card->first_mise_retained) {
                $toRetain = $card->subscription_amount;
                $total = $card->contributions->where('is_paid', true)->sum('amount');
            } else {
                $toRetain = 0;
                $total = $card->contributions->where('is_paid', true)->sum('amount') - $card->subscription_amount;
            }

            if ($total < $toRetain) {
                notyf()->error('Cette carte ne peut pas être retirée car le solde est insuffisant.');
                return;
            }

            $useCurrentAccount = $card->created_at->lt(
                Carbon::parse(self::CARD_MIGRATION_DATE)
            );

            $accountType = $useCurrentAccount ? 'current' : 'savings';

            $account = $this->getOrCreateAccount($card->member_id, $card->currency, $accountType);

            if ($accountType === 'current') {
                $minBalance = ($card->currency === 'USD') ? self::MIN_BALANCE_USD : self::MIN_BALANCE_CDF;
                if (!$account->can_withdraw_all) {
                    if (($account->balance - $total) < $minBalance) {
                        DB::rollBack();
                        notyf()->error("Opération impossible. Le retrait de ce carnet laisserait un solde inférieur au minimum de {$minBalance} {$card->currency} sur le compte courant.");
                        return;
                    }
                }
            }

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
            // $account->balance -= $total;
            // $agentAccount->balance -= ($total - $toRetain);
            // $retainedAccount->balance += $toRetain;

            // $account->save();
            // $agentAccount->save();
            // $retainedAccount->save();

            Account::where('id', $account->id)
                ->lockForUpdate()
                ->decrement('balance', $total);

            AgentAccount::where('id', $agentAccount->id)
                ->lockForUpdate()
                ->decrement('balance', ($total - $toRetain));

            AgentAccount::where('id', $retainedAccount->id)
                ->lockForUpdate()
                ->increment('balance', $toRetain);

            $account->refresh();
            $agentAccount->refresh();
            $retainedAccount->refresh();

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

            if ($toRetain > 0) {
                $this->createTransaction(
                    null,
                    self::RETAINED_ACCOUNT_USER_ID,
                    'depot',
                    $card->currency,
                    $toRetain,
                    $retainedAccount->balance,
                    $this->getCardRetainedDescription($card)
                );
            }

            UserLogHelper::log_user_activity(
                action: self::TRANSACTION_TYPE_CARD_WITHDRAWAL,
                description: "Retrait de la carte #{$card->id} du membre {$card->member->name} {$card->member->postnom} ({$card->member->code}), montant total {$total} {$card->currency}, retenu de {$toRetain} {$card->currency}",
            );

            // Notifier les utilisateurs concernés
            $usersToNotify = User::role(['Admin', 'Caissier', 'SUPER IT', 'Comptable'])->get();
            $notificationMessage = "Un retrait de carte (Total: " . number_format($total, 2) . " {$card->currency}) a été effectué pour le membre {$card->member->name} {$card->member->postnom} ({$card->member->code}) par " . Auth::user()->name . "." . Auth::user()->postnom . ".";

            foreach ($usersToNotify as $notifyUser) {
                Notification::create([
                    'user_id' => $notifyUser->id,
                    'title' => 'Retrait Carte effectué',
                    'message' => $notificationMessage,
                    'read' => false,
                ]);
            }

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
        $this->cardDetail = MembershipCard::with(['contributions', 'member'])->find($cardId);
        $this->dispatch('openModal', name: 'modalCardDetails');
    }

    public function closeCardViewModal()
    {
        $this->dispatch('closeModal', name: 'modalCardDetails');
    }

    public function toggleContributionStatus($contributionId)
    {
        Gate::authorize('modifier-transaction-compte');

        $contribution = \App\Models\DailyContribution::find($contributionId);
        if (!$contribution) {
            $this->dispatch('notyf', type: 'error', message: 'Contribution non trouvée.');
            return;
        }

        $oldStatus = $contribution->is_paid ? 'Payé' : 'Non payé';
        $contribution->is_paid = !$contribution->is_paid;
        $contribution->save();
        $newStatus = $contribution->is_paid ? 'Payé' : 'Non payé';

        // Rafraîchir les détails du carnet
        $this->cardDetail = MembershipCard::with(['contributions', 'member'])->find($contribution->membership_card_id);

        // Journalisation
        UserLogHelper::log_user_activity(
            "Modification du statut d'une contribution",
            "Statut passé de {$oldStatus} à {$newStatus} pour la contribution ID {$contributionId} (Carnet: {$this->cardDetail->code})"
        );

        $this->dispatch('notyf', type: 'success', message: 'Statut de contribution mis à jour.');
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

        // Obtenir la plage de dates selon le filtre actif
        [$dateFrom, $dateTo] = $this->getDateRange();

        $transactions = Transaction::whereIn('account_id', $accountIds)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($this->search, function ($query) {
                $searchTerm = "%{$this->search}%";
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('type', 'like', $searchTerm)
                        ->orWhere('currency', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.members.member-details', [
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
    private function getOrCreateAccount($userId, $currency, $type = 'current')
    {
        $account = Account::firstOrCreate(
            ['user_id' => $userId, 'currency' => $currency, 'type' => $type],
            ['balance' => 0]
        );
        return Account::where('id', $account->id)->lockForUpdate()->first();
    }

    /**
     * Obtient ou crée un compte agent
     */
    private function getOrCreateAgentAccount($currency, $userId = null)
    {
        $userId = $userId ?? Auth::id();
        $account = AgentAccount::firstOrCreate(
            ['user_id' => $userId, 'currency' => $currency],
            ['balance' => 0]
        );
        return AgentAccount::where('id', $account->id)->lockForUpdate()->first();
    }

    /**
     * Crée une transaction
     */
    private function createTransaction($accountId, $userId, $type, $currency, $amount, $balanceAfter, $description, $agentAccountId = null)
    {
        // Si agentAccountId est null, on tente de le trouver via userId
        if (!$agentAccountId) {
            $agentAcc = AgentAccount::where('user_id', $userId)->where('currency', $currency)->first();
            $agentAccountId = $agentAcc ? $agentAcc->id : null;
        }

        return Transaction::create([
            'account_id' => $accountId,
            'agent_account_id' => $agentAccountId,
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
        $this->dispatch('demander-impression',
            urlPC: route('receipt.generate', ['id' => $transaction->id]),
            urlPOS: route('receipt.generate_pos', ['id' => $transaction->id])
        );
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

    public function toggleVisibleAccount($memberId)
    {
        $member = User::findOrFail($memberId);
        $member->visible_account = !$member->visible_account;
        $member->save();
        $this->dispatch('$refresh');
    }

    public function toggleWithdrawAll($accountId)
    {
        Gate::authorize('autoriser-tout-retirer', User::class);

        $account = Account::findOrFail($accountId);
        $account->can_withdraw_all = !$account->can_withdraw_all;
        $account->save();

        $status = $account->can_withdraw_all ? 'activée' : 'désactivée';
        notyf()->success("Autorisation de tout retirer {$status}.");
        $this->dispatch('$refresh');
    }

    // --- GESTION DIRECTE DES SOLDES ---

    public function confirmUpdateBalance($accountId)
    {
        Gate::authorize('modifier-solde-compte', User::class);
        $account = Account::findOrFail($accountId);
        $this->editingAccountId = $accountId;
        $this->newBalance = $account->balance;
        $this->modificationReason = '';
        $this->openModifyBalance = true;
    }

    public function updateBalance()
    {
        Gate::authorize('modifier-solde-compte', User::class);

        $this->validate([
            'newBalance' => 'required|numeric|min:0',
            'modificationReason' => 'required|string|min:5',
        ]);

        DB::beginTransaction();
        try {
            $account = Account::findOrFail($this->editingAccountId);
            $oldBalance = $account->balance;
            $account->balance = $this->newBalance;
            $account->save();

            // Créer une transaction de rectification
            $this->createTransaction(
                $account->id,
                $account->user_id,
                'rectification_solde',
                $account->currency,
                abs($this->newBalance - $oldBalance),
                $account->balance,
                "RECTIFICATION MANUELLE DU SOLDE: " . ($this->newBalance >= $oldBalance ? "+" : "-") . " " . abs($this->newBalance - $oldBalance) . " " . $account->currency . ". Raison: " . $this->modificationReason
            );

            UserLogHelper::log_user_activity(
                action: 'modifier_solde',
                description: "Modification manuelle du solde {$account->currency} de {$account->user->name} ({$account->user->code}). Ancien: {$oldBalance}, Nouveau: {$this->newBalance}. Raison: {$this->modificationReason}",
            );

            DB::commit();
            $this->openModifyBalance = false;
            notyf()->success('Solde mis à jour avec succès !');
            $this->dispatch('$refresh');
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);
            notyf()->error('Erreur lors de la mise à jour du solde.');
        }
    }

    // --- GESTION DES TRANSACTIONS ---

    public function confirmEditTransaction($transactionId)
    {
        Gate::authorize('modifier-transaction-compte', User::class);
        $transaction = Transaction::findOrFail($transactionId);
        $this->editingTransactionId = $transactionId;
        $this->editAmount = $transaction->amount;
        $this->editBalanceAfter = $transaction->balance_after;
        $this->editDescription = $transaction->description;
        $this->openEditTransaction = true;
    }

    public function updateTransaction()
    {
        Gate::authorize('modifier-transaction-compte', User::class);

        $this->validate([
            'editAmount' => 'required|numeric|min:0',
            'editBalanceAfter' => 'required|numeric|min:0',
            'editDescription' => 'required|string|min:5',
        ]);

        try {
            $transaction = Transaction::findOrFail($this->editingTransactionId);
            $oldAmount = $transaction->amount;

            $transaction->amount = $this->editAmount;
            $transaction->balance_after = $this->editBalanceAfter;
            $transaction->description = $this->editDescription;
            $transaction->save();

            UserLogHelper::log_user_activity(
                action: 'modifier_transaction',
                description: "Modification simple de la transaction #{$transaction->id}. Montant: {$oldAmount} -> {$this->editAmount}. Raison: {$this->editDescription}",
            );

            $this->openEditTransaction = false;
            notyf()->success('Transaction mise à jour avec succès !');
            $this->dispatch('$refresh');
        } catch (\Throwable $th) {
            report($th);
            notyf()->error('Erreur lors de la modification de la transaction.');
        }
    }

    public function confirmDeleteTransaction($transactionId)
    {
        Gate::authorize('modifier-transaction-compte', User::class);
        $this->editingTransactionId = $transactionId;
        $this->openConfirmDeleteTransaction = true;
    }

    public function deleteTransaction()
    {
        Gate::authorize('modifier-transaction-compte', User::class);

        try {
            $transaction = Transaction::findOrFail($this->editingTransactionId);
            $details = "ID: #{$transaction->id}, Type: {$transaction->type}, Montant: {$transaction->amount} {$transaction->currency}";

            $transaction->delete();

            UserLogHelper::log_user_activity(
                action: 'supprimer_transaction',
                description: "Suppression simple de la transaction: {$details}",
            );

            $this->openConfirmDeleteTransaction = false;
            notyf()->success('Transaction supprimée avec succès !');
            $this->dispatch('$refresh');
        } catch (\Throwable $th) {
            report($th);
            notyf()->error('Erreur lors de la suppression de la transaction.');
        }
    }

    /**
     * Initialise le filtre de date par défaut (30 derniers jours)
     */
    private function initializeDateFilter()
    {
        $this->date_filter = '30_days';
        $this->date_from = null;
        $this->date_to = null;
    }

    /**
     * Calcule la plage de dates selon le filtre actif
     * @return array [dateFrom, dateTo]
     */
    private function getDateRange()
    {
        $now = now();

        switch ($this->date_filter) {
            case '30_days':
                return [
                    $now->copy()->subDays(30)->startOfDay(),
                    $now->copy()->endOfDay()
                ];

            case '3_months':
                return [
                    $now->copy()->subMonths(3)->startOfDay(),
                    $now->copy()->endOfDay()
                ];

            case 'custom':
                // Validation des dates personnalisées
                if (!$this->date_from || !$this->date_to) {
                    // Si les dates ne sont pas définies, utiliser 30 jours par défaut
                    return [
                        $now->copy()->subDays(30)->startOfDay(),
                        $now->copy()->endOfDay()
                    ];
                }

                try {
                    $dateFrom = \Carbon\Carbon::parse($this->date_from)->startOfDay();
                    $dateTo = \Carbon\Carbon::parse($this->date_to)->endOfDay();

                    // Vérifier que date_from <= date_to
                    if ($dateFrom->gt($dateTo)) {
                        notyf()->error('La date de début doit être antérieure ou égale à la date de fin.');
                        return [
                            $now->copy()->subDays(30)->startOfDay(),
                            $now->copy()->endOfDay()
                        ];
                    }

                    return [$dateFrom, $dateTo];
                } catch (\Exception $e) {
                    notyf()->error('Format de date invalide.');
                    return [
                        $now->copy()->subDays(30)->startOfDay(),
                        $now->copy()->endOfDay()
                    ];
                }

            default:
                return [
                    $now->copy()->subDays(30)->startOfDay(),
                    $now->copy()->endOfDay()
                ];
        }
    }

    /**
     * Hook Livewire appelé quand date_filter change
     */
    public function updatedDateFilter()
    {
        // Réinitialiser la pagination
        $this->resetPage();

        // Si on revient à un preset, réinitialiser les dates personnalisées
        if ($this->date_filter !== 'custom') {
            $this->date_from = null;
            $this->date_to = null;
        }
    }

    /**
     * Applique le filtre de date personnalisé
     */
    public function applyCustomFilter()
    {
        $this->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ], [
            'date_from.required' => 'La date de début est requise.',
            'date_from.date' => 'Format de date invalide.',
            'date_to.required' => 'La date de fin est requise.',
            'date_to.date' => 'Format de date invalide.',
            'date_to.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ]);

        $this->date_filter = 'custom';
        $this->resetPage();
        notyf()->success('Filtre personnalisé appliqué avec succès.');
    }
}
