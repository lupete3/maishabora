<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\Provision;
use App\Models\ProvisionSetting;
use App\Models\Repayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service de calcul automatique des provisions sur créances douteuses
 */
class ProvisionCalculator
{
    private AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Calculer les provisions pour tous les crédits actifs
     */
    public function calculateAll(bool $generateJournalEntries = false): Collection
    {
        $credits = Credit::where('is_paid', false)->get();
        $provisions = collect();

        foreach ($credits as $credit) {
            $provision = $this->calculateForCredit($credit);
            if ($provision) {
                $provisions->push($provision);

                // Générer écriture comptable si demandé
                if ($generateJournalEntries && $provision->provision_amount > 0) {
                    $this->accountingService->recordProvision(
                        $credit,
                        $provision->provision_amount,
                        $provision->classification
                    );
                }
            }
        }

        return $provisions;
    }

    /**
     * Calculer la provision pour un crédit spécifique
     */
    public function calculateForCredit(Credit $credit): ?Provision
    {
        // Classifier le crédit selon jours de retard
        $classification = $this->classifyCredit($credit);

        // Obtenir le taux de provision
        $rate = $this->getProvisionRate($classification);

        // Calculer capital restant dû
        $outstandingAmount = $this->getOutstandingAmount($credit);

        // Calculer montant de provision
        $provisionAmount = ($outstandingAmount * $rate) / 100;

        // Vérifier si provision existe déjà pour aujourd'hui
        $existingProvision = Provision::where('credit_id', $credit->id)
            ->whereDate('calculated_at', now())
            ->first();

        if ($existingProvision) {
            // Mettre à jour provision existante
            $existingProvision->update([
                'classification' => $classification,
                'provision_rate' => $rate,
                'outstanding_amount' => $outstandingAmount,
                'provision_amount' => $provisionAmount,
            ]);
            return $existingProvision;
        } else {
            // Créer nouvelle provision
            return Provision::create([
                'credit_id' => $credit->id,
                'classification' => $classification,
                'provision_rate' => $rate,
                'outstanding_amount' => $outstandingAmount,
                'provision_amount' => $provisionAmount,
                'currency' => $credit->currency,
                'calculated_at' => now(),
                'notes' => "Provision calculée automatiquement - {$classification}",
            ]);
        }
    }

    /**
     * Classifier une échéance individuelle selon jours de retard
     */
    public function classifyRepayment(Repayment $repayment): string
    {
        if ($repayment->is_paid) {
            return 'saine';
        }

        $today = Carbon::now()->startOfDay();
        $dueDate = Carbon::parse($repayment->due_date)->startOfDay();

        if ($dueDate->lt($today) || $dueDate->equalTo($today)) {
            $daysOverdue = abs((int) $today->diffInDays($dueDate));

            if ($daysOverdue >= 1 && $daysOverdue <= 30) {
                return '1-30';
            } elseif ($daysOverdue > 30 && $daysOverdue <= 60) {
                return '31-60';
            } elseif ($daysOverdue > 60 && $daysOverdue <= 90) {
                return '61-90';
            } elseif ($daysOverdue > 90) {
                return '>90';
            }
        }

        return 'saine';
    }

    /**
     * Classifier un crédit selon l'échéance la plus en retard
     */
    public function classifyCredit(Credit $credit): string
    {
        $unpaidRepayments = $credit->repayments->where('is_paid', false);

        if ($unpaidRepayments->isEmpty()) {
            return 'saine';
        }

        $worstClass = 'saine';
        $classPriority = ['saine' => 0, '1-30' => 1, '31-60' => 2, '61-90' => 3, '>90' => 4];

        foreach ($unpaidRepayments as $repayment) {
            $class = $this->classifyRepayment($repayment);
            if ($classPriority[$class] > $classPriority[$worstClass]) {
                $worstClass = $class;
            }
        }

        return $worstClass;
    }

    /**
     * Obtenir le taux de provision pour une classification
     */
    public function getProvisionRate(string $classification): float
    {
        $setting = ProvisionSetting::where('classification', $classification)->first();
        return $setting ? $setting->rate : 0.0;
    }

    /**
     * Calculer capital restant dû (Capital seul, excluant intérêts et pénalités)
     * Aligné sur le "fond déboursé" pour limiter le risque réel
     */
    /**
     * Calculer capital restant dû (Principal uniquement)
     * Basé sur le nombre d'échéances payées pour ignorer les intérêts/pénalités dans le risque.
     */
    private function getOutstandingAmount(Credit $credit): float
    {
        $totalInstallments = max(1, (int) $credit->installments);
        $principalPerInstallment = (float) $credit->amount / $totalInstallments;

        // On compte les échéances marquées comme payées
        $paidCount = $credit->repayments->where('is_paid', true)->count();

        $outstanding = (float) $credit->amount - ($paidCount * $principalPerInstallment);

        return max(0, $outstanding);
    }

