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

    protected $listeners = ['refreshAnalysis' => 'loadAnalysis'];

    public function mount($loan_application_id)
    {
        $this->loan_application_id = $loan_application_id;
        $this->loadAnalysis();
    }

    public function loadAnalysis()
    {
        $loan = LoanApplication::with(['cashflow', 'balance', 'ratios', 'securities', 'decision'])->find($this->loan_application_id);
        if (!$loan) return;

        $service = new LoanAnalysisService($loan);
        $this->analysis = $service->fullAnalysis();

        if ($this->annual_rate) {
            $this->analysis['emi'] = $service->simulateEmi($this->annual_rate);
        }
    }

    public function simulateEMI($rate)
    {
        $this->annual_rate = floatval($rate);
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

    

    public function render()
    {
        return view('livewire.credit.loan-analysis-viewer');
    }
}
