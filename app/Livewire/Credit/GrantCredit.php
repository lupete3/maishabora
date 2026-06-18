<?php

namespace App\Livewire\Credit;

use App\Helpers\UserLogHelper;
use App\Models\Notification;
use Livewire\Component;
use App\Models\User;
use App\Models\Account;
use App\Models\AgentAccount;
use App\Models\Credit;
use App\Models\Repayment;
use App\Models\MainCashRegister;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class GrantCredit extends Component
{
    public $member_id;
    public $currency = 'USD';
    public $amount = 0;
    public $interest_rate = 5.0; // %
    public $installments = 3;
    public $start_date;
    public $frequency = 'monthly'; // 'daily', 'monthly', 'weekly'
    public $repayment_type = 'degressif'; // 'constant', 'degressif'
    public $creditFrisFix = 3; // frais fixe de dossier
    public $mutuelle_rate = 1.0; // frais mutuelle par défaut (1%)

    const MUTUELLE_ACCOUNT_USER_ID = 4; // Compte mutuelle (ID à ajuster selon le besoin)

    public $description = '';

    public $members = [];
    public $search;
    public $results = [];

    public $agent;
    public $resultsAgent;
    public $agent_id;

    public $disbursing_agent;
    public $resultsDisbursingAgent = [];
    public $disbursing_agent_id;

    public $showConfirmModal = false;
    public $creditSummary = [];
    public $hasActiveCredit = false;


    protected $rules = [
        'member_id' => 'required|exists:users,id',
        'currency' => 'required|in:USD,CDF',
        'amount' => 'required|numeric|min:0.01',
        'interest_rate' => 'required|numeric|min:0|max:100',
        'installments' => 'required|integer|min:1',
        'start_date' => 'required|date',
        'frequency' => 'required|in:daily,monthly,weekly',
        'repayment_type' => 'required|in:constant,degressif',
        'creditFrisFix' => 'required|numeric|min:0.0|max:100',
        'mutuelle_rate' => 'required|numeric|min:0.0|max:100',
        'disbursing_agent_id' => 'required|exists:users,id',
    ];

    public function mount()
    {
        $user = Auth::user();
        Gate::authorize('ajouter-credit', User::class);

        $this->members = User::where('role', 'membre')->get();
        $this->start_date = Carbon::today()->addMonth()->toDateString();
    }

    public function updatedSearch()
    {
        $query = trim($this->search);

        if ($query !== '') {
            // Découper la recherche en plusieurs mots
            $terms = preg_split('/\s+/', $query);

            $this->results = User::where('role', 'membre')
                ->where(function ($mainQuery) use ($terms) {
                    foreach ($terms as $term) {
                        $mainQuery->where(function ($q) use ($term) {
                            $q->where('code', 'like', "%{$term}%")
                                ->orWhere('name', 'like', "%{$term}%")
                                ->orWhere('postnom', 'like', "%{$term}%")
                                ->orWhere('prenom', 'like', "%{$term}%")
                                ->orWhere('telephone', 'like', "%{$term}%");
                        });
                    }
                })
                ->limit(10)
                ->get(['id', 'code', 'name', 'postnom', 'prenom'])
                ->toArray();
        } else {
            $this->results = [];
        }
    }

    public function updatedAgent()
    {
        $query = trim($this->agent);

        if ($query !== '') {
            // Découper la recherche en plusieurs mots
            $terms = preg_split('/\s+/', $query);

            $this->resultsAgent = User::where('role', '!=', 'membre') // ou commenter selon ton besoin
                ->where(function ($mainQuery) use ($terms) {
                    foreach ($terms as $term) {
                        $mainQuery->where(function ($q) use ($term) {
                            $q->where('code', 'like', "%{$term}%")
                                ->orWhere('name', 'like', "%{$term}%")
                                ->orWhere('postnom', 'like', "%{$term}%")
                                ->orWhere('prenom', 'like', "%{$term}%")
                                ->orWhere('telephone', 'like', "%{$term}%");
                        });
                    }
                })
                ->limit(10)
                ->get(['id', 'code', 'name', 'postnom', 'prenom'])
                ->toArray();
        } else {
            $this->resultsAgent = [];
        }
    }

    public function selectResult(int $id)
    {
        $user = User::find($id);
        if ($user) {
            $this->search = "{$user->name} {$user->postnom}";
            $this->results = [];

            $this->member_id = $user->id;
            $this->dispatch('userSelected', $user->id);
        }
    }

    public function selectResultAgent(int $id)
    {
        $user = User::find($id);
        if ($user) {
            $this->agent_id = "{$user->name} {$user->postnom}";
            $this->resultsAgent = [];

            $this->agent_id = $user->id;
            $this->dispatch('userSelected', $user->id);
        }
    }
    public function selectResultDisbursingAgent(int $id)
    {
        $user = User::find($id);
        if ($user) {
            $this->disbursing_agent = "{$user->name} {$user->postnom}";
            $this->resultsDisbursingAgent = [];

            $this->disbursing_agent_id = $user->id;
        }
    }

    public function updatedDisbursingAgent()
    {
        $query = trim($this->disbursing_agent);

        if ($query !== '') {
            $terms = preg_split('/\s+/', $query);

            $this->resultsDisbursingAgent = User::whereIn('role', ['Caissier', 'Receptionniste'])
                ->where(function ($mainQuery) use ($terms) {
                    foreach ($terms as $term) {
                        $mainQuery->where(function ($q) use ($term) {
                            $q->where('code', 'like', "%{$term}%")
                                ->orWhere('name', 'like', "%{$term}%")
                                ->orWhere('postnom', 'like', "%{$term}%")
                                ->orWhere('prenom', 'like', "%{$term}%")
                                ->orWhere('telephone', 'like', "%{$term}%");
                        });
                    }
                })
                ->limit(10)
                ->get(['id', 'code', 'name', 'postnom', 'prenom'])
                ->toArray();
        } else {
            $this->resultsDisbursingAgent = [];
        }
    }

    public function submit()
    {
        $this->validate();

        DB::beginTransaction();
        try {
            $member = User::findOrFail($this->member_id);

            $account = Account::where('user_id', $this->member_id)
                ->where('currency', $this->currency)
                ->where('type', 'current')
                ->lockForUpdate()
                ->first();

            if (!$account) {
                DB::rollBack();
                notyf()->error("Le membre ne possède pas de compte courant en {$this->currency}. Veuillez d'abord lui créer ce compte.");
                return;
            }

            if ($account->status === 'Inactif') {
                DB::rollBack();
                notyf()->error("Opération refusée. Le compte courant {$this->currency} de ce membre est Inactif.");
                return;
            }

            $mainCash = MainCashRegister::where('currency', $this->currency)
                ->lockForUpdate()
                ->firstOrCreate(['currency' => $this->currency], ['balance' => 0]);

            $creditFrisFix = round($this->amount * ($this->creditFrisFix / 100), 2);
            $mutuelleAmount = round($this->amount * ($this->mutuelle_rate / 100), 2);
            $totalFees = $creditFrisFix + $mutuelleAmount;

            // Validation des soldes
            if ($account->balance < $totalFees) {
                DB::rollBack();
                notyf()->error(__('Solde insuffisant dans le compte client pour payer les frais du dossier et la mutuelle'));
                return;
            }

            if ($mainCash->balance < $this->amount) {
                DB::rollBack();
                notyf()->error(__('Solde insuffisant dans la caisse centrale.'));
                return;
            }

            // Déduction des frais (commission et mutuelle) du client
            $account->balance -= $totalFees;
            $account->save();

            // Enregistrement de la transaction pour les frais de dossier
            Transaction::create([
                'account_id' => $account->id,
                'user_id' => $member->id,
                'type' => 'commission_credit',
                'currency' => $this->currency,
                'amount' => $creditFrisFix,
                'balance_after' => $account->balance + $mutuelleAmount,
                'description' => "Frais de commission du dossier du credit. Montant: {$creditFrisFix} {$this->currency} octroyé à {$member->name} {$member->postnom}",
            ]);

            // Enregistrement de la transaction pour les frais de mutuelle
            Transaction::create([
                'account_id' => $account->id,
                'user_id' => $member->id,
                'type' => 'frais_mutuelle',
                'currency' => $this->currency,
                'amount' => $mutuelleAmount,
                'balance_after' => $account->balance,
                'description' => "Frais de mutuelle (1% par defaut). Montant: {$mutuelleAmount} {$this->currency} pour le credit octroyé à {$member->name} {$member->postnom}",
            ]);

            // Transfert du montant du crédit de la caisse centrale au compte du client
            $mainCash->balance -= $this->amount;
            $account->balance += $this->amount;
            $mainCash->save();
            $account->save();

            // Création de l'enregistrement du crédit
            $credit = Credit::create([
                'user_id' => $member->id,
                'account_id' => $account->id,
                'currency' => $this->currency,
                'amount' => $this->amount,
                'interest_rate' => $this->interest_rate,
                'installments' => $this->installments,
                'start_date' => $this->start_date,
                'due_date' => Carbon::parse($this->start_date),
                'credit_type' => $this->repayment_type,
                'frais_credit' => $this->creditFrisFix,
                'mutuelle' => $mutuelleAmount,
                'repayment_type' => $this->frequency,
                'is_paid' => false,
                'agent_id' => $this->agent_id,
            ]);

            // Enregistrement des transactions pour l'octroi du crédit
            Transaction::create([
                'user_id' => $member->id,
                'type' => 'octroi_de_credit',
                'currency' => $this->currency,
                'amount' => $this->amount,
                'balance_after' => $account->balance,
                'account_id' => $account->id,
                'description' => (isset($this->description) && strlen($this->description) > 10) ? $this->description : "Crédit octroyé à {$member->name} {$member->postnom}",
            ]);

            Transaction::create([
                'account_id' => NULL,
                'user_id' => Auth::user()->id,
                'type' => 'octroi_de_credit_client',
                'currency' => $credit->currency,
                'amount' => $this->amount,
                'balance_after' => $mainCash->balance,
                'description' => (isset($this->description) && strlen($this->description) > 10) ? $this->description : "Crédit octroyé à {$member->name} {$member->postnom}",
            ]);

            // Envoyer les frais de commission du crédit au compte 94
            $commissionCreditAccount = AgentAccount::firstOrCreate(
                ['user_id' => 94, 'currency' => $credit->currency],
                ['balance' => 0]
            );
            $commissionCreditAccount->balance += $creditFrisFix;
            $commissionCreditAccount->save();

            // Envoyer les frais de mutuelle au compte MUTUELLE_ACCOUNT_USER_ID
            $mutuelleAccount = AgentAccount::firstOrCreate(
                ['user_id' => self::MUTUELLE_ACCOUNT_USER_ID, 'currency' => $credit->currency],
                ['balance' => 0]
            );
            $mutuelleAccount->balance += $mutuelleAmount;
            $mutuelleAccount->save();

            // Envoyer le montant du crédit au compte du caissier sélectionné pour attente du retrait
            $cassisierAccount = AgentAccount::firstOrCreate(
                ['user_id' => $this->disbursing_agent_id, 'currency' => $credit->currency],
                ['balance' => 0]
            );
            $cassisierAccount->balance += $this->amount;
            $cassisierAccount->save();

            // Log de l'activité utilisateur
            UserLogHelper::log_user_activity(
                action: 'octroi_de_credit',
                description: "Crédit octroyé à {$member->name} {$member->postnom} ({$member->code}), montant total {$this->amount} {$this->currency}"
            );

            // Enregistrement de la transaction pour commission crédit
            Transaction::create([
                'account_id' => null,
                'agent_account_id' => $commissionCreditAccount->id,
                'user_id' => 94,
                'type' => 'commission_credit',
                'currency' => $credit->currency,
                'amount' => $creditFrisFix,
                'balance_after' => $commissionCreditAccount->balance,
                'description' => "Frais de commission du dossier du credit #{$credit->id} - Montant: {$creditFrisFix} {$credit->currency} octroyé à {$member->name} {$member->postnom}",
            ]);

            // Enregistrement de la transaction pour commission mutuelle
            Transaction::create([
                'account_id' => null,
                'agent_account_id' => $mutuelleAccount->id,
                'user_id' => self::MUTUELLE_ACCOUNT_USER_ID,
                'type' => 'commission_mutuelle',
                'currency' => $credit->currency,
                'amount' => $mutuelleAmount,
                'balance_after' => $mutuelleAccount->balance,
                'description' => "Frais de mutuelle du dossier du credit #{$credit->id} - Montant: {$mutuelleAmount} {$credit->currency} octroyé à {$member->name} {$member->postnom}",
            ]);

            // Enregistrement de la transaction pour commission crédit au caissier
            Transaction::create([
                'account_id' => null,
                'agent_account_id' => $cassisierAccount->id,
                'user_id' => $this->disbursing_agent_id,
                'type' => 'frais_credit_pour_retrait',
                'currency' => $credit->currency,
                'amount' => $this->amount,
                'balance_after' => $cassisierAccount->balance,
                'description' => "Frais à retirer du dossier du credit #{$credit->id} - Montant: {$this->amount} {$credit->currency} du client {$member->name} {$member->postnom}",
            ]);

            // Notifier les utilisateurs concernés
            $usersToNotify = User::role(['Admin', 'Caissier', 'SUPER IT', 'Comptable'])->get();
            $notificationMessage = "Un crédit de " . number_format($this->amount, 2) . " {$this->currency} a été octroyé à {$member->name} {$member->postnom} ({$member->code}) par " . Auth::user()->name . " " . Auth::user()->postnom . ".";

            foreach ($usersToNotify as $notifyUser) {
                Notification::create([
                    'user_id' => $notifyUser->id,
                    'title' => 'Crédit octroyé',
                    'message' => $notificationMessage,
                    'read' => false,
                ]);
            }

            // Définition de l'échéancier selon le type de remboursement
            $startDate = Carbon::parse($this->start_date);
            $currentDate = $startDate->copy();
            $lastDueDate = null;

            if ($this->repayment_type === 'degressif') {
                // Remboursement à capital constant (dégressif)
                $capitalPart = $this->amount / $this->installments;
                $remainingCapital = $this->amount;

                for ($i = 0; $i < $this->installments; $i++) {
                    $interest = $remainingCapital * ($this->interest_rate / 100);
                    $installmentTotal = $capitalPart + $interest;

                    // Ajustement pour la dernière échéance pour éviter les erreurs d'arrondi
                    if ($i === $this->installments - 1) {
                        $installmentTotal = $remainingCapital + $interest;
                    }

                    Repayment::create([
                        'credit_id' => $credit->id,
                        'due_date' => $currentDate->toDateString(),
                        'expected_amount' => round($installmentTotal, 2),
                        'total_due' => round($installmentTotal, 2),
                    ]);

                    $remainingCapital -= $capitalPart;

                    // Incrémentation de la date
                    if ($this->frequency === 'daily') {
                        $currentDate->addDay();
                        if ($currentDate->isSunday()) {
                            $currentDate->addDay();
                        }
                    } elseif ($this->frequency === 'weekly') {
                        $currentDate->addWeek();
                    } else {
                        $currentDate->addMonth();
                    }
                }
                $lastDueDate = $currentDate->copy();
            } else {
                // --- Calculs initiaux basés sur la logique de l'intérêt forfaitaire constant ---

                // 1. Calcul de la part de capital constante (Arrondie une fois)
                $monthlyCapital = round($this->amount / $this->installments, 2);
                // Ex: 400 / 3 = 133.33 (si installments=3) ou 400 / 4 = 100.00 (si installments=4)

                // 2. Calcul de l'INTÉRÊT FORFAITAIRE CONSTANT par mensualité
                // On applique le taux ($this->interest_rate / 100) au capital initial ($this->amount)
                $monthlyInterest = round($this->amount * ($this->interest_rate / 100), 2);
                // Ex: 400 * 0.05 = 20.00 OU 500 * 0.05 = 25.00

                // 3. Calcul de la mensualité constante (annuité) pour toutes les périodes sauf la dernière
                $annuity_flat = $monthlyCapital + $monthlyInterest;
                // Ex: 133.33 + 20.00 = 153.33 (si 400/3) OU 100.00 + 20.00 = 120.00 (si 400/4)
                // Ex: 125.00 + 25.00 = 150.00 (si 500/4)

                $remainingCapital = $this->amount;

                for ($i = 0; $i < $this->installments; $i++) {

                    $interest = $monthlyInterest; // Intérêt forfaitaire constant

                    // La part de capital est la part constante, sauf à la dernière échéance
                    $capitalPart = $monthlyCapital;

                    // La mensualité (annuity) est la valeur constante par défaut
                    $annuity = $annuity_flat;

                    // --- AJUSTEMENT pour la dernière échéance ---
                    if ($i === $this->installments - 1) {
                        // La partie capital doit égaler le capital restant pour solder le crédit (pour corriger les erreurs d'arrondi)
                        $capitalPart = $remainingCapital;
                        // La mensualité est recalculée avec le capital restant exact et l'intérêt fixe
                        $annuity = $capitalPart + $interest;
                    }

                    Repayment::create([
                        'credit_id' => $credit->id,
                        'due_date' => $currentDate->toDateString(),
                        'expected_amount' => round($annuity, 2),
                        'total_due' => round($annuity, 2),
                    ]);

                    // Déduction du capital remboursé
                    $remainingCapital -= $capitalPart;

                    // Incrémentation de la date
                    if ($this->frequency === 'daily') {
                        $currentDate->addDay();
                        if ($currentDate->isSunday()) {
                            $currentDate->addDay();
                        }
                    } elseif ($this->frequency === 'weekly') {
                        $currentDate->addWeek();
                    } else {
                        $currentDate->addMonth();
                    }
                }
                $lastDueDate = $currentDate->copy();
            }

            // Mise à jour de la date d'échéance finale
            $credit->due_date = $lastDueDate ? $lastDueDate->toDateString() : $credit->start_date;
            $credit->save();

            // ÉCRITURE COMPTABLE AUTOMATIQUE - DÉCAISSEMENT CRÉDIT
            // try {
            //     $accountingService = app(\App\Services\AccountingService::class);
            //     $accountingService->recordCreditDisbursement($credit);
            // } catch (\Exception $e) {
            //     \Illuminate\Support\Facades\Log::error("Erreur comptable décaissement crédit: " . $e->getMessage());
            // }

            DB::commit();

            notyf()->success(__('Crédit octroyé avec succès !'));
            $this->reset(['amount', 'description']);
            $this->dispatch('facture-validee', url: route('credit.receipt.generate', ['id' => $credit->id]));
            return;
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);
            notyf()->error(__('Une erreur est survenue lors de l’octroi du crédit.'));
        }
    }

    public function confirmGrant()
    {
        $this->validate();

        $this->hasActiveCredit = Credit::where('user_id', $this->member_id)
            ->where('is_paid', false)
            ->exists();

        // Calcul du montant des frais et total à rembourser (pour affichage)
        $creditFrisFix = round($this->amount * ($this->creditFrisFix / 100), 2);
        $mutuelleAmount = round($this->amount * ($this->mutuelle_rate / 100), 2);
        $interestAmount = round(($this->amount * $this->interest_rate / 100), 2);
        $totalToRepay = $this->amount + $interestAmount;

        $member = User::find($this->member_id);

        $this->creditSummary = [
            'membre' => $member ? "{$member->name} {$member->postnom}" : 'Inconnu',
            'montant' => "{$this->amount} {$this->currency}",
            'taux' => "{$this->interest_rate} %",
            'frais' => "{$creditFrisFix} {$this->currency}",
            'mutuelle' => "{$mutuelleAmount} {$this->currency}",
            'total' => "{$totalToRepay} {$this->currency}",
            'debut' => $this->start_date,
            'echeances' => "{$this->installments} × {$this->frequency}",
            'type' => ucfirst($this->repayment_type),
            'agent' => User::find($this->agent_id) ? User::find($this->agent_id)->name . ' ' . User::find($this->agent_id)->postnom : 'Inconnu',
            'disbursing_agent' => User::find($this->disbursing_agent_id) ? User::find($this->disbursing_agent_id)->name . ' ' . User::find($this->disbursing_agent_id)->postnom : 'Inconnu',
            'description' => (isset($this->description) && strlen($this->description) > 10) ? $this->description : "Crédit octroyé à {$member->name} {$member->postnom}",
        ];

        // Ouvre le modal
        $this->showConfirmModal = true;
    }

    public function confirmSubmit()
    {
        $this->showConfirmModal = false;
        $this->submit(); // Exécute la logique principale d’octroi
    }

    public function render()
    {
        return view('livewire.credit.grant-credit', [
            'members' => $this->members,
        ]);
    }
}
