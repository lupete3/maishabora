<?php

namespace App\Livewire\Credit;

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
    public $member_id;
    public $credit_id;
    public $selectedCredit = null;

    public $members = [];
    public $credits = [];
    public string $search = '';
    public array $results = [];

    protected $rules = [
        'member_id' => 'required|exists:users,id',
        'credit_id' => 'required|exists:credits,id',
    ];

    public function mount()
    {
        $user = Auth::user();
        Gate::authorize('afficher-credit', User::class);

        $this->members = User::where('role', 'membre')->get();
    }

    public function updatedSearch()
    {
        $query = trim($this->search);
        if ($query !== '') {
            $this->results = User::query()
                ->where(function($q) use ($query) {
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

    public function payRepayment($repaymentId)
    {
        try {
            DB::transaction(function () use ($repaymentId) {
                $repayment = Repayment::findOrFail($repaymentId);

                if ($repayment->is_paid) {
                    notyf()->info(__('Cette échéance a déjà été remboursée.'));
                    return;
                }

                $credit = $repayment->credit;
                $member = $credit->user;

                // Compte du membre
                $account = Account::firstOrCreate(
                    ['user_id' => $member->id, 'currency' => $credit->currency],
                    ['balance' => 0]
                );

                $amountToPay = round($repayment->total_due, 3); // Sans pénalité si payé manuellement à temps

                if ($account->balance < $amountToPay) {
                    throw new \Exception('Solde insuffisant pour effectuer ce remboursement.');
                }

                // Débiter le compte membre
                $account->balance -= $amountToPay;
                $account->save();

                // Marquer l’échéance comme payée
                $repayment->paid_date = date('Y-m-d');
                $repayment->paid_amount = $amountToPay;
                $repayment->total_due = $amountToPay;
                $repayment->is_paid = true;
                $repayment->save();

                // Vérifier si tout est remboursé
                if (!$credit->repayments()->where('is_paid', false)->exists()) {
                    $credit->is_paid = true;
                    $credit->save();
                }

                // Récupérer ou créer le compte agent encaisseur
                $agentAccount = AgentAccount::firstOrCreate(
                    ['user_id' => 95, 'currency' => $credit->currency],
                    ['balance' => 0]
                );

                $interestPart = round($amountToPay * ($credit->interest_rate * 2 / 100), 3);
                $penality = $repayment->penalty;

                // Créditer le compte agent
                $agentAccount->balance += ($interestPart+$penality);
                $agentAccount->save();

                // Enregistrement de la transaction client (débit)
                Transaction::create([
                    'account_id' => $account->id,
                    'user_id' => $member->id,
                    'type' => 'remboursement_de_credit',
                    'currency' => $credit->currency,
                    'amount' => $amountToPay,
                    'balance_after' => $account->balance,
                    'description' => "Remboursement manuel de l'échéance #{$repayment->id} pour le crédit #{$credit->id}",
                ]);

                // Enregistrement de la transaction agent (crédit)
                Transaction::create([
                    'agent_account_id' => $agentAccount->id,
                    'user_id' => 95,
                    'type' => 'encaissement_agent',
                    'currency' => $credit->currency,
                    'amount' => ($interestPart+$penality),
                    'balance_after' => $agentAccount->balance,
                    'description' => "Encaissement agent pour l’échéance #{$repayment->id} du client {$member->code} {$member->name} {$member->postnom}",
                ]);

                // Notification
                Notification::create([
                    'user_id' => $member->id,
                    'title' => 'Remboursement effectué',
                    'message' => "Votre échéance du {$repayment->due_date->format('d/m/Y')} a été remboursée manuellement.",
                    'read' => false,
                ]);
            });

            notyf()->success(__('Échéance remboursée avec succès !'));
            $this->updatedCreditId(); // Rafraîchir
        } catch (\Throwable $e) {
            report($e);
            notyf()->error('Erreur lors du remboursement : ' . $e->getMessage());
        }
    }


    public function render()
    {
        return view('livewire.credit.manage-repayments');
    }
}
