<?php

namespace App\Livewire;

use App\Helpers\UserLogHelper;
use App\Models\Notification;
use Livewire\Component;
use App\Models\AgentAccount;
use App\Models\MainCashRegister;
use App\Models\Transaction;
use App\Models\Transfert;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\WithPagination;

class TransferToCentralCash extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';
    public $currency = '';
    public $amount = '';
    public $currencies = ['USD', 'CDF'];
    public $filterType = 'month'; // 'day', 'week', 'month', 'range'
    public $startDate;
    public $endDate;
    public $password = '';

    // ✅ Nouveau : contrôle d’affichage du modal
    public $showConfirmation = false;

    protected $rules = [
        'currency' => 'required|in:USD,CDF',
        'amount' => 'required|numeric|min:0.01',
    ];

    protected $messages = [
        'currency.required' => 'Veuillez choisir une devise.',
        'currency.in' => 'Devise invalide.',
        'amount.required' => 'Le montant est obligatoire.',
        'amount.numeric' => 'Le montant doit être un nombre.',
        'amount.min' => 'Le montant doit être supérieur à 0.',
    ];

    public function mount()
    {
        Gate::authorize('ajouter-transfert-caisse', User::class);
    }

    public function updated($property)
    {
        $this->validateOnly($property);

        // Auto-remplissage du montant lors du choix de la devise
        if ($property === 'currency' && $this->currency) {
            $this->fillAmountFromBalance();
        }
    }

    public function setFillAmount($curr, $bal)
    {
        $this->currency = $curr;
        $this->amount = $bal;
    }

    private function fillAmountFromBalance()
    {
        $agentAccount = AgentAccount::where('user_id', Auth::id())
            ->where('currency', $this->currency)
            ->first();

        if ($agentAccount) {
            $this->amount = $agentAccount->balance;
        } else {
            $this->amount = '';
        }
    }

    public function submit()
    {
        $this->validate();

        $agentAccount = AgentAccount::where('user_id', Auth::id())
            ->where('currency', $this->currency)
            ->first();

        if ($agentAccount && $agentAccount->balance < $this->amount) {
            $this->addError('amount', "Solde insuffisant dans votre caisse.");
            return;
        }

        // ✅ Active le modal de prévisualisation
        $this->showConfirmation = true;
    }

    public function confirmSubmit()
    {
        $this->validate();

        if (!\Illuminate\Support\Facades\Hash::check($this->password, Auth::user()->password)) {
            $this->addError('password', 'Mot de passe incorrect.');
            notyf()->error('Mot de passe incorrect.');
            return;
        }

        $agentAccount = AgentAccount::firstOrCreate(
            ['user_id' => Auth::id(), 'currency' => $this->currency],
            ['balance' => 0]
        );

        if ($agentAccount->balance < $this->amount) {
            $this->addError('amount', "Solde insuffisant dans votre caisse.");
            return;
        }

        $mainCash = MainCashRegister::firstOrCreate(
            ['currency' => $this->currency],
            ['balance' => 0]
        );

        // ✅ Enregistrement du transfert (en attente)
        $transfer = Transfert::create([
            'from_agent_account_id' => $agentAccount->id,
            'to_main_cash_register_id' => $mainCash->id,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'status' => 'pending',
        ]);

        // Notifier les utilisateurs concernés (Admin/Comptable pour validation)
        $usersToNotify = User::whereIn('role', ['admin', 'caissier', 'SUPER IT', 'comptable'])->get();
        $notificationMessage = "Une nouvelle demande de virement de " . number_format($this->amount, 2) . " {$this->currency} a été effectuée vers la caisse centrale par " . Auth::user()->name . " " . Auth::user()->postnom . ". Référence : #REF{$transfer->id}. Veuillez la valider.";

        foreach ($usersToNotify as $notifyUser) {
            Notification::create([
                'user_id' => $notifyUser->id,
                'title' => 'Nouveau virement en attente',
                'message' => $notificationMessage,
                'read' => false,
            ]);
        }

        UserLogHelper::log_user_activity(
            action: 'virement_caisse_centrale_demande',
            description: "Demande de virement de {$this->amount} {$this->currency} du compte de " . Auth::user()->name . " vers la caisse centrale. #REF{$transfer->id}"
        );

        notyf()->success('Demande de virement envoyée avec succès ! En attente de validation.');

        // ✅ Ferme le modal et réinitialise
        $this->reset(['amount', 'currency', 'showConfirmation']);

        $this->redirect(route('transfer.to.central'), navigate: false);
    }

    public function validateTransfer($id)
    {
        Gate::authorize('valider-transfert-caisse', User::class);

        $transfer = Transfert::findOrFail($id);

        if ($transfer->status !== 'pending') {
            notyf()->error('Ce virement ne peut plus être validé.');
            return;
        }

        $agentAccount = AgentAccount::findOrFail($transfer->from_agent_account_id);
        $mainCash = MainCashRegister::findOrFail($transfer->to_main_cash_register_id);

        if ($agentAccount->balance < $transfer->amount) {
            notyf()->error("Solde insuffisant dans la caisse de l'agent.");
            return;
        }

        // Action dans une transaction DB
        \Illuminate\Support\Facades\DB::transaction(function () use ($transfer, $agentAccount, $mainCash) {
            // Mise à jour des soldes
            $agentAccount->decrement('balance', $transfer->amount);
            $mainCash->increment('balance', $transfer->amount);

            // Mise à jour du virement
            $transfer->update([
                'status' => 'validated',
                'processed_by_id' => Auth::id()
            ]);

            // Création de la transaction
            Transaction::create([
                'agent_account_id' => $agentAccount->id,
                'user_id' => $agentAccount->user_id,
                'type' => 'virement vers caisse centrale',
                'currency' => $transfer->currency,
                'amount' => $transfer->amount,
                'balance_after' => $agentAccount->balance,
                'description' => "Virement de {$transfer->amount} {$transfer->currency} du compte de " . $agentAccount->user->name . " vers la caisse centrale. #REF{$transfer->id}",
            ]);

            // ÉCRITURE COMPTABLE AUTOMATIQUE
            try {
                $accountingService = app(\App\Services\AccountingService::class);
                $accountingService->recordTransfer(
                    fromCaisse: 'agent',
                    toCaisse: 'centrale',
                    amount: (float) $transfer->amount,
                    currency: $transfer->currency
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Erreur comptable transfert caisse: " . $e->getMessage());
            }

            UserLogHelper::log_user_activity(
                action: 'virement_caisse_centrale_valide',
                description: "Virement #REF{$transfer->id} validé par " . Auth::user()->name
            );
        });

        notyf()->success('Virement validé avec succès !');
    }

    public function cancelTransfer($id)
    {
        Gate::authorize('annuler-transfert-caisse', User::class);

        $transfer = Transfert::findOrFail($id);

        if ($transfer->status !== 'pending') {
            notyf()->error('Ce virement ne peut plus être annulé.');
            return;
        }

        $transfer->update([
            'status' => 'cancelled',
            'processed_by_id' => Auth::id()
        ]);

        UserLogHelper::log_user_activity(
            action: 'virement_caisse_centrale_annule',
            description: "Virement #REF{$transfer->id} annulé par " . Auth::user()->name
        );

        notyf()->success('Virement annulé.');
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
        $agentAccounts = AgentAccount::where('user_id', Auth::id())->get();

        $user = Auth::user();
        $isAdminOrFinance = in_array($user->role, ['admin', 'comptable', 'caissier', 'SUPER IT']);

        // Historique des transferts
        $query = Transfert::with(['fromAgentAccount.user', 'toMainCashRegister']);

        if (!$isAdminOrFinance) {
            $query->whereHas('fromAgentAccount', function ($q) {
                $q->where('user_id', Auth::id());
            });
        }

        $transfers = $query->when($this->filterType === 'day', function ($q) {
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
            ->latest()
            ->paginate(10);

        return view('livewire.transfer-to-central-cash', compact('agentAccounts', 'transfers', 'isAdminOrFinance'));
    }
}
