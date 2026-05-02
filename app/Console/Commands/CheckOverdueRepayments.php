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
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CheckOverdueRepayments extends Command
{
    protected $signature = 'check:overdue-repayments';
    protected $description = 'Vérifie les échéances en retard et applique les remboursements ou pénalités';

    const MIN_BALANCE_USD = 5;
    const MIN_BALANCE_CDF = 5000;

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


            //Vérifier si le membre a assez de fonds (en respectant le solde minimum sauf si autorisé à tout retirer)
            $minBalance = ($credit->currency === 'USD') ? self::MIN_BALANCE_USD : self::MIN_BALANCE_CDF;

            // Si le compte est autorisé à tout retirer, le solde minimum est de 0
            if ($account->can_withdraw_all) {
                $minBalance = 0;
            }

            if (($account->balance - $totalDue) >= $minBalance) {
                DB::transaction(function () use ($account, $totalDue, $credit, $penaltyAmount, $interestPart, $expectedAmount, $repayment, $member) {
                    //Débiter le compte du membre
                    $account->balance -= $totalDue;
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
                        'amount' => $totalDue,
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

                    // Notifier les utilisateurs concernés
                    $usersToNotify = User::role(['Admin', 'Caissier', 'SUPER IT', 'Comptable'])->get();
                    $notificationMessage = "Un remboursement de " . number_format($totalDue, 2) . " {$credit->currency} a été effectué pour le membre {$member->name} {$member->postnom} ({$member->code}) par " . (Auth::user() ? Auth::user()->name . "." . Auth::user()->postnom : "Système") . ".";

                    foreach ($usersToNotify as $notifyUser) {
                        Notification::create([
                            'user_id' => $notifyUser->id,
                            'title' => 'Remboursement automatique effectué',
                            'message' => $notificationMessage,
                            'read' => false,
                        ]);
                    }
                });
            } else {
                // insuffisant → appliquer pénalité sans virement
                if ($repayment->penalty != $penaltyAmount) {
                    $repayment->penalty = $penaltyAmount;
                    $repayment->total_due = $totalDue;
                    $repayment->save();

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
