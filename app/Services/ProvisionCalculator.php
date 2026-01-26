<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\Provision;
use App\Models\ProvisionSetting;
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
     * Classifier un crédit selon jours de retard
     */
    public function classifyCredit(Credit $credit): string
    {
        if (!$credit->due_date) {
            return 'saine';
        }

        $dueDate = Carbon::parse($credit->due_date);
        $today = Carbon::now();

        // Si pas encore échu
        if ($today->lte($dueDate)) {
            return 'saine';
        }

        $daysOverdue = $today->diffInDays($dueDate);

        if ($daysOverdue >= 1 && $daysOverdue <= 30) {
            return '1-30';
        } elseif ($daysOverdue >= 31 && $daysOverdue <= 60) {
            return '31-60';
        } elseif ($daysOverdue >= 61 && $daysOverdue <= 90) {
            return '61-90';
        } else {
            return '>90';
        }
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
     * Calculer capital restant dû
     */
    private function getOutstandingAmount(Credit $credit): float
    {
        $totalPaid = $credit->repayments()->sum('paid_amount');
        return max(0, $credit->amount - $totalPaid);
    }

    /**
     * Calculer les indicateurs PAR (Portfolio At Risk)
     */
    public function calculatePARIndicators(): array
    {
        $credits = Credit::where('is_paid', false)->get();

        $totalOutstanding = 0;
        $par30 = 0;
        $par60 = 0;
        $par90 = 0;

        foreach ($credits as $credit) {
            $outstanding = $this->getOutstandingAmount($credit);
            $totalOutstanding += $outstanding;

            $classification = $this->classifyCredit($credit);

            if (in_array($classification, ['1-30', '31-60', '61-90', '>90'])) {
                $par30 += $outstanding;
            }
            if (in_array($classification, ['31-60', '61-90', '>90'])) {
                $par60 += $outstanding;
            }
            if (in_array($classification, ['61-90', '>90'])) {
                $par90 += $outstanding;
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
    public function getStatsByClassification(): Collection
    {
        $credits = Credit::where('is_paid', false)->get();

        $stats = [
            'saine' => ['count' => 0, 'outstanding' => 0, 'provision' => 0],
            '1-30' => ['count' => 0, 'outstanding' => 0, 'provision' => 0],
            '31-60' => ['count' => 0, 'outstanding' => 0, 'provision' => 0],
            '61-90' => ['count' => 0, 'outstanding' => 0, 'provision' => 0],
            '>90' => ['count' => 0, 'outstanding' => 0, 'provision' => 0],
        ];

        foreach ($credits as $credit) {
            $classification = $this->classifyCredit($credit);
            $outstanding = $this->getOutstandingAmount($credit);
            $rate = $this->getProvisionRate($classification);
            $provision = ($outstanding * $rate) / 100;

            $stats[$classification]['count']++;
            $stats[$classification]['outstanding'] += $outstanding;
            $stats[$classification]['provision'] += $provision;
        }

        return collect($stats);
    }
}
