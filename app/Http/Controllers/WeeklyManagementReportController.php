<?php

namespace App\Http\Controllers;

use App\Models\CompanyInformation;
use App\Services\WeeklyManagementReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class WeeklyManagementReportController extends Controller
{
    public function index(Request $request, WeeklyManagementReportService $service)
    {
        $report = $service->build(
            $request->query('start_date'),
            $request->query('end_date')
        );

        return view('reports.weekly-management', [
            'report' => $report,
            'startDate' => $report['period']['start']->toDateString(),
            'endDate' => $report['period']['end']->toDateString(),
            'company' => CompanyInformation::first(),
        ]);
    }

    public function exportPdf(Request $request, WeeklyManagementReportService $service)
    {
        $report = $service->build(
            $request->query('start_date'),
            $request->query('end_date')
        );

        $pdf = Pdf::loadView('pdf.weekly-management-report', [
            'report' => $report,
            'company' => CompanyInformation::first(),
        ])->setPaper('A4', 'portrait');

        $filename = 'rapport-hebdomadaire-' .
            $report['period']['start']->format('Ymd') . '-' .
            $report['period']['end']->format('Ymd') . '.pdf';

        return $pdf->stream($filename);
    }
}
