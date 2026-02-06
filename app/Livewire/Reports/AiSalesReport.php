<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use App\Models\MembershipCard;
use App\Services\AIReportService;
use Carbon\Carbon;

class AiSalesReport extends Component
{
    public $summarySalesPerformance;
    public $loading = false;

    public function mount()
    {
        $this->generateReport();
    }

    public function generateReport()
    {
        $this->loading = true;
        $ai = new AIReportService();

        // 1. Fetch card sales for last 30 days
        $cards = MembershipCard::where('created_at', '>=', Carbon::now()->subDays(30))
            ->get();

        // 2. AI Summary
        $this->summarySalesPerformance = $ai->summarizeSalesPerformance($cards);

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.reports.ai-sales-report');
    }
}
