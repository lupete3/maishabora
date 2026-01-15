<?php



namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Repayment;
use App\Models\Account;
use App\Models\AgentAccount;
use App\Models\MainCashRegister;
use App\Models\Transaction;
use App\Models\Notification;
use App\Models\Credit;
use Carbon\Carbon;

class CheckOverdueRepayments extends Command
{
    protected $signature = 'check:overdue-repayments';
    protected $description = 'Vérifie les échéances en retard et applique les remboursements ou pénalités';

    public function handle()
    {
        $today = Carbon::today();

        //Trouver toutes les échéances non payées avec date < aujourd'hui
        $overdue = Repayment::where('due_date', '<=', $today)
            ->where('is_paid', false)
            ->get();

        foreach ($overdue as $repayment) {
            $credit = $repayment->credit;
            $member = $credit->user;

            //Récupérer le compte du membre
            $account = Account::firstOrCreate(
                [
                    'user_id' => $member->id,
                    'currency' => $credit->currency,
                    'type' => 'current'
                ],
                ['balance' => 0]
            );

            //Calcul du montant dû + pénalité
            $daysLate = max(0, Carbon::parse($repayment->due_date)->diffInDays($today));
            $dailyPenaltyRate = 0.003; //0.3% par jour
            $expectedAmount = round((float) $repayment->expected_amount, 3);
            $penaltyAmount = round($expectedAmount * $dailyPenaltyRate * $daysLate, 3);
            $totalDue = round($expectedAmount + $penaltyAmount, 3);
            $interestPart = round($credit->amount * ($credit->interest_rate / 100), 3);
            //$interestAfter = $interestPart+$penaltyAmount;


            //Vérifier si le membre a assez de fonds
            if ($account->balance >= $expectedAmount) {
                //Débiter le compte du membre
                $account->balance -= $expectedAmount;
                $account->save();

                //Crediter la caisse centrale
                $agentAccount = AgentAccount::firstOrCreate(
                    ['user_id' => 95, 'currency' => $credit->currency],
                    ['balance' => 0]
                );

                //Crediter la caisse centrale
                $penalityAccount = AgentAccount::firstOrCreate(
                    ['user_id' => 472, 'currency' => $credit->currency],
                    ['balance' => 0]
                );

                $penalityAccount->balance += $penaltyAmount;
                $agentAccount->balance += $interestPart;

                $penalityAccount->save();
                $agentAccount->save();

                //Enregistrer la transaction
                Transaction::create([
                    'account_id' => $account->id,
                    'user_id' => $member->id,
                    'type' => 'remboursement_de_credit',
                    'currency' => $credit->currency,
                    'amount' => $expectedAmount,
                    'balance_after' => $account->balance,
                    'description' => "Remboursement automatique de l'échéance n°{$repayment->id}",
                ]);


                //Enregistrement de la transaction
                if ($penaltyAmount > 0) {
                    Transaction::create([
                        'account_id' => NULL,
                        'agent_account_id' => $penalityAccount->id,
                        'user_id' => 472,
                        'type' => 'Pénalité du credit',
                        'currency' => $credit->currency,
                        'amount' => $penaltyAmount,
                        'balance_after' => $penalityAccount->balance,
                        'description' => "Pénalité du credit #{$credit->id} - Montant: {$penaltyAmount} {$credit->currency} du compte client {$member->code} {$member->name} {$member->postnom}",
                    ]);
                }

                //Enregistrement de la transaction
                Transaction::create([
                    'account_id' => NULL,
                    'agent_account_id' => $agentAccount->id,
                    'user_id' => 95,
                    'type' => 'Interêt du credit',
                    'currency' => $credit->currency,
                    'amount' => $interestPart,
                    'balance_after' => $agentAccount->balance,
                    'description' => "Interêt du credit #{$credit->id} - Montant: {$interestPart} {$credit->currency} du compte client {$member->code} {$member->name} {$member->postnom}",
                ]);

                //Mettre à jour l'échéance
                $repayment->paid_date = now();
                $repayment->paid_amount = $expectedAmount;
                $repayment->is_paid = true;
                $repayment->penalty = $penaltyAmount;
                $repayment->total_due = $totalDue;
                $repayment->save();

                // si tout est remboursé
                if (!$repayment->credit->repayments->where('is_paid', false)->count()) {
                    $repayment->credit->is_paid = true;
                    $repayment->credit->save();
                }

                //Notification de remboursement automatique
                Notification::create([
                    'user_id' => $member->id,
                    'title' => 'Remboursement Automatique',
                    'message' => "Votre échéance du {$repayment->due_date} a été remboursée automatiquement avec succès.",
                    'read' => false,
                ]);
            } else {
                // insuffisant → appliquer pénalité sans virement
                if ($repayment->penalty != $penaltyAmount) {
                    $repayment->penalty = $penaltyAmount;
                    $repayment->total_due = $totalDue;
                    $repayment->save();

                    //Enregistrer la transaction (solde négatif)
                    Transaction::create([
                        'account_id' => $account->id,
                        'user_id' => $member->id,
                        'type' => 'penalite_de_credit',
                        'currency' => $credit->currency,
                        'amount' => round($penaltyAmount, 2),
                        'balance_after' => $account->balance,
                        'description' => "Pénalité appliquée sur l'échéance du {$repayment->due_date}",
                    ]);

                    //Notification de pénalité
                    Notification::create([
                        'user_id' => $member->id,
                        'title' => 'Retard de remboursement',
                        'message' => "Votre échéance du {$repayment->due_date} est en retard de {$daysLate} jour(s). Une pénalité de " . round($penaltyAmount, 2) . " a été appliquée.",
                        'read' => false,
                    ]);
                }
            }
        }

        $this->info(count($overdue) . ' échéances en retard vérifiées.');
    }
}