    /**
     * Calculer les indicateurs PAR (Portfolio At Risk)
     */
    public function calculatePARIndicators(string $currency = 'all'): array
    {
        $query = Credit::where('is_paid', false);

        if ($currency !== 'all') {
            $query->where('currency', $currency);
        }

        $credits = $query->get();

        $totalOutstanding = 0;
        $par30 = 0;
        $par60 = 0;
        $par90 = 0;

        foreach ($credits as $credit) {
            $totalInstallments = max(1, (int) $credit->installments);
            $principalPerInstallment = (float) $credit->amount / $totalInstallments;

            $unpaidRepayments = $credit->repayments->where('is_paid', false);

            foreach ($unpaidRepayments as $repayment) {
                $totalOutstanding += $principalPerInstallment;
                $classification = $this->classifyRepayment($repayment);

                if (in_array($classification, ['1-30', '31-60', '61-90', '>90'])) {
                    $par30 += $principalPerInstallment;
                }
                if (in_array($classification, ['31-60', '61-90', '>90'])) {
                    $par60 += $principalPerInstallment;
                }
                if (in_array($classification, ['61-90', '>90'])) {
                    $par90 += $principalPerInstallment;
                }
            }
        }

        return [
            'total_outstanding' => $totalOutstanding,
            'par30' => $par30,
            'par60' => $par60,
            'par90' => $par90,
            'par30_rate' => $totalOutstanding > 0 ? ($par30 / $totalOutstanding) * 100 : 0,
            'par60_rate' => $totalOutstanding > 0 ? ($par60 / $totalOutstanding) * 100 : 0,
            'par90_rate' => $totalOutstanding > 0 ? ($par90 / $totalOutstanding) * 100 : 0,
        ];
    }

    /**
     * Obtenir statistiques par classification
     */
    public function getStatsByClassification(string $currency = 'all'): Collection
    {
        $query = Credit::where('is_paid', false)->with('repayments');

        if ($currency !== 'all') {
            $query->where('currency', $currency);
        }

        $credits = $query->get();

        $stats = [
            'saine' => ['count' => 0, 'outstanding' => 0, 'provision' => 0],
            '1-30' => ['count' => 0, 'outstanding' => 0, 'provision' => 0],
            '31-60' => ['count' => 0, 'outstanding' => 0, 'provision' => 0],
            '61-90' => ['count' => 0, 'outstanding' => 0, 'provision' => 0],
            '>90' => ['count' => 0, 'outstanding' => 0, 'provision' => 0],
        ];

        foreach ($credits as $credit) {
            $totalInstallments = max(1, (int) $credit->installments);
            $principalPerInstallment = (float) $credit->amount / $totalInstallments;

            $unpaidRepayments = $credit->repayments->where('is_paid', false);

            foreach ($unpaidRepayments as $repayment) {
                $classification = $this->classifyRepayment($repayment);
                $rate = $this->getProvisionRate($classification);

                $stats[$classification]['count']++; // On compte ici le nombre d'échéances concernées ou on peut garder le count par crédit si besoin
                $stats[$classification]['outstanding'] += $principalPerInstallment;
                $stats[$classification]['provision'] += ($principalPerInstallment * $rate) / 100;
            }
        }

        return collect($stats);
    }

    /**
     * Obtenir la liste des crédits pour une classification spécifique
     */
    public function getCreditsByClassification(string $classification, string $currency = 'all'): Collection
    {
        $query = Credit::where('is_paid', false)->with(['user', 'repayments']);

        if ($currency !== 'all') {
            $query->where('currency', $currency);
        }

        $credits = $query->get();
        $results = collect();

        foreach ($credits as $credit) {
            $totalInstallments = max(1, (int) $credit->installments);
            $principalPerInstallment = (float) $credit->amount / $totalInstallments;

            $matchingRepayments = $credit->repayments->where('is_paid', false)->filter(function ($r) use ($classification) {
                return $this->classifyRepayment($r) === $classification;
            });

            if ($matchingRepayments->isNotEmpty()) {
                // On calcule le cumul du principal pour cette catégorie
                $matchingCount = $matchingRepayments->count();
                $matchingPrincipal = $matchingCount * $principalPerInstallment;
                $rate = $this->getProvisionRate($classification);

                // On clone le crédit pour y attacher les infos spécifiques à cette classification
                $row = clone $credit;
                $row->outstanding_amount = $matchingPrincipal;
                $row->provision_rate = $rate;
                $row->provision_amount = ($matchingPrincipal * $rate) / 100;

                // Retard exact pour l'échéance la plus vieille de ce groupe
                $today = Carbon::now()->startOfDay();
                $oldestOfGroup = $matchingRepayments->sortBy(fn($r) => Carbon::parse($r->due_date)->timestamp)->first();
                $dueDate = Carbon::parse($oldestOfGroup->due_date)->startOfDay();

                $row->days_overdue = $dueDate->lt($today) ? abs((int) $today->diffInDays($dueDate)) : 0;

                $results->push($row);
            }
        }

        return $results;
    }
}
