<?php

namespace App\Livewire;

use App\Models\Repayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RepaymentsReportExport;

class RepaymentReport extends Component
{
    public $reportType = 'daily'; // 'daily', 'weekly', 'monthly', 'yearly', 'custom'
    public $startDate;
    public $endDate;
    public $currency = 'all'; // 'all', 'USD', 'CDF'

    public function mount()
    {
        $this->startDate = now()->startOfDay()->toDateString();
        $this->endDate = now()->endOfDay()->toDateString();
    }

    public function render()
    {
        $report = $this->generateReport();
        return view('livewire.repayment-report', [
            'data' => $report['data'],
            'totals' => $report['totals']
        ]);
    }

    public function generateReport()
    {
        $query = Repayment::with(['credit.user']) // Eager loading
            ->where('is_paid', true);

        // Dates
        switch ($this->reportType) {
            case 'daily':
                $this->startDate = now()->startOfDay()->toDateString();
                $this->endDate = now()->endOfDay()->toDateString();
                break;
            case 'weekly':
                $this->startDate = now()->startOfWeek()->toDateString();
                $this->endDate = now()->endOfWeek()->toDateString();
                break;
            case 'monthly':
                $this->startDate = now()->startOfMonth()->toDateString();
                $this->endDate = now()->endOfMonth()->toDateString();
                break;
            case 'yearly':
                $this->startDate = now()->startOfYear()->toDateString();
                $this->endDate = now()->endOfYear()->toDateString();
                break;
            case 'custom':
                if (!$this->startDate || !$this->endDate) {
                    return collect(); // rien si les dates ne sont pas définies
                }
                break;
        }

        // Appliquer la plage de dates
        $query->whereBetween('paid_date', [$this->startDate, $this->endDate]);

        // Filtrer par devise
        if ($this->currency !== 'all') {
            $query->whereHas('credit', function ($q) {
                $q->where('currency', $this->currency);
            });
        }

        $data = $query->orderBy('paid_date', 'desc')->get();

        // 🔹 Totaux groupés par devise
        $totals = $data->groupBy(fn($r) => $r->credit->currency ?? 'N/A')
            ->map(function ($items) {
                return [
                    'total_paid' => $items->sum('total_due'),
                    'total_penality' => $items->sum('penalty'),
                ];
            });

        return [
            'data' => $data,
            'totals' => $totals
        ];
    }

    public function exportPdf()
    {
        $report = $this->generateReport();

        $pdf = Pdf::loadView('pdf.repayments-report', [
            'data' => $report['data'],   // ✅ juste la collection de remboursements
            'reportType' => $this->reportType,
            'currency' => $this->currency,
            'totals' => $report['totals'], // ✅ totaux séparés
        ])->setPaper('A4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, "repayments_report_" . now()->format('Ymd_His') . ".pdf");
    }

    public function exportExcel()
    {
        return Excel::download(
            new RepaymentsReportExport($this->generateReport()),
            'repayments_report_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
