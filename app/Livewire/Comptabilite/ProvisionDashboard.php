<?php

namespace App\Livewire\Comptabilite;

use App\Services\ProvisionCalculator;
use Livewire\Component;

class ProvisionDashboard extends Component
{
    public $parIndicators = [];
    public $statsByClassification = [];
    public $totalProvisions = 0;
    public $currency = 'all'; // Filtre de devise : 'all', 'USD', 'CDF'

    public function mount()
    {
        $this->refreshData();
    }

    public function render()
    {
        return view('livewire.comptabilite.provision-dashboard');
    }

    /**
     * Mise à jour du filtre de devise
     */
    public function updatedCurrency()
    {
        $this->refreshData();
    }

    /**
     * Rafraîchir les données
     */
    public function refreshData()
    {
        $calculator = app(ProvisionCalculator::class);

        $this->parIndicators = $calculator->calculatePARIndicators($this->currency);
        $this->statsByClassification = $calculator->getStatsByClassification($this->currency);
        $this->totalProvisions = $this->statsByClassification->sum('provision');
    }

    /**
     * Recalculer toutes les provisions
     */
    public function calculateProvisions()
    {
        $calculator = app(ProvisionCalculator::class);
        $provisions = $calculator->calculateAll(generateJournalEntries: false);

        notyf()->success("Provisions recalculées : {$provisions->count()} crédits traités");
        $this->refreshData();
    }

    /**
     * Générer les écritures comptables
     */
    public function generateJournalEntries()
    {
        $calculator = app(ProvisionCalculator::class);
        $provisions = $calculator->calculateAll(generateJournalEntries: true);

        notyf()->success("Écritures comptables générées pour {$provisions->count()} provisions");
        $this->refreshData();
    }
}
