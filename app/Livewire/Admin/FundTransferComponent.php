<?php

namespace App\Livewire\Admin;

use App\Helpers\UserLogHelper;
use App\Models\MainCashRegister;
use App\Models\AgentAccount;
use App\Models\Account;
use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Validation\ValidationException;
use Livewire\WithPagination;

class FundTransferComponent extends Component
{
    use WithPagination;

    public $transfer_type = 'agent'; // 'agent' ou 'member'
    public $currency = 'CDF'; // ou 'USD'
    public $amount;
    public $description;
    public $recipient_id;
    public $search = '';
    public $searchagent = '';
    public $perPage = 10;
    public $filterType = 'month'; // 'day', 'week', 'month', 'range'
    public $startDate;
    public $endDate;
    public $password;

    protected $paginationTheme = 'bootstrap';

    public $members = [];
    public $results = [];

    public $showPreview = false; // contrôle du modal
    public $previewData = [];

    public function updatedSearchagent()
    {
        $query = trim($this->searchagent);
        if ($query !== '') {
            $this->results = User::query()
                ->where(function ($q) {
                    if ($this->transfer_type === 'agent') {
                        $q->where('role', '!=', 'membre')->orWhereNull('role');
                    } else {
                        $q->where('role', 'membre');
                    }
                })
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
            $this->searchagent = "{$user->name} {$user->postnom} {$user->prenom}";
            $this->results = [];

            $this->recipient_id = $user->id;
            $this->dispatch('userSelected', $user->id);
        }
    }

    public function updatedTransferType()
    {
        $this->reset(['recipient_id']);
    }

