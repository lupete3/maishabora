<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Repayment;
use App\Models\Account;
use App\Models\AgentAccount;
use App\Models\Transaction;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CheckOverdueRepayments extends Command
{
    protected $signature = 'check:overdue-repayments';
    protected $description = 'Vérifie les échéances en retard et applique les remboursements ou pénalités';

    const MIN_BALANCE_USD = 5;
    const MIN_BALANCE_CDF = 5000;

    public function handle()
    {
        $today = Carbon::today();

        // Trouver toutes les échéances non payées dont la date d'échéance est passée
        $overdue = Repayment::where('due_date', '<=', $today)
            ->where('is_paid', false)
            ->get();

        foreach ($overdue as $repayment) {
            $credit = $repayment->credit;
            $member = $credit->user;

            // Récupérer le compte courant du membre
            $account = Account::firstOrCreate(
                [
                    'user_id'  => $member->id,
                    'currency' => $credit->currency,
                    'type'     => 'current',
                ],
                ['balance' => 0]
            );

            if ($account->status === 'Inactif') {
                continue;
            }

            // ---------------------------------------------------------------
            // 1. CALCUL DE LA PÉNALITÉ SUR LE SOLDE RESTANT
            // ---------------------------------------------------------------
            // Parts de l'échéance (utilise les nouvelles colonnes si disponibles)
            $principalAmount = floatval($repayment->principal_amount ?? $repayment->expected_amount);
            $interestAmount  = floatval($repayment->interest_amount ?? 0);

            // Soldes encore impayés
            $remainingPrincipal = max(0.0, $principalAmount - floatval($repayment->paid_principal));
            $remainingInterest  = max(0.0, $interestAmount  - floatval($repayment->paid_interest));
            $remainingExpected  = $remainingPrincipal + $remainingInterest;

            // Jours écoulés depuis le dernier calcul de pénalité (idempotent)
            $lastCalcDate = $repayment->last_penalty_calculation_date ?? $repayment->due_date;
            $daysLate     = max(0, Carbon::parse($lastCalcDate)->diffInDays($today));
            $newPenalty   = 0.0;

            if ($daysLate > 0) {
                $newPenalty = round($remainingExpected * 0.003 * $daysLate, 3); // 0.3 % / jour
                $repayment->penalty += $newPenalty;
                $repayment->last_penalty_calculation_date = $today;
            }

            // Pénalité encore impayée
            $remainingPenalty = max(0.0, floatval($repayment->penalty) - floatval($repayment->paid_penalty));

            // Total restant à régler sur cette échéance
            $totalDueRemaining = round($remainingExpected + $remainingPenalty, 3);

            // Mettre à jour total_due (somme cumulée : capital + intérêt + toutes pénalités)
            $repayment->total_due = round($principalAmount + $interestAmount + floatval($repayment->penalty), 3);
            $repayment->save();

            // Si tout est déjà soldé, marquer l'échéance et passer à la suivante
            if ($totalDueRemaining <= 0) {
                $repayment->is_paid   = true;
                $repayment->paid_date = now();
                $repayment->save();

                if (!$repayment->credit->repayments->where('is_paid', false)->count()) {
                    $repayment->credit->is_paid = true;
                    $repayment->credit->save();
                }
                continue;
            }

            if ($credit->credit_type === 'degressif') {

                // Capital restant avant cette échéance
                $remainingCapital = floatval($credit->amount);

                foreach ($credit->repayments->sortBy('due_date') as $schedule) {

                    $currentInterest = round(
                        $remainingCapital * (floatval($credit->interest_rate) / 100),
                        2
                    );

                    $capitalPart = round(
                        floatval($schedule->expected_amount) - $currentInterest,
                        2
                    );

                    // Si c'est l'échéance actuelle → on récupère son intérêt
                    if ($schedule->id == $repayment->id) {
                        $interestPart = $currentInterest;
                        break;
                    }

                    // Déduire le capital pour passer à l’échéance suivante
                    $remainingCapital -= $capitalPart;
                }
            } else {

                // Crédit constant
                $interestPart = round(
                    floatval($credit->amount) *
                        (floatval($credit->interest_rate) / 100),
                    2
                );
            }

            //Vérifier si le membre a assez de fonds (en respectant le solde minimum sauf si autorisé à tout retirer)
            $minBalance = ($credit->currency === 'USD') ? self::MIN_BALANCE_USD : self::MIN_BALANCE_CDF;
            if ($account->can_withdraw_all) {
                $minBalance = 0;
            }

            $availableBalance = floatval($account->balance) - $minBalance;

            if ($availableBalance > 0) {
                // On prélève au maximum ce qui est disponible
                $amountToPay = min($availableBalance, $totalDueRemaining);

                DB::transaction(function () use (
                    $account, $amountToPay, $credit, $repayment, $member,
                    $remainingPenalty, $remainingInterest, $remainingPrincipal, $today
                ) {
                    // ---- Allocation : Pénalité → Intérêt → Capital ----
                    $rem = $amountToPay;

                    $paidPen = min($rem, $remainingPenalty); $rem -= $paidPen;
                    $paidInt = min($rem, $remainingInterest); $rem -= $paidInt;
                    $paidPri = min($rem, $remainingPrincipal);

                    $totalPaidThisTime = round($paidPen + $paidInt + $paidPri, 3);

                    if ($totalPaidThisTime <= 0) {
                        return;
                    }

                    // Débiter le compte membre
                    $account->balance -= $totalPaidThisTime;
                    $account->save();

                    // Créditer le compte intérêts (agent 95)
                    if ($paidInt > 0) {
                        $agentAccount = AgentAccount::firstOrCreate(
                            ['user_id' => 95, 'currency' => $credit->currency],
                            ['balance' => 0]
                        );
                        $agentAccount->balance += $paidInt;
                        $agentAccount->save();

                        Transaction::create([
                            'account_id'       => null,
                            'agent_account_id' => $agentAccount->id,
                            'user_id'          => 95,
                            'type'             => 'Interêt du credit',
                            'currency'         => $credit->currency,
                            'amount'           => $paidInt,
                            'balance_after'    => $agentAccount->balance,
                            'description'      => "Interêt du credit #{$credit->id} - {$paidInt} {$credit->currency} - client {$member->code} {$member->name} {$member->postnom}",
                        ]);
                    }

                    // Créditer le compte pénalités (agent 472)
                    if ($paidPen > 0) {
                        $penalityAccount = AgentAccount::firstOrCreate(
                            ['user_id' => 472, 'currency' => $credit->currency],
                            ['balance' => 0]
                        );
                        $penalityAccount->balance += $paidPen;
                        $penalityAccount->save();

                        Transaction::create([
                            'account_id'       => null,
                            'agent_account_id' => $penalityAccount->id,
                            'user_id'          => 472,
                            'type'             => 'Pénalité du credit',
                            'currency'         => $credit->currency,
                            'amount'           => $paidPen,
                            'balance_after'    => $penalityAccount->balance,
                            'description'      => "Pénalité du credit #{$credit->id} - {$paidPen} {$credit->currency} - client {$member->code} {$member->name} {$member->postnom}",
                        ]);
                    }

                    // Transaction de débit client
                    Transaction::create([
                        'account_id'    => $account->id,
                        'user_id'       => $member->id,
                        'type'          => 'remboursement_de_credit',
                        'currency'      => $credit->currency,
                        'amount'        => $totalPaidThisTime,
                        'balance_after' => $account->balance,
                        'description'   => "Remboursement automatique partiel/total de l'échéance n°{$repayment->id}",
                    ]);

                    // Mettre à jour les colonnes de ventilation de l'échéance
                    $repayment->paid_penalty  = floatval($repayment->paid_penalty) + $paidPen;
                    $repayment->paid_interest = floatval($repayment->paid_interest) + $paidInt;
                    $repayment->paid_principal = floatval($repayment->paid_principal) + $paidPri;
                    $repayment->paid_amount   = floatval($repayment->paid_amount) + $totalPaidThisTime;

                    // Marquer comme payé si le total réglé couvre le total dû
                    $repayment->is_paid = ($repayment->paid_amount >= $repayment->total_due);
                    if ($repayment->is_paid) {
                        $repayment->paid_date = $today;
                    }
                    $repayment->save();
                    // si tout est remboursé
                    if (!$credit->repayments()->where('is_paid', false)->exists()) {
                        $credit->update([
                            'is_paid' => true,
                        ]);
                    }

                    // Notification membre
                    Notification::create([
                        'user_id' => $member->id,
                        'title'   => 'Remboursement Automatique',
                        'message' => "Un prélèvement automatique de " . number_format($totalPaidThisTime, 2) . " {$credit->currency} a été effectué pour votre échéance du {$repayment->due_date->format('d/m/Y')}.",
                        'read'    => false,
                    ]);

                    // Notification équipe
                    $usersToNotify = User::role(['Admin', 'Caissier', 'SUPER IT', 'Comptable'])->get();
                    $msg = "Remboursement automatique de " . number_format($totalPaidThisTime, 2)
                        . " {$credit->currency} effectué pour {$member->name} {$member->postnom} ({$member->code}) — échéance n°{$repayment->id}.";

                    foreach ($usersToNotify as $notifyUser) {
                        Notification::create([
                            'user_id' => $notifyUser->id,
                            'title'   => 'Remboursement automatique effectué',
                            'message' => $msg,
                            'read'    => false,
                        ]);
                    }
                });

            } else {
                // Rien à recalculer.
                // La pénalité et total_due ont déjà été mis à jour
                // au début du traitement.
            }
        }

        $this->info(count($overdue) . ' échéances en retard vérifiées.');
    }
}
