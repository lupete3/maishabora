<?php

namespace App\Livewire\Reports;

use App\Services\ManagementRatioService;
use Livewire\Component;

class ManagementRatios extends Component
{
    public $currency = 'USD';
    public $dateReference;
    public $ratios = [];
    public $totals = [];

    public function mount()
    {
        $this->dateReference = now()->format('Y-m-d');
        $this->loadRatios();
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['currency', 'dateReference'])) {
            $this->loadRatios();
        }
    }

    public function loadRatios()
    {
        $service = app(ManagementRatioService::class);
        $data = $service->getRatios($this->currency, $this->dateReference);
        
        $this->ratios = $data['ratios'];
        $this->totals = $data['totals'];
    }

    public function render()
    {
        return view('livewire.reports.management-ratios', [
            'currencies' => ['USD', 'CDF'],
        ]);
    }
}
