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
    public $applyInterest = true;

    /** Pénalité actuelle de l'échéance sélectionnée (affichage modal) */
    public $penality = 0;

    /** Montant saisi par l'agent pour le remboursement (partiel ou total) */
    public $paymentAmount = 0;

    /** Détails ventilés de l'échéance sélectionnée (pour l'affichage modal) */
    public $repaymentDetails = [];

    public $openModalConfirm = false;

    protected $rules = [
        'member_id'     => 'required|exists:users,id',
        'credit_id'     => 'required|exists:credits,id',
        'paymentAmount' => 'required|numeric|min:0.01',
    ];

    public function render()
    {
        return view('livewire.credit.manage-repayments');
    }

    public function mount()
    {
        Gate::authorize('afficher-credit', User::class);
    }

    public function updatedSearch()
    {
        $query = trim($this->search);
        if ($query !== '') {
            $this->results = User::query()
                ->where(function ($q) use ($query) {
                    $q->where('code',      'like', "%{$query}%")
                      ->orWhere('name',     'like', "%{$query}%")
                      ->orWhere('postnom',  'like', "%{$query}%")
                      ->orWhere('prenom',   'like', "%{$query}%")
                      ->orWhere('telephone','like', "%{$query}%");
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
            $this->search  = "{$user->name} {$user->postnom}";
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

    /**
     * Ouvre le modal de confirmation pour une échéance donnée.
     * Pré-calcule tous les soldes ventilés restants.
     */
    public function confirmRepayment($repaymentId)
    {
        $repayment = Repayment::findOrFail($repaymentId);

        $principalAmount = floatval($repayment->principal_amount ?? $repayment->expected_amount);
        $interestAmount  = floatval($repayment->interest_amount  ?? 0);

        $remainingPrincipal = max(0.0, $principalAmount - floatval($repayment->paid_principal));
        $remainingInterest  = max(0.0, $interestAmount  - floatval($repayment->paid_interest));
        $remainingPenalty   = max(0.0, floatval($repayment->penalty) - floatval($repayment->paid_penalty));
        $totalRemaining     = round($remainingPrincipal + $remainingInterest + $remainingPenalty, 2);

        $this->repaymentToPay    = $repaymentId;
        $this->penality          = round($remainingPenalty, 2);
        $this->paymentAmount     = $totalRemaining;
        $this->repaymentDetails  = [
            'remaining_principal' => $remainingPrincipal,
            'remaining_interest'  => $remainingInterest,
            'remaining_penalty'   => $remainingPenalty,
            'total_remaining'     => $totalRemaining,
            'currency'            => $repayment->credit->currency,
        ];
        $this->openModalConfirm = true;
    }

    /**
     * Effectue le remboursement.
     *
     * @param bool $withInterest  true  → allocation Pénalité → Intérêt → Capital
     *                            false → allocation directement sur le Capital restant
     */
    public function payRepayment($withInterest = true)
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
        ]);

        $repaymentId = $this->repaymentToPay;

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

                // Vérification du solde minimum
                $minBalance = ($credit->currency === 'USD') ? self::MIN_BALANCE_USD : self::MIN_BALANCE_CDF;
                if ($account->can_withdraw_all) {
                    $minBalance = 0;
                }

                $requestedAmount = round(floatval($this->paymentAmount), 3);
                $availableBalance = floatval($account->balance) - $minBalance;

                if ($availableBalance <= 0) {
                    notyf()->error("Solde insuffisant. Le solde minimum obligatoire est de {$minBalance} {$credit->currency}.");
                    return;
                }

                // Plafonner au disponible si nécessaire
                $amountToPay = min($requestedAmount, $availableBalance);

                // ----- Soldes restants -----
                $principalAmount    = floatval($repayment->principal_amount ?? $repayment->expected_amount);
                $interestAmount     = floatval($repayment->interest_amount  ?? 0);
                $remainingPrincipal = max(0.0, $principalAmount - floatval($repayment->paid_principal));
                $remainingInterest  = max(0.0, $interestAmount  - floatval($repayment->paid_interest));
                $remainingPenalty   = max(0.0, floatval($repayment->penalty) - floatval($repayment->paid_penalty));

                // ----- Allocation -----
                $paidPen = 0.0;
                $paidInt = 0.0;
                $paidPri = 0.0;

                if ($withInterest) {
                    // Ordre : Pénalité → Intérêt → Capital
                    $rem     = $amountToPay;
                    $paidPen = min($rem, $remainingPenalty); $rem -= $paidPen;
                    $paidInt = min($rem, $remainingInterest); $rem -= $paidInt;
                    $paidPri = min($rem, $remainingPrincipal);
                } else {
                    // Solder sans intérêt : allocation directe au capital restant
                    $paidPri = min($amountToPay, $remainingPrincipal);

                    // Si le capital restant est entièrement soldé, on annule (waive) l'intérêt et la pénalité restants
                    if ($paidPri >= $remainingPrincipal) {
                        $repayment->interest_amount = $repayment->paid_interest;
                        $repayment->penalty = $repayment->paid_penalty;
                        // On met à jour les variables locales pour le calcul de total_due ci-dessous
                        $interestAmount = floatval($repayment->interest_amount);
                    }
                }

                $totalPaid = round($paidPen + $paidInt + $paidPri, 3);

                if ($totalPaid <= 0) {
                    notyf()->error("Montant alloué nul. Vérifiez les soldes restants.");
                    return;
                }

                // Débiter le compte membre
                $account->balance = floatval($account->balance) - $totalPaid;
                $account->save();

                // ----- Transactions agent -----
                if ($withInterest && $paidInt > 0) {
                    $agentAccount = AgentAccount::firstOrCreate(
                        ['user_id' => 95, 'currency' => $credit->currency],
                        ['balance' => 0]
                    );
                    $agentAccount->balance = floatval($agentAccount->balance) + $paidInt;
                    $agentAccount->save();

                    Transaction::create([
                        'agent_account_id' => $agentAccount->id,
                        'user_id'          => 95,
                        'type'             => 'encaissement_agent',
                        'currency'         => $credit->currency,
                        'amount'           => $paidInt,
                        'balance_after'    => $agentAccount->balance,
                        'description'      => "Intérêt échéance #{$repayment->id} — client {$member->code} {$member->name} {$member->postnom}",
                    ]);
                }

                if ($withInterest && $paidPen > 0) {
                    $penalityAccount = AgentAccount::firstOrCreate(
                        ['user_id' => 472, 'currency' => $credit->currency],
                        ['balance' => 0]
                    );
                    $penalityAccount->balance = floatval($penalityAccount->balance) + $paidPen;
                    $penalityAccount->save();

                    Transaction::create([
                        'agent_account_id' => $penalityAccount->id,
                        'user_id'          => 472,
                        'type'             => 'encaissement_agent',
                        'currency'         => $credit->currency,
                        'amount'           => $paidPen,
                        'balance_after'    => $penalityAccount->balance,
                        'description'      => "Pénalité échéance #{$repayment->id} — client {$member->code} {$member->name} {$member->postnom}",
                    ]);
                }

                // Transaction débit client
                Transaction::create([
                    'account_id'    => $account->id,
                    'user_id'       => $member->id,
                    'type'          => 'remboursement_de_credit',
                    'currency'      => $credit->currency,
                    'amount'        => $totalPaid,
                    'balance_after' => $account->balance,
                    'description'   => "Remboursement " . ($withInterest ? "avec intérêts" : "capital uniquement") . " — échéance #{$repayment->id}, crédit #{$credit->id}",
                ]);

                // ----- Mise à jour de l'échéance -----
                $repayment->paid_penalty  = floatval($repayment->paid_penalty)  + $paidPen;
                $repayment->paid_interest = floatval($repayment->paid_interest) + $paidInt;
                $repayment->paid_principal = floatval($repayment->paid_principal) + $paidPri;
                $repayment->paid_amount   = floatval($repayment->paid_amount)   + $totalPaid;

                // Recalculer le total_due si pénalité a changé (sécurité)
                $repayment->total_due = round($principalAmount + $interestAmount + floatval($repayment->penalty), 3);

                $repayment->is_paid = ($repayment->paid_amount >= $repayment->total_due);
                if ($repayment->is_paid) {
                    $repayment->paid_date = now()->format('Y-m-d');
                }
                $repayment->save();

                // Vérifier si tout le crédit est remboursé
                if (!$credit->repayments()->where('is_paid', false)->exists()) {
                    $credit->is_paid = true;
                    $credit->save();
                }

                // Journalisation
                UserLogHelper::log_user_activity(
                    action: 'remboursement_credit',
                    description: "Remboursement " . ($withInterest ? "avec intérêts" : "capital seul") . " — échéance #{$repayment->id}, crédit #{$credit->id}, membre {$member->code} {$member->name} {$member->postnom}, montant {$totalPaid} {$credit->currency}"
                );

                // Notification membre
                Notification::create([
                    'user_id' => $member->id,
                    'title'   => 'Remboursement effectué',
                    'message' => "Un remboursement de " . number_format($totalPaid, 2) . " {$credit->currency} a été enregistré pour votre échéance du {$repayment->due_date->format('d/m/Y')}.",
                    'read'    => false,
                ]);

                $this->openModalConfirm = false;
                notyf()->success(__('Remboursement enregistré avec succès !'));
            });

            $this->updatedCreditId();

        } catch (\Throwable $e) {
            report($e);
            notyf()->error('Erreur lors du remboursement : ' . $e->getMessage());
        }
    }
}
