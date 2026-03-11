<?php

namespace App\Services;

use App\Models\LoanApplication;

class LoanAnalysisService
{
    protected $loanApplication;

    public function __construct(LoanApplication $loanApplication)
    {
        $this->loanApplication = $loanApplication;
    }

    public function fullAnalysis()
    {
        $this->loanApplication->load(['cashflow', 'balance', 'ratios', 'securities', 'decision']);

        $cashflow = $this->loanApplication->cashflow;
        $balance = $this->loanApplication->balance;

        // Recalcul des ratios si on a assez de données
        if ($cashflow && $balance) {
            $this->calculateRatios($cashflow, $balance);
        }

        $ratios = $this->loanApplication->ratios()->first();

        return [
            'cashflow' => $cashflow ? $cashflow->toArray() : [],
            'balance' => $balance ? $balance->toArray() : [],
            'ratios' => $ratios ? $ratios->toArray() : [],
            'securities_total' => $this->loanApplication->securities->sum('valeur_estimee'),
        ];
    }

    public function calculateRatios($cashflow, $balance)
    {
        $actifCirculant = ($balance->cash ?? 0) + ($balance->creances ?? 0) + ($balance->stock ?? 0);
        $dettesCT = ($balance->dettes_formelles_ct ?? 0) + ($balance->dettes_informelles_ct ?? 0);

        $ratios = [
            'fonds_roulement' => $actifCirculant - $dettesCT,
            'independance_financiere' => ($balance->total_passif > 0) ? ($balance->fonds_propres / $balance->total_passif) : 0,
            'liquidite_generale' => ($dettesCT > 0) ? ($actifCirculant / $dettesCT) : 0,
            'solvabilite' => ($balance->total_dettes > 0) ? ($balance->total_actif / $balance->total_dettes) : 10, // 10 par défaut si pas de dettes
            'profitabilite_nette' => (($cashflow->chiffre_affaires_mensuel_estime ?? 0) > 0)
                ? ($cashflow->revenu_disponible_mensuel / $cashflow->chiffre_affaires_mensuel_estime) * 100
                : 0,
            'ventes_mensuelles' => $cashflow->chiffre_affaires_mensuel_estime ?? 0,
            'benefice_net_mensuel' => $cashflow->revenu_disponible_mensuel ?? 0,
            'date_calcul' => now(),
        ];

        return \App\Models\LoanFinancialRatio::updateOrCreate(
            ['loan_application_id' => $this->loanApplication->id],
            $ratios
        );
    }

    public function simulateEmi($annualRate)
    {
        $principal = $this->loanApplication->montant_demande;
        $months = $this->loanApplication->duree_mois;

        if ($months <= 0)
            return 0;
        if ($annualRate <= 0)
            return $principal / $months;

        $monthlyRate = ($annualRate / 100) / 12;

        $emi = ($principal * $monthlyRate * pow(1 + $monthlyRate, $months)) / (pow(1 + $monthlyRate, $months) - 1);

        return round($emi, 2);
    }
}