    public function submitTransfer()
    {
        $this->validate([
            'transfer_type' => 'required|in:agent,member',
            'recipient_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:CDF,USD',
            'password' => 'required|string',
        ]);

        try {

            DB::transaction(function () {
                $recipient = User::findOrFail($this->recipient_id);

                if ($this->transfer_type === 'agent' && $recipient->role === 'membre') {
                    notyf()->error('Le destinataire doit être un agent, pas un membre.');
                    return;
                }

                if ($this->transfer_type === 'member' && $recipient->role !== 'membre') {
                    notyf()->error('Le destinataire doit avoir le rôle membre.');
                    return;
                }

                $mainCash = MainCashRegister::where('currency', $this->currency)->firstOrFail();

                if ($mainCash->balance < $this->amount) {
                    notyf()->error('Solde insuffisant dans la caisse centrale');
                    return;
                }

                // Débit caisse centrale
                $mainCash->balance -= $this->amount;
                $mainCash->save();

                // Transaction sortante
                Transaction::create([
                    'user_id' => Auth::id(),
                    'type' => 'virement_caisse_sortant',
                    'currency' => $this->currency,
                    'amount' => $this->amount,
                    'balance_after' => $mainCash->balance,
                    'description' => 'Virement sortant vers ' . $this->transfer_type . ' '. $recipient->name . ' ' . $recipient->postnom,
                ]);

                $transfer = '';

                if ($this->transfer_type === 'agent') {
                    $agent = AgentAccount::firstOrCreate(
                        ['user_id' => $this->recipient_id, 'currency' => $this->currency],
                        ['balance' => 0]
                    );

                    $agent->balance += $this->amount;
                    $agent->save();

                    $transfer = Transaction::create([
                        'user_id' => $this->recipient_id,
                        'agent_account_id' => $agent->id,
                        'type' => 'virement_caisse_entrant',
                        'currency' => $this->currency,
                        'amount' => $this->amount,
                        'balance_after' => $agent->balance,
                        'description' => $this->description ?? 'Virement reçu depuis caisse centrale',
                    ]);

                    // ÉCRITURE COMPTABLE (Central -> Agent)
                    try {
                        $accountingService = app(\App\Services\AccountingService::class);
                        $accountingService->recordTransfer(
                            fromCaisse: 'centrale',
                            toCaisse: 'agent', // TODO: Identifier l'agent spécifique si possible ? Pour l'instant Caisse Agent globale
                            amount: (float) $this->amount,
                            currency: $this->currency
                        );
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Erreur comptable virement central->agent: " . $e->getMessage());
                    }

                } else {
                    $account = Account::firstOrCreate(
                        ['user_id' => $this->recipient_id, 'currency' => $this->currency],
                        ['balance' => 0]
                    );

                    if ($account->status === 'Inactif') {
                        notyf()->error("Opération refusée. Le compte {$this->currency} de ce membre est Inactif.");
                        return;
                    }

                    $account->balance += $this->amount;
                    $account->save();

                    $transfer = Transaction::create([
                        'user_id' => $this->recipient_id,
                        'account_id' => $account->id,
                        'type' => 'virement_caisse_entrant',
                        'currency' => $this->currency,
                        'amount' => $this->amount,
                        'balance_after' => $account->balance,
                        'description' => $this->description ?? 'Virement reçu depuis caisse centrale',
                    ]);

                    // TODO: Écriture comptable pour Central -> Membre
                    // Attention: Crédit Caisse (Actif baisse) + Crédit Compte Membre (Dette augmente) = Déséquilibré sans contrepartie (Débit).
                    // Nécessite clarification métier.
                }

                UserLogHelper::log_user_activity(
                    action: 'virement_caisse',
                    description: "Virement de {$this->amount} {$this->currency} vers {$this->transfer_type} ID:{$this->recipient_id}"
                );

                Notification::create([
                    'user_id' => $this->recipient_id,
                    'title' => 'Virement reçu',
                    'message' => "Vous avez reçu un virement de {$this->amount} {$this->currency} dans votre compte.",
                    'read' => false,
                ]);

                // Notifier les utilisateurs concernés
                $usersToNotify = User::role(['Admin', 'Caissier', 'SUPER IT', 'Comptable'])->get();
                $notificationMessage = "Un virement de " . number_format($this->amount, 2) . " {$this->currency} a été effectué vers {$this->transfer_type} ID:{$this->recipient_id} : {$this->searchagent} par " . (Auth::user() ? Auth::user()->name . "." . Auth::user()->postnom : "Système") . ".";

                foreach ($usersToNotify as $notifyUser) {
                    Notification::create([
                        'user_id' => $notifyUser->id,
                        'title' => 'Transfert effectué',
                        'message' => $notificationMessage,
                        'read' => false,
                    ]);
                }

                $this->reset(['amount', 'description', 'recipient_id']);
                $this->dispatch('refreshComponent');
                notyf()->success('Virement effectué avec succès.');

            });

        } catch (\Throwable $e) {
            // Journaliser l’erreur pour le debug si nécessaire
            report($e);
            notyf()->error('Une erreur est survenue lors du virement');
        }
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

    public function getTransactionsProperty()
    {
        return Transaction::whereIn('type', ['virement_caisse_sortant', 'virement_caisse_entrant'])
            ->when($this->search, function ($query) {
                $query->where('description', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterType === 'day', function ($query) {
                $query->whereDate('created_at', now()->today());
            })
            ->when($this->filterType === 'week', function ($query) {
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
            })
            ->when($this->filterType === 'month', function ($query) {
                $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
            })
            ->when($this->filterType === 'range' && $this->startDate && $this->endDate, function ($query) {
                $query->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);
            })
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    public function exportReceipt($transactionId)
    {
        $transfer = Transaction::with('user')->findOrFail($transactionId);

        // Si c’est un transfert entrant, l’agent est le destinataire
        $agent = $transfer->user ?? User::find($transfer->user_id);

        $pdf = Pdf::loadView('receipts.transfer-compte', [
            'transfer' => $transfer,
            'agent' => $agent,
        ])->setPaper([0, 0, 226.77, 600], 'portrait'); // 80mm x ~210mm

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'recu_virement_' . $transactionId . '.pdf');
    }

    public function render()
    {
        return view('livewire.admin.fund-transfer-component', [
            'recipients' => $this->transfer_type === 'agent'
                ? AgentAccount::with('user')->where('currency', $this->currency)->get()
                : Account::with('user')->where('currency', $this->currency)->get(),
        ]);
    }

    public function previewTransfer()
    {
        // Validation simple avant prévisualisation
        $this->validate([
            'transfer_type' => 'required|in:agent,member',
            'recipient_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:CDF,USD',
        ]);

        // Trouver le bénéficiaire
        $user = User::find($this->recipient_id);

        if (!$user) {
            notyf()->error('Bénéficiaire introuvable');
            return;
        }

        if ($this->transfer_type === 'agent' && $user->role === 'membre') {
            notyf()->error('Le destinataire doit être un agent, pas un membre.');
            return;
        }

        if ($this->transfer_type === 'member' && $user->role !== 'membre') {
            notyf()->error('Le destinataire doit avoir le rôle membre.');
            return;
        }

        // Préparer les données à afficher
        $this->previewData = [
            'type' => $this->transfer_type === 'agent' ? 'Agent' : 'Membre',
            'devise' => $this->currency,
            'montant' => number_format($this->amount, 2, ',', ' '),
            'beneficiaire' => "{$user->name} {$user->postnom} {$user->prenom}",
            'description' => $this->description ?: 'Aucune remarque',
        ];

        // Ouvrir le modal
        $this->showPreview = true;
    }

    public function confirmTransfer()
    {
        if (!\Illuminate\Support\Facades\Hash::check($this->password, Auth::user()->password)) {
            $this->addError('password', 'Mot de passe incorrect.');
            notyf()->error('Mot de passe incorrect.');
            return;
        }
        $this->showPreview = false;
        $this->submitTransfer();
    }
}