// namespace App\Console\Commands;

// use Illuminate\Console\Command;
// use App\Models\Repayment;
// use App\Models\Account;
// use App\Models\AgentAccount;
// use App\Models\MainCashRegister;
// use App\Models\Transaction;
// use App\Models\Notification;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;
// use Carbon\Carbon;

// class CheckOverdueRepayments extends Command
// {
//     protected $signature = 'check:overdue-repayments';
//     protected $description = 'Vérifie les échéances en retard et applique les remboursements ou pénalités';

//     public function handle()
//     {
//         $today = Carbon::today();
//         $processed = 0;

//         Repayment::with(['credit.user'])
//             ->where('due_date', '<=', $today)
//             ->where('is_paid', false)
//             ->orderBy('id')
//             ->chunkById(300, function ($repayments) use ($today, &$processed) {

//                 foreach ($repayments as $repayment) {
//                     DB::beginTransaction();

//                     try {
//                         $credit = $repayment->credit;
//                         $member = $credit->user;

//                         // =========================
//                         // COMPTE MEMBRE
//                         // =========================
//                         $account = Account::firstOrCreate(
//                             [
//                                 'user_id' => $member->id,
//                                 'currency' => $credit->currency,
//                                 'type' => 'current'
//                             ],
//                             ['balance' => 0]
//                         );

//                         // =========================
//                         // CALCULS
//                         // =========================
//                         $daysLate = max(0, Carbon::parse($repayment->due_date)->diffInDays($today));
//                         $dailyPenaltyRate = 0.003;
//                         $expectedAmount = round((float) $repayment->expected_amount, 3);
//                         $penaltyAmount = round($expectedAmount * $dailyPenaltyRate * $daysLate, 3);
//                         $totalDue = round($expectedAmount + $penaltyAmount, 3);

//                         $interestPart = round($credit->amount * ($credit->interest_rate / 100), 3);
//                         $interestAfter = $interestPart + $penaltyAmount;

