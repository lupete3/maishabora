<?php

namespace App\Http\Controllers;

use App\Models\MainCashRegister;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ManageCashRegisterController extends Controller
{
    public function index()
    {
        return view("admin.manage-cash-register");
    }

    public function generate(Request $request)
    {
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $search = $request->input('search');
        $format = $request->input('format', 'pdf');

        // Récupérer toutes les transactions liées à la caisse centrale avec filtres
        $query = Transaction::where(function ($query) {
            $query->where('type', 'like', '%fonds%')
                ->orWhere('type', 'like', '%sortie%')
                ->orWhere('type', 'like', '%virement vers caisse centrale%')
                ->orWhere('type', 'like', '%octroi_de_credit_client%')
                ->orWhere('type', 'like', '%frais_retrait_carte_adhesion%')
                ->orWhere('type', 'like', '%virement_caisse_sortant%')
                ->orWhere('type', 'like', '%paie_sortant%');
        })
            ->when($startDate, function ($query) use ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', '%' . $search . '%')
                        ->orWhere('currency', 'like', '%' . $search . '%')
                        ->orWhere('type', 'like', '%' . $search . '%');
                });
            })
            ->latest();

        if ($format === 'excel') {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\CentralCashExport($query),
                "rapport_caisse_centrale_" . now()->format("Ymd_His") . ".xlsx"
            );
        }

        $transactions = $query->get();

        // Récupérer les soldes actuels
        $balances = MainCashRegister::all()->pluck('balance', 'currency')->toArray();

        // Calcul des totaux par type et devise
        $totaux = [
            'entrées' => [],
            'sorties' => []
        ];

        foreach (['USD', 'CDF'] as $currency) {
            $totaux['entrées'][$currency] = $transactions->where('currency', $currency)
                ->filter(fn($t) => str_contains($t->type, 'fonds') || str_contains($t->type, 'virement vers caisse centrale'))
                ->sum('amount');

            $totaux['sorties'][$currency] = $transactions->where('currency', $currency)
                ->filter(fn($t) => str_contains($t->type, 'sortie') || str_contains($t->type, 'octroi_de_credit_client') || str_contains($t->type, 'paie_sortant'))
                ->sum('amount');
        }

        return response()->streamDownload(function () use ($transactions, $balances, $totaux) {
            $pdf = Pdf::loadView('pdf.central-cash-report', compact('transactions', 'balances', 'totaux'));
            echo $pdf->stream();
        }, "rapport_caisse_centrale_" . now()->format("Ymd_His") . ".pdf");
    }

}
