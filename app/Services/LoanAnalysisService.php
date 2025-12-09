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
        $cashflow = $this->loanApplication->cashflow;
        $balance = $this->loanApplication->balance;
        $ratios = $this->loanApplication->ratios;

        return [
            'cashflow' => $cashflow ? $cashflow->toArray() : [],
            'balance' => $balance ? $balance->toArray() : [],
            'ratios' => $ratios ? $ratios->toArray() : [],
            'securities_total' => $this->loanApplication->securities->sum('valeur_estimee'),
        ];
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
