<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use App\Models\Credit;
use App\Models\Repayment;
use App\Services\AIReportService;
use App\Services\CreditStatsService;
use Carbon\Carbon;

class AiCreditReport extends Component
{
    public $summaryCreditPerformance;
    public $loading = false;

    public function mount()
    {
        $this->generateReport();
    }

    public function generateReport()
    {
        $this->loading = true;
        $ai = new AIReportService();
        $statsService = new CreditStatsService();

        // 1. Fetch detailed stats
        $stats = $statsService->getGlobalStats();

        // 2. Generate AI summary
        $this->summaryCreditPerformance = $ai->summarizeCreditPerformance($stats);

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.reports.ai-credit-report');
    }
}
