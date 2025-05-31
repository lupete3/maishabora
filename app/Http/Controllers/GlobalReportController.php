<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class GlobalReportController extends Controller
{
    public function generateMonthlyReport()
    {
        // Récupère les données financières globales
        $data = CashRegister::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mois")
            ->selectRaw("SUM(CASE WHEN type_operation = 'Adhésion' THEN montant ELSE 0 END) as adhesions")
            ->selectRaw("SUM(CASE WHEN type_operation = 'Contribution' THEN montant ELSE 0 END) as contributions")
            ->selectRaw("SUM(CASE WHEN type_operation IN ('Retrait', 'Terminé') THEN montant ELSE 0 END) as retraits")
            ->groupBy('mois')
            ->orderBy('mois', 'desc')
            ->take(12)
            ->get()
            ->map(function ($item) {
                return [
                    'mois' => $item->mois,
                    'adhesions' => $item->adhesions,
                    'contributions' => $item->contributions,
                    'retraits' => $item->retraits,
                    'solde' => $item->adhesions + $item->contributions - $item->retraits
                ];
            })->toArray();

        $totalAdhesion = array_sum(array_column($data, 'adhesions'));
        $totalContributions = array_sum(array_column($data, 'contributions'));
        $totalWithdrawals = array_sum(array_column($data, 'retraits'));
        $totalBalance = $totalAdhesion + $totalContributions - $totalWithdrawals;

        $pdf = Pdf::loadView('pdf.global-monthly-report', compact('data', 'totalAdhesion', 'totalContributions', 'totalWithdrawals', 'totalBalance'));

        return $pdf->download("rapport-mensuel-global-" . now()->format('Y-m-d') . ".pdf");
    }

    public function generateAnnualReport()
    {
        // Récupère les données des 12 derniers mois
        $data = CashRegister::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mois")
            ->selectRaw("SUM(CASE WHEN type_operation = 'Adhésion' THEN montant ELSE 0 END) as adhesions")
            ->selectRaw("SUM(CASE WHEN type_operation = 'Contribution' THEN montant ELSE 0 END) as contributions")
            ->selectRaw("SUM(CASE WHEN type_operation IN ('Retrait', 'Terminé') THEN montant ELSE 0 END) as retraits")
            ->where('created_at', '>=', now()->subYear())
            ->groupBy('mois')
            ->orderBy('mois', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'mois' => $item->mois,
                    'adhesions' => $item->adhesions,
                    'contributions' => $item->contributions,
                    'retraits' => $item->retraits,
                    'solde' => $item->adhesions + $item->contributions - $item->retraits
                ];
            })->toArray();

        $totalAdhesion = array_sum(array_column($data, 'adhesions'));
        $totalContributions = array_sum(array_column($data, 'contributions'));
        $totalWithdrawals = array_sum(array_column($data, 'retraits'));
        $totalBalance = $totalAdhesion + $totalContributions - $totalWithdrawals;

        // Génération du PDF
        $pdf = \PDF::loadView('pdf.global-annual-report', compact(
            'data',
            'totalAdhesion',
            'totalContributions',
            'totalWithdrawals',
            'totalBalance'
        ));

        return $pdf->download("rapport-annuel-" . now()->format('Y') . ".pdf");
    }
}
