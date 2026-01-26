<?php

namespace App\Services;

use App\Models\Compte;
use App\Models\Journal;
use App\Models\JournalType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service central pour la génération automatique des écritures comptables
 * Garantit la cohérence et la traçabilité de toutes les opérations
 */
class AccountingService
{
    /**
     * Enregistrer achat de carte de membre
     */
    public function recordMembershipPurchase($card, float $amount, string $currency): void
    {
        // Caisse (débit) / Cotisations membres (crédit)
        $this->createBalancedEntry(
            date: now()->format('Y-m-d'),
            libelle: "Achat carte membre #{$card->id}",
            reference: "CARD-{$card->id}",
            devise: $currency,
            debitAccount: $this->getCaisseAccount($currency),
            creditAccount: $this->getAccount('7061'), // Frais d'adhésion
            amount: $amount,
            journalType: 'Journal de caisse'
        );
    }

    /**
     * Enregistrer dépôt sur compte membre
     */
    public function recordDeposit($account, float $amount, string $currency): void
    {
        // Caisse (débit) / Épargne membres (crédit)
        $this->createBalancedEntry(
            date: now()->format('Y-m-d'),
            libelle: "Dépôt compte #{$account->id} - {$account->user->name}",
            reference: "DEP-{$account->id}-" . now()->timestamp,
            devise: $currency,
            debitAccount: $this->getCaisseAccount($currency),
            creditAccount: $this->getAccount('421'), // Comptes d'épargne courants
            amount: $amount,
            journalType: 'Journal de caisse'
        );
    }

    /**
     * Enregistrer retrait sur compte membre
     */
    public function recordWithdrawal($account, float $amount, string $currency): void
    {
        // Épargne membres (débit) / Caisse (crédit)
        $this->createBalancedEntry(
            date: now()->format('Y-m-d'),
            libelle: "Retrait compte #{$account->id} - {$account->user->name}",
            reference: "WIT-{$account->id}-" . now()->timestamp,
            devise: $currency,
            debitAccount: $this->getAccount('421'), // Comptes d'épargne courants
            creditAccount: $this->getCaisseAccount($currency),
            amount: $amount,
            journalType: 'Journal de caisse'
        );
    }

    /**
     * Enregistrer décaissement de crédit
     */
    public function recordCreditDisbursement($credit): void
    {
        // Crédits en cours (débit) / Caisse (crédit)
        $this->createBalancedEntry(
            date: $credit->start_date ?? now()->format('Y-m-d'),
            libelle: "Décaissement crédit #{$credit->id} - {$credit->user->name}",
            reference: "CREDIT-{$credit->id}",
            devise: $credit->currency,
            debitAccount: $this->getAccount('271'), // Crédits en cours
            creditAccount: $this->getCaisseAccount($credit->currency),
            amount: $credit->amount,
            journalType: 'Journal des crédits'
        );

        // Enregistrer frais de crédit si > 0
        if ($credit->frais_credit > 0) {
            $this->createBalancedEntry(
                date: $credit->start_date ?? now()->format('Y-m-d'),
                libelle: "Frais crédit #{$credit->id}",
                reference: "FRAIS-CREDIT-{$credit->id}",
                devise: $credit->currency,
                debitAccount: $this->getCaisseAccount($credit->currency),
                creditAccount: $this->getAccount('702'), // Commissions sur crédits
                amount: $credit->frais_credit,
                journalType: 'Journal des crédits'
            );
        }
    }

    /**
     * Enregistrer remboursement de crédit (principal uniquement)
     */
    public function recordRepayment($repayment, float $amount = null): void
    {
        $credit = $repayment->credit;
        $amountToRecord = $amount ?? $repayment->amount_paid;

        // Caisse (débit) / Crédits en cours (crédit)
        $this->createBalancedEntry(
            date: $repayment->payment_date ?? now()->format('Y-m-d'),
            libelle: "Remboursement crédit #{$credit->id} - {$credit->user->name}",
            reference: "REPAY-{$repayment->id}",
            devise: $repayment->currency,
            debitAccount: $this->getCaisseAccount($repayment->currency),
            creditAccount: $this->getAccount('271'), // Crédits en cours
            amount: $amountToRecord,
            journalType: 'Journal des crédits'
        );
    }

