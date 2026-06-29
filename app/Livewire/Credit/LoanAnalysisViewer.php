<?php

namespace App\Livewire\Credit;

use Livewire\Component;
use App\Models\LoanApplication;
use App\Services\LoanAnalysisService;

class LoanAnalysisViewer extends Component
{
    public $loan_application_id;
    public $analysis = [];
    public $annual_rate;
    public $aiAnalysis;
    public $isAnalyzing = false;

    protected $listeners = ['refreshAnalysis' => 'loadAnalysis'];

    public function mount($loan_application_id)
    {
        $this->loan_application_id = $loan_application_id;
        $this->loadAnalysis();
    }

    public function loadAnalysis()
    {
        $loan = LoanApplication::with([
            'user',
            'business',
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
        ])->find($this->loan_application_id);

        if (!$loan) return;

        $service = new LoanAnalysisService($loan);
        $this->analysis = $service->fullAnalysis();
        $this->analysis['loan'] = $loan->toArray();

        // --- Indicateurs de décision calculés depuis la proposition de l'agent ---
        $proposal          = $loan->agentProposal;
        $cashflowAnalysis  = $loan->cashflowAnalysis;
        $legacyCashflow    = $loan->cashflow;

        $proposedAmount = (float) ($proposal?->proposed_amount  ?? 0);
        $proposedRate   = (float) ($proposal?->proposed_rate    ?? 0);
        $proposedMonths = (int)   ($proposal?->proposed_maturity_months ?? 0);
        $gracePeriod    = (int)   ($proposal?->grace_period_months      ?? 0);

        // Calcul EMI depuis la proposition de l'agent
        $emiFromProposal = 0;
        if ($proposedAmount > 0 && $proposedMonths > 0) {
            $activeMonths = max(1, $proposedMonths - $gracePeriod);
            if ($proposedRate <= 0) {
                $emiFromProposal = $proposedAmount / $activeMonths;
            } else {
                $monthlyRate = ($proposedRate / 100) / 12;
                $denominator = pow(1 + $monthlyRate, $activeMonths) - 1;
                if ($denominator > 0) {
                    $emiFromProposal = ($proposedAmount * $monthlyRate * pow(1 + $monthlyRate, $activeMonths)) / $denominator;
                }
            }
        }

        // Capacité de remboursement mensuelle
        $repaymentCapacity = (float) (
            $cashflowAnalysis?->repayment_capacity
            ?? $legacyCashflow?->capacite_remboursement_mensuelle
            ?? 0
        );

        // Revenu mensuel disponible
        $revenuDisponible = (float) (
            $cashflowAnalysis?->available_income
            ?? $legacyCashflow?->revenu_disponible_mensuel
            ?? 0
        );

        // Taux d'effort = EMI / capacité de remboursement
        $tauxEffort = ($repaymentCapacity > 0 && $emiFromProposal > 0)
            ? round(($emiFromProposal / $repaymentCapacity) * 100, 2)
            : null;

        // Couverture des garanties = total garanties / montant proposé
        $totalSecurities     = (float) ($this->analysis['securities_total'] ?? 0);
        $couvertureGaranties = ($proposedAmount > 0)
            ? round(($totalSecurities / $proposedAmount) * 100, 2)
            : null;

        // Equilibre du bilan
        $totalActif  = (float) ($loan->balanceSheetDetail?->total_assets            ?? $loan->balance?->total_actif  ?? 0);
        $totalPassif = (float) ($loan->balanceSheetDetail?->total_liabilities_equity ?? $loan->balance?->total_passif ?? 0);
        $bilanEcart  = round($totalActif - $totalPassif, 2);

        // Coût total du crédit
        $totalRepayable = round($emiFromProposal * $proposedMonths, 2);
        $coutCredit     = round($totalRepayable - $proposedAmount, 2);

        // Recommandation automatique
        $scoreDecision = 0;
        $alertes = [];

        if ($tauxEffort !== null) {
            if ($tauxEffort <= 50)        $scoreDecision += 30;
            elseif ($tauxEffort <= 70)    { $scoreDecision += 15; $alertes[] = "Taux d'effort élevé ({$tauxEffort}%) — à surveiller"; }
            else                          { $alertes[] = "Taux d'effort critique ({$tauxEffort}%) — dépasse 70%"; }
        }
        if ($couvertureGaranties !== null) {
            if ($couvertureGaranties >= 150)      $scoreDecision += 30;
            elseif ($couvertureGaranties >= 100)  { $scoreDecision += 20; }
            elseif ($couvertureGaranties >= 80)   { $scoreDecision += 10; $alertes[] = "Couverture garanties insuffisante ({$couvertureGaranties}%)"; }
            else                                  { $alertes[] = "Couverture garanties très faible ({$couvertureGaranties}%)"; }
        }
        $ratios = $this->analysis['ratios'] ?? [];
        $liquidite = (float) ($ratios['liquidite_generale'] ?? 0);
        if ($liquidite >= 1.5)       $scoreDecision += 20;
        elseif ($liquidite >= 1)     $scoreDecision += 10;
        else                         $alertes[] = "Liquidité insuffisante ({$liquidite})";

        $indep = (float) ($ratios['independance_financiere'] ?? 0);
        if ($indep >= 0.7)           $scoreDecision += 20;
        elseif ($indep >= 0.5)       $scoreDecision += 10;
        else                         $alertes[] = "Indépendance financière faible ({$indep})";

        if ($scoreDecision >= 80)         $recommendation = ['label' => 'FAVORABLE',          'color' => 'success', 'icon' => 'bxs-check-circle'];
        elseif ($scoreDecision >= 50)     $recommendation = ['label' => 'FAVORABLE SOUS RÉSERVE', 'color' => 'warning', 'icon' => 'bxs-info-circle'];
        else                              $recommendation = ['label' => 'DÉFAVORABLE',         'color' => 'danger',  'icon' => 'bxs-x-circle'];

        $this->analysis['decision_indicators'] = [
            'proposed_amount'      => $proposedAmount,
            'proposed_rate'        => $proposedRate,
            'proposed_months'      => $proposedMonths,
            'grace_period'         => $gracePeriod,
            'emi_from_proposal'    => round($emiFromProposal, 2),
            'repayment_capacity'   => $repaymentCapacity,
            'revenu_disponible'    => $revenuDisponible,
            'taux_effort'          => $tauxEffort,
            'total_securities'     => $totalSecurities,
            'couverture_garanties' => $couvertureGaranties,
            'total_actif'          => $totalActif,
            'total_passif'         => $totalPassif,
            'bilan_ecart'          => $bilanEcart,
            'total_repayable'      => $totalRepayable,
            'cout_credit'          => $coutCredit,
            'score_decision'       => $scoreDecision,
            'alertes'              => $alertes,
            'recommendation'       => $recommendation,
        ];

        if ($this->annual_rate) {
            $this->analysis['emi'] = $service->simulateEmi($this->annual_rate);
        }
    }

    public function simulateEMI($rate)
    {
        if (is_numeric($rate) && (float) $rate > 0) {
            $this->annual_rate = (float) $rate;
        } else {
            $this->annual_rate = null;
            unset($this->analysis['emi']);
        }
        $this->loadAnalysis();
    }

    // Helper badge couleur selon ratio
    public function badgeColor($key, $value)
    {
        switch ($key) {
            case 'fonds_roulement':
            case 'liquidite_generale':
            case 'solvabilite':
                return $value >= 1 ? 'success' : 'danger';
            case 'independance_financiere':
                return $value >= 0.7 ? 'success' : 'warning';
            case 'profitabilite_nette':
                return $value >= 10 ? 'success' : 'warning';
            default:
                return 'secondary';
        }
    }

    public function analyzeWithAI()
    {
        $this->isAnalyzing = true;
        $loan = LoanApplication::find($this->loan_application_id);
        if ($loan) {
            $service = new \App\Services\AICreditAnalysisService();
            $this->aiAnalysis = $service->analyze($loan);
        }
        $this->isAnalyzing = false;
    }

    public function render()
    {
        return view('livewire.credit.loan-analysis-viewer');
    }
}
