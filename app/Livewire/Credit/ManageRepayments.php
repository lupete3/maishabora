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

                // Calcul du montant à payer
                if ($withInterest) {
                    $expectedAmount = floatval($repayment->expected_amount);
                    $penalityAmount = floatval($this->penality);
                    $amountToPay = round($expectedAmount + $penalityAmount, 3);
                } else {
                    $capitalRestant = floatval($repayment->credit->amount) / max(floatval($repayment->credit->installments), 1);
                    $amountToPay = round($capitalRestant, 3);
                }

                // Vérification du solde
                if (floatval($account->balance) < $amountToPay) {
                    notyf()->error(__('Solde insuffisant pour effectuer ce remboursement.'));
                    return;
                }

                // Débiter le compte membre
                $account->balance = floatval($account->balance) - $amountToPay;
                $account->save();

                // Mettre à jour le remboursement
                $repayment->paid_date = now()->format('Y-m-d');
                $repayment->paid_amount = $amountToPay;
                $repayment->total_due = $amountToPay;
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

                    $interestPart = floatval($repayment->credit->amount) * (floatval($credit->interest_rate) / 100);
                    $penality = floatval($repayment->penalty);

                    $agentAccount->balance = floatval($agentAccount->balance) + ($interestPart + $penality);
                    $agentAccount->save();

                    // Transaction agent
                    Transaction::create([
                        'agent_account_id' => $agentAccount->id,
                        'user_id' => 95,
                        'type' => 'encaissement_agent',
                        'currency' => $credit->currency,
                        'amount' => ($interestPart + $penality),
                        'balance_after' => $agentAccount->balance,
                        'description' => "Encaissement agent pour l’échéance #{$repayment->id} du client {$member->code} {$member->name} {$member->postnom}",
                    ]);
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

                $this->openModalConfirm = false;
                notyf()->success(__('Échéance remboursée avec succès !'));
            });

            $this->updatedCreditId(); // Rafraîchir l’affichage

        } catch (\Throwable $e) {
            report($e);
            notyf()->error('Erreur lors du remboursement : ' . $e->getMessage());
        }
    }

}
