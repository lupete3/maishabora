<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use App\Models\Credit;
use App\Models\Repayment;
use App\Services\AIReportService;
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

        // 1. Fetch active credits (not yet paid)
        $credits = Credit::where('is_paid', false)->get();

        // 2. Fetch delayed repayments
        $delays = Repayment::where('is_paid', false)
            ->where('due_date', '<', Carbon::now())
            ->get();

        // 3. Generate AI summary
        $this->summaryCreditPerformance = $ai->summarizeCreditPerformance($credits, $delays);

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.reports.ai-credit-report');
    }
}
