<?php

namespace App\Services;

use App\Models\LoanApplication;
use App\Models\LoanFinancialRatio;

class LoanAnalysisService
{
    protected LoanApplication $loanApplication;

    public function __construct(LoanApplication $loanApplication)
    {
        $this->loanApplication = $loanApplication;
    }

    public function fullAnalysis(): array
    {
        $this->loanApplication->load([
            'cashflow',
            'balance',
            'ratios',
            'securities',
            'decision',
            'fieldVisit',
            'businessProfile',
            'balanceSheetDetail',
            'cashflowAnalysis',
            'agentProposal',
        ]);

        $cashflow = $this->loanApplication->cashflow;
        $balance = $this->loanApplication->balance;

        if ($cashflow && $balance) {
            $this->calculateRatios($cashflow, $balance);
        }

        $ratios = $this->loanApplication->ratios()->first();

        return [
            'cashflow' => $cashflow?->toArray() ?? [],
            'balance' => $balance?->toArray() ?? [],
            'field_visit' => $this->loanApplication->fieldVisit?->toArray() ?? [],
            'business_profile' => $this->loanApplication->businessProfile?->toArray() ?? [],
            'balance_detail' => $this->loanApplication->balanceSheetDetail?->toArray() ?? [],
            'cashflow_analysis' => $this->loanApplication->cashflowAnalysis?->toArray() ?? [],
            'agent_proposal' => $this->loanApplication->agentProposal?->toArray() ?? [],
            'ratios' => $ratios?->toArray() ?? [],
            'securities_total' => $this->loanApplication->securities->sum('valeur_estimee'),
            'required_fields' => $this->requiredFieldStatus(),
        ];
    }

    public function calculateRatios($cashflow, $balance): LoanFinancialRatio
    {
        $actifCirculant = ($balance->cash ?? 0) + ($balance->creances ?? 0) + ($balance->stock ?? 0);
        $dettesCT = ($balance->dettes_formelles_ct ?? 0) + ($balance->dettes_informelles_ct ?? 0);

        $ratios = [
            'fonds_roulement' => $actifCirculant - $dettesCT,
            'independance_financiere' => ($balance->total_passif > 0) ? ($balance->fonds_propres / $balance->total_passif) : null,
            'liquidite_generale' => ($dettesCT > 0) ? ($actifCirculant / $dettesCT) : null,
            'solvabilite' => ($balance->total_dettes > 0) ? ($balance->total_actif / $balance->total_dettes) : null,
            'profitabilite_nette' => (($cashflow->chiffre_affaires_mensuel_estime ?? 0) > 0)
                ? ($cashflow->revenu_disponible_mensuel / $cashflow->chiffre_affaires_mensuel_estime) * 100
                : null,
            'ventes_mensuelles' => $cashflow->chiffre_affaires_mensuel_estime ?? 0,
            'benefice_net_mensuel' => $cashflow->revenu_disponible_mensuel ?? 0,
            'date_calcul' => now(),
        ];

        return LoanFinancialRatio::updateOrCreate(
            ['loan_application_id' => $this->loanApplication->id],
            $ratios
        );
    }

    public function simulateEmi($annualRate): float|int
    {
        $principal = $this->loanApplication->montant_demande;
        $months = $this->loanApplication->duree_mois;

        if ($months <= 0) {
            return 0;
        }

        if ($annualRate <= 0) {
            return $principal / $months;
        }

        $monthlyRate = ($annualRate / 100) / 12;
        $denominator = pow(1 + $monthlyRate, $months) - 1;

        if ($denominator == 0.0) {
            return 0;
        }

        $emi = ($principal * $monthlyRate * pow(1 + $monthlyRate, $months)) / $denominator;

        return round($emi, 2);
    }

    protected function requiredFieldStatus(): array
    {
        $visit = $this->loanApplication->fieldVisit;
        $business = $this->loanApplication->businessProfile;
        $cashflow = $this->loanApplication->cashflowAnalysis;
        $balance = $this->loanApplication->balanceSheetDetail;
        $proposal = $this->loanApplication->agentProposal;

        return [
            'date_visite' => filled($visit?->visit_date),
            'taux_change_si_cdf' => $this->loanApplication->currency !== 'CDF' || (($visit?->usd_cdf_rate ?? 0) > 0),
            'activite' => filled($business?->activity),
            'adresse_entreprise' => filled($business?->full_address),
            'ventes_retenues' => (($cashflow?->retained_sales ?? 0) > 0),
            'achats_retenus' => $cashflow !== null && $cashflow->retained_purchases >= 0,
            'charges_entreprise' => $cashflow !== null && $cashflow->business_expenses_total >= 0,
            'depenses_menage' => $cashflow !== null && $cashflow->household_expenses_total >= 0,
            'cash' => $balance !== null && $balance->cash >= 0,
            'stock' => $balance !== null && $balance->stock >= 0,
            'fonds_propres' => (($balance?->equity ?? 0) > 0),
            'conclusion_agent' => filled($proposal?->final_conclusions),
            'montant_propose' => (($proposal?->proposed_amount ?? 0) > 0),
            'taux_propose' => (($proposal?->proposed_rate ?? 0) > 0),
            'maturite_proposee' => (($proposal?->proposed_maturity_months ?? 0) > 0),
        ];
    }
}