    /**
     * Enregistrer intérêts sur crédit
     */
    public function recordInterest($credit, float $interestAmount, string $currency): void
    {
        // Caisse (débit) / Produits d'intérêts (crédit)
        $this->createBalancedEntry(
            date: now()->format('Y-m-d'),
            libelle: "Intérêts crédit #{$credit->id}",
            reference: "INT-{$credit->id}-" . now()->timestamp,
            devise: $currency,
            debitAccount: $this->getCaisseAccount($currency),
            creditAccount: $this->getAccount('701'), // Intérêts sur crédits
            amount: $interestAmount,
            journalType: 'Journal des crédits'
        );
    }

    /**
     * Enregistrer pénalité de retard
     */
    public function recordLatePenalty($credit, float $penaltyAmount, string $currency): void
    {
        // Caisse (débit) / Pénalités de retard (crédit)
        $this->createBalancedEntry(
            date: now()->format('Y-m-d'),
            libelle: "Pénalité retard crédit #{$credit->id}",
            reference: "PEN-{$credit->id}-" . now()->timestamp,
            devise: $currency,
            debitAccount: $this->getCaisseAccount($currency),
            creditAccount: $this->getAccount('703'), // Pénalités de retard
            amount: $penaltyAmount,
            journalType: 'Journal des crédits'
        );
    }

    /**
     * Enregistrer transfert inter-caisses
     */
    public function recordTransfer($fromCaisse, $toCaisse, float $amount, string $currency): void
    {
        // Caisse destination (débit) / Caisse source (crédit)
        $this->createBalancedEntry(
            date: now()->format('Y-m-d'),
            libelle: "Transfert caisse {$fromCaisse} vers {$toCaisse}",
            reference: "TRANS-" . now()->timestamp,
            devise: $currency,
            debitAccount: $this->getCaisseAccount($currency, $toCaisse),
            creditAccount: $this->getCaisseAccount($currency, $fromCaisse),
            amount: $amount,
            journalType: 'Journal de caisse'
        );
    }

    /**
     * Enregistrer paiement de salaire
     */
    public function recordSalaryPayment($payroll, float $netAmount = null): void
    {
        $amountToRecord = $netAmount ?? $payroll->amount; // Utilise le montant passé ou le montant du payroll

        // Charges de personnel (débit) / Caisse (crédit)
        $this->createBalancedEntry(
            date: $payroll->payment_date ?? now()->format('Y-m-d'),
            libelle: "Salaire {$payroll->user->name} - " . date('m/Y'),
            reference: "SAL-{$payroll->id}",
            devise: $payroll->currency ?? 'CDF',
            debitAccount: $this->getAccount('641'), // Salaires et traitements
            creditAccount: $this->getCaisseAccount($payroll->currency ?? 'CDF'),
            amount: $amountToRecord,
            journalType: 'Journal des charges'
        );
    }

    /**
     * Enregistrer provision sur crédit
     */
    public function recordProvision($credit, float $provisionAmount, string $classification): void
    {
        // Dotations aux provisions (débit) / Provisions (crédit)
        $provisionAccount = match ($classification) {
            '1-30' => '4911',
            '31-60' => '4912',
            '61-90' => '4913',
            '>90' => '4914',
            default => '491'
        };

        $this->createBalancedEntry(
            date: now()->format('Y-m-d'),
            libelle: "Provision crédit #{$credit->id} - {$classification}j",
            reference: "PROV-{$credit->id}",
            devise: $credit->currency,
            debitAccount: $this->getAccount('6593'), // Dotations provisions
            creditAccount: $this->getAccount($provisionAccount),
            amount: $provisionAmount,
            journalType: 'Journal des opérations diverses'
        );
    }

