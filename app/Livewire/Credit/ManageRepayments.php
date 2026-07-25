<?php

namespace App\Livewire\Credit;

use App\Helpers\UserLogHelper;
use Livewire\Component;
use App\Models\User;
use App\Models\Credit;
use App\Models\Repayment;
use App\Models\Account;
use App\Models\AgentAccount;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\UserLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class ManageRepayments extends Component
{
    const MIN_BALANCE_USD = 5;
    const MIN_BALANCE_CDF = 5000;

    public $member_id;
    public $credit_id;
    public $selectedCredit = null;

    public $members = [];
    public $credits = [];
    public string $search = '';
    public array $results = [];

    public $repaymentToPay = null;
    public $applyInterest = true; // valeur par défaut

    public $penality = 0;
    public $password;

    public $openModalConfirm = false;

    protected $rules = [
        'member_id' => 'required|exists:users,id',
        'credit_id' => 'required|exists:credits,id',
    ];

    public function render()
    {
        return view('livewire.credit.manage-repayments');
    }

    public function mount()
    {
        $user = Auth::user();
        Gate::authorize('afficher-credit', User::class);
    }

    public function updatedSearch()
    {
        $query = trim($this->search);
        if ($query !== '') {
            $this->results = User::query()
                ->where(function ($q) use ($query) {
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

    public function confirmRepayment($repaymentId)
    {
        $this->repaymentToPay = $repaymentId;
        $repayment = Repayment::findOrFail($repaymentId);
        $this->penality = floatval($repayment->penalty);
        $this->openModalConfirm = true;
        //$this->dispatch('openModal', name: 'confirm-repayment'); // JS pour ouvrir le modal
    }

    public function payRepayment($withInterest = true)
    {
        $repaymentId = $this->repaymentToPay;

        if (!\Illuminate\Support\Facades\Hash::check($this->password, Auth::user()->password)) {
            $this->addError('password', 'Mot de passe incorrect.');
            notyf()->error('Mot de passe incorrect.');
            return;
        }

        try {

            DB::transaction(function () use ($repaymentId, $withInterest) {

                $repayment = Repayment::findOrFail($repaymentId);

                if ($repayment->is_paid) {
                    notyf()->info(__('Cette échéance a déjà été remboursée.'));
                    return;
                }

                $credit = $repayment->credit;
                $member = $credit->user;

                // Récupération ou création du compte membre
                $account = Account::firstOrCreate(
                    ['user_id' => $member->id, 'currency' => $credit->currency, 'type' => 'current'],
                    ['balance' => 0]
                );

                if ($account->status === 'Inactif') {
                    notyf()->error("Opération refusée. Le compte courant {$credit->currency} de ce membre est Inactif.");
                    return;
                }

                // Calcul du montant à payer
                if ($withInterest) {
                    $expectedAmount = floatval($repayment->expected_amount);
                    $penalityAmount = floatval($this->penality);
                    $amountToPay = round($expectedAmount + $penalityAmount, 3);
                } else {
                    $capitalRestant = floatval($repayment->credit->amount) / max(floatval($repayment->credit->installments), 1);
                    $amountToPay = round($capitalRestant, 3);
                }

                // Vérification du solde minimum (sauf si autorisé à tout retirer)
                $minBalance = ($credit->currency === 'USD') ? self::MIN_BALANCE_USD : self::MIN_BALANCE_CDF;
                if ($account->can_withdraw_all) {
                    $minBalance = 0;
                }

                if ((floatval($account->balance) - $amountToPay) < $minBalance) {
                    notyf()->error("Solde insuffisant. Le solde minimum obligatoire est de {$minBalance} {$credit->currency}.");
                    return;
                }

                // Débiter le compte membre
                $account->balance = floatval($account->balance) - $amountToPay;
                $account->save();

                // Mettre à jour le remboursement
                $repayment->paid_date = now()->format('Y-m-d');
                $repayment->paid_amount = floatval($amountToPay);
                $repayment->total_due = floatval($amountToPay);
                $repayment->penalty = floatval($this->penality);
                $repayment->is_paid = true;
                $repayment->save();

                // Vérifier si le crédit est entièrement remboursé
                if (!$credit->repayments()->where('is_paid', false)->exists()) {
                    $credit->is_paid = true;
                    $credit->save();
                }

                // Gestion de l'encaissement agent si paiement avec intérêt
                if ($withInterest) {
                    $agentAccount = AgentAccount::firstOrCreate(
                        ['user_id' => 95, 'currency' => $credit->currency],
                        ['balance' => 0]
                    );

                    //Crediter la caisse centrale
                    $penalityAccount = AgentAccount::firstOrCreate(
                        ['user_id' => 472, 'currency' => $credit->currency],
                        ['balance' => 0]
                    );

                    //$interestPart = floatval($repayment->credit->amount) * (floatval($credit->interest_rate) / 100);
                    if ($credit->credit_type === 'degressif') {

                        // Capital restant avant cette échéance
                        $remainingCapital = floatval($credit->amount);

                        foreach ($credit->repayments->sortBy('due_date') as $schedule) {

                            $currentInterest = round(
                                $remainingCapital * (floatval($credit->interest_rate) / 100),
                                2
                            );

                            $capitalPart = round(
                                floatval($schedule->expected_amount) - $currentInterest,
                                2
                            );

                            // Si c'est l'échéance actuelle → on récupère son intérêt
                            if ($schedule->id == $repayment->id) {
                                $interestPart = $currentInterest;
                                break;
                            }

                            // Déduire le capital pour passer à l’échéance suivante
                            $remainingCapital -= $capitalPart;
                        }

                    } else {

                        // Crédit constant
                        $interestPart = round(
                            floatval($credit->amount) *
                            (floatval($credit->interest_rate) / 100),
                            2
                        );
                    }

                    $penality = floatval($repayment->penalty);

                    $agentAccount->balance = floatval($agentAccount->balance) + ($interestPart);
                    $penalityAccount->balance = floatval($penalityAccount->balance) + ($penality);

                    $agentAccount->save();
                    $penalityAccount->save();

                    // Transaction agent
                    Transaction::create([
                        'agent_account_id' => $agentAccount->id,
                        'user_id' => 95,
                        'type' => 'encaissement_agent',
                        'currency' => $credit->currency,
                        'amount' => ($interestPart),
                        'balance_after' => $agentAccount->balance,
                        'description' => "Encaissement agent pour l’échéance #{$repayment->id} du client {$member->code} {$member->name} {$member->postnom}",
                    ]);

                    // Transaction agent
                    if ($penality > 0) {
                        Transaction::create([
                            'agent_account_id' => $penalityAccount->id,
                            'user_id' => 472,
                            'type' => 'encaissement_agent',
                            'currency' => $credit->currency,
                            'amount' => $penality,
                            'balance_after' => $penalityAccount->balance,
                            'description' => "Encaissement pénalité pour l’échéance #{$repayment->id} du client {$member->code} {$member->name} {$member->postnom}",
                        ]);
                    }
                }

                // Transaction client (débit)
                Transaction::create([
                    'account_id' => $account->id,
                    'user_id' => $member->id,
                    'type' => 'remboursement_de_credit',
                    'currency' => $credit->currency,
                    'amount' => $amountToPay,
                    'balance_after' => $account->balance,
                    'description' => "Remboursement manuel de l'échéance #{$repayment->id} pour le crédit #{$credit->id}",
                ]);

                // Journalisation
                UserLogHelper::log_user_activity(
                    action: 'remboursement_credit',
                    description: "Remboursement manuel de l'échéance #{$repayment->id} pour le crédit #{$credit->id} du membre {$member->code} {$member->name} {$member->postnom}, montant {$amountToPay} {$credit->currency}"
                );

                // Notification membre
                Notification::create([
                    'user_id' => $member->id,
                    'title' => 'Remboursement effectué',
                    'message' => "Votre échéance du {$repayment->due_date->format('d/m/Y')} a été remboursée manuellement.",
                    'read' => false,
                ]);

                // Notifier les utilisateurs concernés
                $usersToNotify = User::role(['Admin', 'Caissier', 'SUPER IT', 'Comptable'])->get();
                $notificationMessage = "Un remboursement de " . number_format($amountToPay, 2) . " {$credit->currency} a été effectué pour le membre {$member->name} {$member->postnom} ({$member->code}) par " . (Auth::user() ? Auth::user()->name . "." . Auth::user()->postnom : "Système") . ".";

                foreach ($usersToNotify as $notifyUser) {
                    Notification::create([
                        'user_id' => $notifyUser->id,
                        'title' => 'Remboursement automatique effectué',
                        'message' => $notificationMessage,
                        'read' => false,
                    ]);
                }

                $this->openModalConfirm = false;

                // ÉCRITURES COMPTABLES AUTOMATIQUES
                // try {
                //     $accountingService = app(\App\Services\AccountingService::class);

                //     // Calcul des parts (si paiement avec intérêts)
                //     $montantInteret = 0;
                //     $montantPenalite = 0;
                //     $montantCapital = $amountToPay;

                //     if ($withInterest) {
                //         $montantInteret = floatval($repayment->credit->amount) * (floatval($credit->interest_rate) / 100);
                //         $montantPenalite = floatval($repayment->penalty);
                //         $montantCapital = $amountToPay - $montantInteret - $montantPenalite;
                //     }

                //     // 1. Enregistrer le remboursement du capital
                //     if ($montantCapital > 0) {
                //         $accountingService->recordRepayment($repayment, $montantCapital);
                //     }

                //     // 2. Enregistrer les intérêts
                //     if ($montantInteret > 0) {
                //         $accountingService->recordInterest($credit, $montantInteret, $credit->currency);
                //     }

                //     // 3. Enregistrer les pénalités
                //     if ($montantPenalite > 0) {
                //         $accountingService->recordLatePenalty($credit, $montantPenalite, $credit->currency);
                //     }

                // } catch (\Exception $e) {
                //     \Illuminate\Support\Facades\Log::error("Erreur comptable remboursement crédit: " . $e->getMessage());
                // }

                notyf()->success(__('Échéance remboursée avec succès !'));
            });

            $this->updatedCreditId();

        } catch (\Throwable $e) {
            report($e);
            notyf()->error('Erreur lors du remboursement : ' . $e->getMessage());
        }
    }
}
