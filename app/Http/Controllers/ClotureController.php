<?php

namespace App\Http\Controllers;

use App\Models\Cloture;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ClotureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('cloturecaisseagent');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function exportFiche($id)
    {
        $cloture = Cloture::with(['user', 'billetages', 'validatedBy'])->findOrFail($id);

        // Fetch daily transactions for deposits and withdrawals
        $cloture->deposits = Transaction::where('user_id', $cloture->user_id)
            ->whereDate('created_at', $cloture->closing_date)
            ->whereIn('type', ['mise_quotidienne', 'dépôt', 'vente_carte_adhesion'])
            ->get();

        $cloture->withdrawals = Transaction::where('user_id', $cloture->user_id)
            ->whereDate('created_at', $cloture->closing_date)
            ->whereIn('type', ['retrait_carte_adhesion', 'retrait'])
            ->get();

        // Fetch previous closure for the same agent
        $previousCloture = Cloture::where('user_id', $cloture->user_id)
            ->where('closing_date', '<', $cloture->closing_date)
            ->latest('closing_date')
            ->first();

        $cloture->previous_logical_usd = $previousCloture ? $previousCloture->logical_usd : 0;
        $cloture->previous_logical_cdf = $previousCloture ? $previousCloture->logical_cdf : 0;

        // Totals for accounting proof
        $cloture->total_deposits_usd = $cloture->deposits->where('currency', 'USD')->sum('amount');
        $cloture->total_deposits_cdf = $cloture->deposits->where('currency', 'CDF')->sum('amount');
        $cloture->total_withdrawals_usd = $cloture->withdrawals->where('currency', 'USD')->sum('amount');
        $cloture->total_withdrawals_cdf = $cloture->withdrawals->where('currency', 'CDF')->sum('amount');

        $pdf = Pdf::loadView('receipts.cloture', compact('cloture'));
        return $pdf->download('Fiche-Cloture-' . $cloture->id . '.pdf');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Cloture $cloture)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cloture $cloture)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cloture $cloture)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cloture $cloture)
    {
        //
    }
}
