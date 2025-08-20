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

    protected $rules = [
        'member_id' => 'required|exists:users,id',
        'credit_id' => 'required|exists:credits,id',
    ];

    public $repaymentToPay = null;
    public $applyInterest = true; // valeur par défaut

    public function confirmRepayment($repaymentId)
    {
        $this->repaymentToPay = $repaymentId;
        $this->dispatch('openModal', name: 'confirm-repayment'); // JS pour ouvrir le modal
    }

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


// public function payRepayment($withInterest = true)
// {
//     try {
//         DB::transaction(function () use ($withInterest) {
//             $repayment = Repayment::findOrFail($this->repaymentToPay);

//             if ($repayment->is_paid) {
//                 notyf()->info(__('Cette échéance a déjà été remboursée.'));
//                 return;
//             }

//             $credit = $repayment->credit;
//             $member = $credit->user;

//             $account = Account::firstOrCreate(
//                 ['user_id' => $member->id, 'currency' => $credit->currency],
//                 ['balance' => 0]
//             );

//             // ✅ Si remboursement total demandé sans intérêts futurs
//             if (!$withInterest) {
//                 $capitalRestant = $credit->amount - $credit->repayments()->where('is_paid', true)->sum('expected_amount');
//                 $amountToPay = $capitalRestant;
//             } else {
//                 $amountToPay = round($repayment->total_due, 3);
//             }

//             if ($account->balance < $amountToPay) {
//                 throw new \Exception('Solde insuffisant pour effectuer ce remboursement.');
//             }

//             // Débiter le compte membre
//             $account->balance -= $amountToPay;
//             $account->save();

//             if (!$withInterest) {
//                 // ✅ Tout solder sans intérêts futurs
//                 foreach ($credit->repayments as $r) {
//                     if (!$r->is_paid) {
//                         $r->paid_date = now();
//                         $r->paid_amount = $r->expected_amount;
//                         $r->total_due = $r->expected_amount;
//                         $r->is_paid = true;
//                         $r->save();
//                     }
//                 }
//                 $credit->is_paid = true;
//                 $credit->save();
//             } else {
//                 // Paiement normal d'une échéance
//                 $repayment->paid_date = now();
//                 $repayment->paid_amount = $amountToPay;
//                 $repayment->total_due = $amountToPay;
//                 $repayment->is_paid = true;
//                 $repayment->save();

//                 if (!$credit->repayments()->where('is_paid', false)->exists()) {
//                     $credit->is_paid = true;
//                     $credit->save();
//                 }
//             }

//             // ... reste de ta logique (transactions, logs, notifications) ...
//         });

//         notyf()->success(__('Remboursement effectué avec succès !'));
//         $this->updatedCreditId();

//     } catch (\Throwable $e) {
//         report($e);
//         notyf()->error('Erreur lors du remboursement : ' . $e->getMessage());
//     }
// }



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

                // Compte du membre
                $account = Account::firstOrCreate(
                    ['user_id' => $member->id, 'currency' => $credit->currency],
                    ['balance' => 0]
                );

                if ($withInterest == true) {
                    // Paiement normal d'une échéance avec intérêts
                    $amountToPay = round($repayment->total_due, 3); // Sans pénalité si payé manuellement à temps
                } else {
                    // Remboursement total sans intérêts futurs
                    $capitalRestant = $repayment->credit->amount / $repayment->credit->installments;
                    $amountToPay = round($capitalRestant, 3);
                }

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

                if ($withInterest == true) {
                    // Paiement normal d'une échéance avec intérêts
                    $amountToPay = $repayment->total_due; // Sans pénalité si payé manuellement à temps
                
                    // Récupérer ou créer le compte agent encaisseur
                    $agentAccount = AgentAccount::firstOrCreate(
                        ['user_id' => 95, 'currency' => $credit->currency],
                        ['balance' => 0]
                    );

                    $interestPart = $repayment->credit->amount * ($credit->interest_rate / 100);
                    $penality = $repayment->penalty;

                    // Créditer le compte agent
                    $agentAccount->balance += ($interestPart+$penality);
                    $agentAccount->save();
                
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
                }

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

                UserLogHelper::log_user_activity(
                    action: 'remboursement_credit',
                    description: "Remboursement manuel de l'échéance #{$repayment->id} pour le crédit #{$credit->id} du membre {$member->code} {$member->name} {$member->postnom}, montant {$amountToPay} {$credit->currency}"
                );

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