//                         // =========================
//                         // CAS : SOLDE SUFFISANT
//                         // =========================
//                         if ($account->balance >= $expectedAmount) {

//                             $account->decrement('balance', $expectedAmount);

//                             $agentAccount = AgentAccount::firstOrCreate(
//                                 ['user_id' => 95, 'currency' => $credit->currency],
//                                 ['balance' => 0]
//                             );

//                             $mainCash = MainCashRegister::firstOrCreate(
//                                 ['currency' => $credit->currency],
//                                 ['balance' => 0]
//                             );

//                             $agentAccount->increment('balance', $interestAfter);
//                             $mainCash->increment('balance', $expectedAmount - $interestPart);

//                             // =========================
//                             // TRANSACTIONS
//                             // =========================
//                             Transaction::insert([
//                                 [
//                                     'account_id' => $account->id,
//                                     'user_id' => $member->id,
//                                     'type' => 'remboursement_de_credit',
//                                     'currency' => $credit->currency,
//                                     'amount' => $expectedAmount,
//                                     'balance_after' => $account->balance,
//                                     'description' => "Remboursement auto échéance #{$repayment->id}",
//                                     'created_at' => now(),
//                                     'updated_at' => now(),
//                                 ],
//                                 [
//                                     'account_id' => null,
//                                     'agent_account_id' => $agentAccount->id,
//                                     'user_id' => 95,
//                                     'type' => 'Interêt du credit',
//                                     'currency' => $credit->currency,
//                                     'amount' => $interestAfter,
//                                     'balance_after' => $agentAccount->balance,
//                                     'description' => "Intérêt crédit #{$credit->id} ({$member->code})",
//                                     'created_at' => now(),
//                                     'updated_at' => now(),
//                                 ]
//                             ]);

//                             // =========================
//                             // MAJ ECHEANCE
//                             // =========================
//                             $repayment->update([
//                                 'paid_date' => now(),
//                                 'paid_amount' => $expectedAmount,
//                                 'is_paid' => true,
//                                 'penalty' => $penaltyAmount,
//                                 'total_due' => $totalDue,
//                             ]);

//                             // =========================
//                             // CREDIT SOLDÉ ?
//                             // =========================
//                             if (!$credit->repayments()->where('is_paid', false)->exists()) {
//                                 $credit->update(['is_paid' => true]);
//                             }

//                             Notification::create([
//                                 'user_id' => $member->id,
//                                 'title' => 'Remboursement Automatique',
//                                 'message' => "Votre échéance du {$repayment->due_date} a été remboursée automatiquement.",
//                                 'read' => false,
//                             ]);
//                         }
//                         // =========================
//                         // CAS : SOLDE INSUFFISANT
//                         // =========================
//                         else {
//                             $repayment->update([
//                                 'penalty' => $penaltyAmount,
//                                 'total_due' => $totalDue,
//                             ]);

//                             Transaction::create([
//                                 'account_id' => $account->id,
//                                 'user_id' => $member->id,
//                                 'type' => 'penalite_de_credit',
//                                 'currency' => $credit->currency,
//                                 'amount' => $penaltyAmount,
//                                 'balance_after' => $account->balance,
//                                 'description' => "Pénalité échéance {$repayment->due_date}",
//                             ]);

//                             Notification::create([
//                                 'user_id' => $member->id,
//                                 'title' => 'Retard de remboursement',
//                                 'message' => "Retard de {$daysLate} jour(s). Pénalité : {$penaltyAmount} {$credit->currency}.",
//                                 'read' => false,
//                             ]);
//                         }

//                         DB::commit();
//                         $processed++;

//                     } catch (\Throwable $e) {
//                         DB::rollBack();

//                         Log::error('Erreur remboursement automatique', [
//                             'repayment_id' => $repayment->id,
//                             'error' => $e->getMessage()
//                         ]);
//                     }
//                 }
//             });

//         $this->info("✔ {$processed} échéances traitées avec succès.");
//     }
// }





