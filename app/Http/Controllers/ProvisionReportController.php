<?php

namespace App\Http\Controllers;

use App\Services\ProvisionCalculator;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ProvisionReportController extends Controller
{
    public function export(Request $request)
    {
        $currency = $request->get('currency', 'all');
        $calculator = app(ProvisionCalculator::class);

        $parIndicators = $calculator->calculatePARIndicators($currency);
        $statsByClassification = $calculator->getStatsByClassification($currency);

        $detailsByClassification = [];
        foreach ($statsByClassification->keys() as $classification) {
            $detailsByClassification[$classification] = $calculator->getCreditsByClassification($classification, $currency);
        }

        $totaux = [
            'outstanding' => $statsByClassification->sum('outstanding'),
            'provision' => $statsByClassification->sum('provision'),
        ];

        $data = [
            'currency' => $currency,
            'date' => Carbon::now()->format('d/m/Y'),
            'parIndicators' => $parIndicators,
            'statsByClassification' => $statsByClassification,
            'detailsByClassification' => $detailsByClassification,
            'totaux' => $totaux
        ];

        $pdf = PDF::loadView('pdf.provisions', $data)->setPaper('a4', 'landscape');

        $filename = 'rapport_provisions_' . ($currency == 'all' ? 'global' : $currency) . '_' . date('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }
}
