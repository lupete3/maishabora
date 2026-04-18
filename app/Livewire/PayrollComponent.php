<?php

namespace App\Livewire;

use App\Helpers\UserLogHelper;
use App\Models\Account;
use App\Models\AgentAccount;
use App\Models\MainCashRegister;
use App\Models\Notification;
use App\Models\Payroll;
use App\Models\Salary;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class PayrollComponent extends Component
{
    use WithPagination;

    public $user_id;
    public $salary_amount;
    public $currency = 'CDF';
    public $period;
    public $search = '';
    public $searchAgent = '';
    public $perPage = 10;
    public $perPageSalary = 3;
    public $filterType = 'month';
    public $startDate;
    public $endDate;
    public $members = [];
    public $results = [];
    public $resultsAgent = [];

    public $caisseSearch = '';
    public $resultsCaisse = [];
    public $caisse_id;

    // Properties for confirmation modal
    public $showingConfirmationModal = false;
    public $selectedUserName = '';
    public $selectedSalaryAmount = 0;
    public $showingCancellationModal = false;
    public $selectedPayrollId;

    const CHARGE_ACCOUNT_USER_ID = 452;
    const CAISSIER_ACCOUNT_USER_ID = 2;
    const RETAINED_ACCOUNT_USER_ID = 328;

    protected $paginationTheme = 'bootstrap';

    public function updatedSearch()
    {
        $query = trim($this->search);
        if ($query !== '') {
            $this->results = User::query()
                ->where(function ($q) use ($query) {
                    $q->where('role', 'membre')
                        ->where('code', 'like', "%{$query}%")
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
    public function updatedSearchagent()
    {
        $query = trim($this->searchAgent);
        if ($query !== '') {
            $this->resultsAgent = User::query()
                ->where(function ($q) use ($query) {
                    $q->where('role', 'membre')
                        ->where('code', 'like', "%{$query}%")
                        ->orWhere('name', 'like', "%{$query}%")
                        ->orWhere('postnom', 'like', "%{$query}%")
                        ->orWhere('prenom', 'like', "%{$query}%")
                        ->orWhere('telephone', 'like', "%{$query}%");
                })
                ->whereHas('salaries', fn($q) => $q->where('currency', $this->currency))
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

            $this->user_id = $user->id;
            $this->dispatch('userSelected', $user->id);
        }
    }
    public function selectResultAgent(int $id)
    {
        $user = User::find($id);
        if ($user) {
            $this->searchAgent = "{$user->name} {$user->postnom}";
            $this->resultsAgent = [];

            $this->user_id = $user->id;
            $this->dispatch('userSelected', $user->id);
        }
    }

    public function updatedCaisseSearch()
    {
        $query = trim($this->caisseSearch);
        if ($query !== '') {
            $this->resultsCaisse = User::role(['Caissier', 'Receptionniste'])
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
            $this->resultsCaisse = [];
        }
    }

    public function selectResultCaisse(int $id)
    {
        $user = User::find($id);
        if ($user) {
            $this->caisseSearch = "{$user->name} {$user->postnom}";
            $this->resultsCaisse = [];
            $this->caisse_id = $user->id;
        }
    }

    public function setSalary()
    {
        Gate::authorize('ajouter-salaire', User::class);

        $this->validate([
            'user_id' => 'required|exists:users,id',
            'salary_amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:CDF,USD',
        ]);

        // On met à jour OU crée un seul salaire par agent (quelle que soit la devise)
        Salary::updateOrCreate(
            ['user_id' => $this->user_id],
            [
                'amount' => $this->salary_amount,
                'currency' => $this->currency,
            ]
        );

        UserLogHelper::log_user_activity(
            action: 'attribuer_salaire',
            description: "Attribution du salaire de {$this->salary_amount} {$this->currency} à l’agent ID:{$this->user_id}"
        );

        notyf()->success('Salaire attribué à l’agent.');
        $this->reset(['user_id', 'salary_amount', 'currency', 'search', 'results']);
    }
    public function confirmPayment($userId)
    {
        Gate::authorize('ajouter-paye', User::class);

        $this->user_id = $userId;
        $user = User::find($userId);
        if (!$user) {
            notyf()->error('Agent non trouvé.');
            return;
        }

        $salary = Salary::where('user_id', $userId)->where('currency', $this->currency)->first();
        if (!$salary) {
            notyf()->error('Salaire non configuré pour cet agent dans cette devise.');
            return;
        }

        if (!$this->caisse_id) {
            notyf()->error('Veuillez sélectionner une caisse (caissier) pour le retrait.');
            return;
        }

        $this->selectedUserName = "{$user->name} {$user->postnom}";
        $this->selectedSalaryAmount = $salary->amount;
        $this->showingConfirmationModal = true;
    }

    public function closeConfirmationModal()
    {
        $this->showingConfirmationModal = false;
        $this->showingCancellationModal = false;
    }

    public function confirmCancellation($payrollId)
    {
        Gate::authorize('annuler-paye', User::class);
        $this->selectedPayrollId = $payrollId;
        $this->showingCancellationModal = true;
    }

    public function paySalary($userId)
    {
        Gate::authorize('ajouter-paye', User::class);

        try {
            $this->processSalaryPayment($userId);
            $this->closeConfirmationModal();
        } catch (\Exception $e) {
            notyf()->error('Erreur lors du paiement du salaire');
        }
    }

    protected function processSalaryPayment($userId)
    {
        try {
            DB::beginTransaction();

            $this->handleSalaryPaymentTransaction($userId);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
            //notyf()->error('Erreur lors du paiement du salaire');
        }
    }
    protected function handleSalaryPaymentTransaction($userId)
    {
        DB::transaction(function () use ($userId) {
            $salary = Salary::where('user_id', $userId)->where('currency', $this->currency)->firstOrFail();
            $mainCash = MainCashRegister::where('currency', $this->currency)->firstOrFail();

            $retenuSalaire = round($salary->amount * (10 / 100), 2);

            if ($mainCash->balance < $salary->amount) {
                notyf()->error('Solde insuffisant dans la caisse centrale.');
                return;
            }

            // Débit caisse centrale
            $mainCash->decrement('balance', $salary->amount);

            Transaction::create([
                'user_id' => Auth::id(),
                'type' => 'paie_sortant',
                'currency' => $this->currency,
                'amount' => $salary->amount,
                'balance_after' => $mainCash->balance,
                'description' => "Paiement salaire agent ID:$userId",
            ]);

            // Crédit compte agent
            $account = Account::firstOrCreate(
                ['user_id' => $userId, 'currency' => $this->currency, 'type' => 'current'],
                ['balance' => 0]
            );

            // Crédit compte agent
            $accountRetenuSalaire = Account::firstOrCreate(
                ['user_id' => self::RETAINED_ACCOUNT_USER_ID, 'currency' => $this->currency, 'type' => 'current'],
                ['balance' => 0]
            );

            // Envoyer le montant du crédit au compte du caissier choisi pour attente du retrait
            $cassisierAccount = AgentAccount::firstOrCreate(
                ['user_id' => $this->caisse_id, 'currency' => $this->currency],
                ['balance' => 0]
            );

            // Création du compte de charge salaire
            $chargeSalaire = AgentAccount::firstOrCreate(
                ['user_id' => self::CHARGE_ACCOUNT_USER_ID, 'currency' => $this->currency],
                ['balance' => 0]
            );

            $amount = $salary->amount - $retenuSalaire;

            $account->increment('balance', $amount);
            $accountRetenuSalaire->increment('balance', $retenuSalaire);
            $cassisierAccount->increment('balance', $amount);
            $chargeSalaire->increment('balance', $amount);

            // Création de la transaction de paiement agent
            Transaction::create([
                'user_id' => $userId,
                'account_id' => $account->id,
                'type' => 'paie_entrant',
                'currency' => $this->currency,
                'amount' => $amount,
                'balance_after' => $account->balance,
                'description' => 'Salaire reçu',
            ]);

            // Création de la transaction de retenue sur salaire
            Transaction::create([
                'user_id' => $userId,
                'account_id' => $accountRetenuSalaire->id,
                'type' => 'paie_entrant',
                'currency' => $this->currency,
                'amount' => $retenuSalaire,
                'balance_after' => $accountRetenuSalaire->balance,
                'description' => 'Retenue sur salaire',
            ]);

            // Enregistrer dans l’historique payroll
            $payroll = Payroll::create([
                'user_id' => $userId,
                'salary_id' => $salary->id,
                'agent_id' => $this->caisse_id,
                'currency' => $this->currency,
                'amount' => $salary->amount,
                'period' => $this->period ?? now()->format('Y-m'),
                'status' => 'paid',
            ]);

            // Enregistrement de la transaction pour paiment salaire au caissier
            Transaction::create([
                'account_id' => null,
                'agent_account_id' => $cassisierAccount->id,
                'user_id' => $this->caisse_id,
                'type' => 'salaire_pour_retrait',
                'currency' => $this->currency,
                'amount' => $amount,
                'balance_after' => $cassisierAccount->balance,
                'description' => "Frais à retirer du salaire #{$payroll->id} de l'agent {$salary->user->name} {$salary->user->postnom}",
            ]);

            Transaction::create([
                'account_id' => null,
                'agent_account_id' => $chargeSalaire->id,
                'user_id' => self::CHARGE_ACCOUNT_USER_ID,
                'type' => 'charge_salaire',
                'currency' => $this->currency,
                'amount' => $amount,
                'balance_after' => $chargeSalaire->balance,
                'description' => "Charge salaire #{$payroll->id} de l'agent {$salary->user->name} {$salary->user->postnom}",
            ]);

            // Notifier les utilisateurs concernés
            $usersToNotify = User::role(['Admin', 'Caissier', 'SUPER IT', 'Comptable'])->get();
            $notificationMessage = "Un paiement de salaire de " . number_format($amount, 2) . " {$this->currency} a été effectué pour agent {$salary->user->name} {$salary->user->postnom} ({$salary->user->code}) par " . Auth::user()->name . "." . Auth::user()->postnom . ".";

            foreach ($usersToNotify as $notifyUser) {
                Notification::create([
                    'user_id' => $notifyUser->id,
                    'title' => 'Paiement de salaire',
                    'message' => $notificationMessage,
                    'read' => false,
                ]);
            }

            UserLogHelper::log_user_activity(
                action: 'paiement_salaire',
                description: "Paiement du salaire de {$salary->amount} {$this->currency} à l’agent ID:$userId"
            );

            Notification::create([
                'user_id' => $userId,
                'title' => 'Paiement de salaire',
                'message' => "Votre salaire de {$amount} {$this->currency} pour la période {$this->period} a été payé.",
                'read' => false,
            ]);

            $this->reset(['user_id', 'caisse_id', 'caisseSearch', 'searchAgent']);
            notyf()->success('Salaire payé avec succès.');
        });
    }

    public function cancelPayment()
    {
        Gate::authorize('annuler-paye', User::class);

        try {
            DB::beginTransaction();

            $payroll = Payroll::findOrFail($this->selectedPayrollId);
            if ($payroll->status === 'cancelled') {
                notyf()->error('Ce paiement est déjà annulé.');
                return;
            }

            $userId = $payroll->user_id;
            $currency = $payroll->currency;
            $totalAmount = $payroll->amount;
            $retenuAmount = round($totalAmount * (10 / 100), 2);
            $netAmount = $totalAmount - $retenuAmount;

            // 1. Inversion Caisse Centrale
            $mainCash = MainCashRegister::where('currency', $currency)->firstOrFail();
            $mainCash->increment('balance', $totalAmount);

            Transaction::create([
                'user_id' => Auth::id(),
                'type' => 'annulation_paie_salaire',
                'currency' => $currency,
                'amount' => $totalAmount,
                'balance_after' => $mainCash->balance,
                'description' => "Annulation paiement salaire #{$payroll->id} de l'agent ID:$userId",
            ]);

            // 2. Inversion Compte Agent
            $account = Account::where('user_id', $userId)->where('currency', $currency)->where('type', 'current')->first();
            if ($account) {
                $account->decrement('balance', $netAmount);
                Transaction::create([
                    'user_id' => $userId,
                    'account_id' => $account->id,
                    'type' => 'annulation_paie',
                    'currency' => $currency,
                    'amount' => $netAmount,
                    'balance_after' => $account->balance,
                    'description' => "Inversion salaire reçu (Annulation #{$payroll->id})",
                ]);
            }

            // 3. Inversion Compte Retenue
            $retainedAccount = Account::where('user_id', self::RETAINED_ACCOUNT_USER_ID)->where('currency', $currency)->where('type', 'current')->first();
            if ($retainedAccount) {
                $retainedAccount->decrement('balance', $retenuAmount);
                Transaction::create([
                    'user_id' => $userId,
                    'account_id' => $retainedAccount->id,
                    'type' => 'annulation_paie',
                    'currency' => $currency,
                    'amount' => $retenuAmount,
                    'balance_after' => $retainedAccount->balance,
                    'description' => "Inversion retenue sur salaire (Annulation #{$payroll->id})",
                ]);
            }

            // 4. Inversion Compte Caissier et Charge
            $caisseId = $payroll->agent_id ?? self::CAISSIER_ACCOUNT_USER_ID;
            $caissierAccount = AgentAccount::where('user_id', $caisseId)->where('currency', $currency)->first();
            if ($caissierAccount) {
                $caissierAccount->decrement('balance', $netAmount);
                Transaction::create([
                    'agent_account_id' => null,
                    'user_id' => $caisseId,
                    'type' => 'annulation_paie',
                    'currency' => $currency,
                    'amount' => $netAmount,
                    'balance_after' => $caissierAccount->balance,
                    'description' => "Inversion provision retrait salaire (Annulation #{$payroll->id})",
                ]);
            }

            $chargeAccount = AgentAccount::where('user_id', self::CHARGE_ACCOUNT_USER_ID)->where('currency', $currency)->first();
            if ($chargeAccount) {
                $chargeAccount->decrement('balance', $netAmount);
                Transaction::create([
                    'agent_account_id' => null,
                    'user_id' => self::CHARGE_ACCOUNT_USER_ID,
                    'type' => 'annulation_paie',
                    'currency' => $currency,
                    'amount' => $netAmount,
                    'balance_after' => $chargeAccount->balance,
                    'description' => "Inversion charge salaire (Annulation #{$payroll->id})",
                ]);
            }

            // 5. Mise à jour du statut
            $payroll->update(['status' => 'cancelled']);

            UserLogHelper::log_user_activity(
                action: 'annulation_paiement_salaire',
                description: "Annulation du paiement de salaire #{$payroll->id} de {$totalAmount} {$currency} pour l'agent ID:$userId"
            );

            DB::commit();
            $this->closeConfirmationModal();
            notyf()->success('Paiement annulé avec succès.');
        } catch (\Exception $e) {
            DB::rollBack();
            notyf()->error("Erreur lors de l'annulation: " . $e->getMessage());
        }
    }

    public function exportPayslip($payrollId)
    {
        $payroll = Payroll::with('user')->findOrFail($payrollId);

        $pdf = Pdf::loadView('receipts.payslip', compact('payroll'))
            ->setPaper('A5', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'bulletin_paie_' . $payrollId . '.pdf');
    }

    public function editSalary($salaryId)
    {
        Gate::authorize('modifier-salaire', User::class);

        $salary = Salary::findOrFail($salaryId);
        $this->user_id = $salary->user_id;
        $this->salary_amount = $salary->amount;
        $this->currency = $salary->currency;
    }

    public function removeSalary($salaryId)
    {
        Gate::authorize('supprimer-salaire', User::class);

        $salary = Salary::findOrFail($salaryId);
        $salary->delete();
        notyf()->success('Salaire supprimé avec succès.');
    }

    public function updatedFilterType()
    {
        $this->resetPage();
        if ($this->filterType !== 'range') {
            $this->reset(['startDate', 'endDate']);
        }
    }

    public function updatedStartDate()
    {
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->resetPage();
    }

    public function render()
    {
        $users = User::where('role', 'membre')->get();
        $salarries = Salary::paginate($this->perPageSalary);

        $payrolls = Payroll::with('user')
            ->when($this->search, fn($q) => $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%$this->search%")))
            ->when($this->filterType === 'day', function ($q) {
                $q->whereDate('created_at', now()->today());
            })
            ->when($this->filterType === 'week', function ($q) {
                $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            })
            ->when($this->filterType === 'month', function ($q) {
                $q->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
            })
            ->when($this->filterType === 'range' && $this->startDate && $this->endDate, function ($q) {
                $q->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);
            })
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        return view('livewire.payroll-component', [
            'payrolls' => $payrolls,
            'users' => $users,
            'salarries' => $salarries
        ]);
    }
}