    /**
     * Enregistrer reprise de provision
     */
    public function recordProvisionReversal($credit, float $amount, string $classification): void
    {
        $provisionAccount = match ($classification) {
            '1-30' => '4911',
            '31-60' => '4912',
            '61-90' => '4913',
            '>90' => '4914',
            default => '491'
        };

        // Provisions (débit) / Reprises de provisions (crédit)
        $this->createBalancedEntry(
            date: now()->format('Y-m-d'),
            libelle: "Reprise provision crédit #{$credit->id}",
            reference: "RPROV-{$credit->id}",
            devise: $credit->currency,
            debitAccount: $this->getAccount($provisionAccount),
            creditAccount: $this->getAccount('7593'), // Reprises de provisions
            amount: $amount,
            journalType: 'Journal des opérations diverses'
        );
    }

    /**
     * Enregistrer cotisation quotidienne
     */
    public function recordDailyContribution($membershipCard, float $amount, string $currency): void
    {
        // Caisse (débit) / Cotisations quotidiennes (crédit)
        $this->createBalancedEntry(
            date: now()->format('Y-m-d'),
            libelle: "Cotisation quotidienne carte #{$membershipCard->id}",
            reference: "COT-{$membershipCard->id}-" . now()->format('Ymd'),
            devise: $currency,
            debitAccount: $this->getCaisseAccount($currency),
            creditAccount: $this->getAccount('7062'), // Cotisations quotidiennes
            amount: $amount,
            journalType: 'Journal de caisse'
        );
    }

    // ==================== MÉTHODES PRIVÉES HELPERS ====================

    /**
     * Créer une écriture équilibrée (débit = crédit)
     * Principe comptable: Débit = Crédit TOUJOURS
     */
    private function createBalancedEntry(
        string $date,
        string $libelle,
        string $reference,
        string $devise,
        Compte $debitAccount,
        Compte $creditAccount,
        float $amount,
        string $journalType
    ): void {
        DB::transaction(function () use ($date, $libelle, $reference, $devise, $debitAccount, $creditAccount, $amount, $journalType) {
            $journalTypeId = $this->getJournalTypeId($journalType);
            $userId = Auth::id() ?? 1; // Fallback to system user

            // Ligne débit
            Journal::create([
                'date_operation' => $date,
                'libelle' => $libelle,
                'reference' => $reference,
                'devise' => $devise,
                'montant_debit' => $amount,
                'montant_credit' => 0,
                'type_operation' => 'debit',
                'compte_id' => $debitAccount->id,
                'type_journal_id' => $journalTypeId,
                'user_id' => $userId,
            ]);

            // Ligne crédit
            Journal::create([
                'date_operation' => $date,
                'libelle' => $libelle,
                'reference' => $reference,
                'devise' => $devise,
                'montant_debit' => 0,
                'montant_credit' => $amount,
                'type_operation' => 'credit',
                'compte_id' => $creditAccount->id,
                'type_journal_id' => $journalTypeId,
                'user_id' => $userId,
            ]);

            // Log pour traçabilité
            Log::info("Écriture comptable créée", [
                'reference' => $reference,
                'montant' => $amount,
                'devise' => $devise,
                'debit' => $debitAccount->code,
                'credit' => $creditAccount->code
            ]);
        });
    }

    /**
     * Obtenir un compte par code
     */
    private function getAccount(string $code): Compte
    {
        $compte = Compte::where('code', $code)->first();

        if (!$compte) {
            throw new \Exception("Compte comptable {$code} introuvable. Veuillez exécuter le seeder du plan comptable.");
        }

        return $compte;
    }

    /**
     * Obtenir le compte caisse approprié selon devise et type
     */
    private function getCaisseAccount(string $currency, ?string $type = 'centrale'): Compte
    {
        $code = match ($currency) {
            'USD' => $type === 'agent' ? '5731' : '571',
            'CDF' => $type === 'agent' ? '5732' : '572',
            default => throw new \Exception("Devise non supportée: {$currency}")
        };

        return $this->getAccount($code);
    }

    /**
     * Obtenir l'ID du type de journal (crée si n'existe pas)
     */
    private function getJournalTypeId(string $libelle): int
    {
        $journalType = JournalType::firstOrCreate(['libelle' => $libelle]);
        return $journalType->id;
    }
}
