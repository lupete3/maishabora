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

        // Trouver toutes les échéances non payées avec date < aujourd'hui
        $overdue = Repayment::where('due_date', '<=', $today)
            ->where('is_paid', false)
            ->get();

        foreach ($overdue as $repayment) {
            $credit = $repayment->credit;
            $member = $credit->user;

            // Récupérer le compte du membre
            $account = Account::firstOrCreate(
                [
                    'user_id' => $member->id,
                    'currency' => $credit->currency
                ],
                ['balance' => 0]
            );

            // Calcul du montant dû + pénalité
            $daysLate = max(0, Carbon::parse($repayment->due_date)->diffInDays($today));
            $dailyPenaltyRate = 0.003; // 0.3% par jour
            $expectedAmount = round((float)$repayment->expected_amount, 3);
            $penaltyAmount = round($expectedAmount * $dailyPenaltyRate * $daysLate, 3);
            $totalDue = round($expectedAmount + $penaltyAmount, 3);
            $interestPart = round($credit->amount * ($credit->interest_rate / 100), 3);
            $interestAfter = $interestPart+$penaltyAmount;


            // Vérifier si le membre a assez de fonds
            if ($account->balance >= $expectedAmount) {
                // Débiter le compte du membre
                $account->balance -= $expectedAmount;
                $account->save();

                // Crediter la caisse centrale
                $agentAccount = AgentAccount::firstOrCreate(
                    ['user_id' => 95, 'currency' => $credit->currency],
                    ['balance' => 0]
                );

                // Crediter la caisse centrale
                $mainCash = MainCashRegister::firstOrCreate(
                    ['currency' => $credit->currency],
                    ['balance' => 0]
                );

                // $mainCash->balance += ($expectedAmount - $interestPart);
                $agentAccount->balance += $interestAfter;

                $mainCash->save();
                $agentAccount->save();

                // Enregistrer la transaction
                Transaction::create([
                    'account_id' => $account->id,
                    'user_id' => $member->id,
                    'type' => 'remboursement_de_credit',
                    'currency' => $credit->currency,
                    'amount' => $expectedAmount,
                    'balance_after' => $account->balance,
                    'description' => "Remboursement automatique de l'échéance n°{$repayment->id}",
                ]);

                Transaction::create([
                    'account_id' => $mainCash->id,
                    'user_id' => $agentAccount->id,
                    'type' => 'Entrée de fonds',
                    'currency' => $credit->currency,
                    'amount' => ($expectedAmount - $interestPart),
                    'balance_after' => $mainCash->balance,
                    'description' => "Remboursement automatique de l'échéance n°{$repayment->id}",
                ]);

                // Enregistrement de la transaction
                Transaction::create([
                    'account_id' => NULL,
                    'agent_account_id' => $agentAccount->id,
                    'user_id' => 95,
                    'type' => 'Interêt du credit',
                    'currency' => $credit->currency,
                    'amount' => $interestAfter,
                    'balance_after' => $agentAccount->balance,
                    'description' => "Interêt du credit #{$credit->id} - Montant: {$interestAfter} {$credit->currency} du compte client {$member->code} {$member->name} {$member->postnom}",
                ]);

                // Mettre à jour l'échéance
                $repayment->paid_date = now();
                $repayment->paid_amount = $expectedAmount;
                $repayment->is_paid = true;
                $repayment->penalty = $penaltyAmount;
                $repayment->total_due = $totalDue;
                $repayment->save();

                // Vérifier si tout est remboursé
                if (!$repayment->credit->repayments->where('is_paid', false)->count()) {
                    $repayment->credit->is_paid = true;
                    $repayment->credit->save();
                }

                // Notification de remboursement automatique
                Notification::create([
                    'user_id' => $member->id,
                    'title' => 'Remboursement Automatique',
                    'message' => "Votre échéance du {$repayment->due_date} a été remboursée automatiquement avec succès.",
                    'read' => false,
                ]);
            } else {
                // Solde insuffisant → appliquer pénalité sans virement
                // if ($repayment->penalty != $penaltyAmount) {
                $repayment->penalty = $penaltyAmount;
                $repayment->total_due = $totalDue;
                $repayment->save();

                // Enregistrer la transaction (solde négatif)
                Transaction::create([
                    'account_id' => $account->id,
                    'user_id' => $member->id,
                    'type' => 'penalite_de_credit',
                    'currency' => $credit->currency,
                    'amount' => round($penaltyAmount, 2),
                    'balance_after' => $account->balance,
                    'description' => "Pénalité appliquée sur l'échéance du {$repayment->due_date}",
                ]);

                // Notification de pénalité
                Notification::create([
                    'user_id' => $member->id,
                    'title' => 'Retard de remboursement',
                    'message' => "Votre échéance du {$repayment->due_date} est en retard de {$daysLate} jour(s). Une pénalité de " . round($penaltyAmount, 2) . " a été appliquée.",
                    'read' => false,
                ]);
                // }
            }
        }

        $this->info(count($overdue) . ' échéances en retard vérifiées.');
    }
}
