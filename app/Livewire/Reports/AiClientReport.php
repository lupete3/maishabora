<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use App\Models\User;
use App\Models\Account;
use App\Services\AIReportService;
use Carbon\Carbon;

class AiClientReport extends Component
{
    public $summaryClientInsights;
    public $loading = false;

    public function mount()
    {
        $this->generateReport();
    }

    public function generateReport()
    {
        $this->loading = true;
        $ai = new AIReportService();

        // 1. New clients in last 30 days
        $newClients = User::where('role', 'client')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->get();

        // 2. Global account state
        $accounts = Account::all();

        // 3. AI Summary
        $this->summaryClientInsights = $ai->summarizeClientInsights($newClients, $accounts);

        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.reports.ai-client-report');
    }
}
