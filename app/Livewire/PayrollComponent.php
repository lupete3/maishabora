<?php

namespace App\Livewire;

use App\Helpers\UserLogHelper;
use App\Models\Account;
use App\Models\MainCashRegister;
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

    public $members = [];
    public $results = [];
    public $resultsAgent = [];

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

    public function paySalary($userId)
    {
        Gate::authorize('ajouter-paye', User::class);

        try {
            $this->processSalaryPayment($userId);
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
            notyf()->error('Erreur lors du paiement du salaire');
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
                ['user_id' => $userId, 'currency' => $this->currency],
                ['balance' => 0]
            );

            // Crédit compte agent
            $accountRetenuSalaire = Account::firstOrCreate(
                ['user_id' => 328, 'currency' => $this->currency],
                ['balance' => 0]
            );

            $account->increment('balance', $salary->amount - $retenuSalaire);
            $accountRetenuSalaire->increment('balance', $retenuSalaire);

            Transaction::create([
                'user_id' => $userId,
                'account_id' => $account->id,
                'type' => 'paie_entrant',
                'currency' => $this->currency,
                'amount' => $salary->amount - $retenuSalaire,
                'balance_after' => $account->balance,
                'description' => 'Salaire reçu',
            ]);

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
            Payroll::create([
                'user_id' => $userId,
                'salary_id' => $salary->id,
                'currency' => $this->currency,
                'amount' => $salary->amount,
                'period' => $this->period ?? now()->format('Y-m'),
                'status' => 'paid',
            ]);

            UserLogHelper::log_user_activity(
                action: 'paiement_salaire',
                description: "Paiement du salaire de {$salary->amount} {$this->currency} à l’agent ID:$userId"
            );

            notyf()->success('Salaire payé avec succès.');
        });
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

    public function render()
    {
        $users = User::where('role', 'membre')->get();
        $salarries = Salary::paginate($this->perPageSalary);

        $payrolls = Payroll::with('user')
            ->when($this->search, fn($q) => $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%$this->search%")))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        return view('livewire.payroll-component', [
            'payrolls' => $payrolls,
            'users' => $users,
            'salarries' => $salarries
        ]);
    }
}
