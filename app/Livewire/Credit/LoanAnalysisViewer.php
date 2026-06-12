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
        if (!$loan)
            return;

        $service = new LoanAnalysisService($loan);
        $this->analysis = $service->fullAnalysis();
        $this->analysis['loan'] = $loan->toArray();

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

    // Helper pour badge couleur selon ratio
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
