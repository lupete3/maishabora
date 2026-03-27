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

        // Define categories
        $depositTypes = ['mise_quotidienne', 'dépôt', 'vente_carte_adhesion', 'remboursement_credit', 'frais_inscription'];
        $withdrawalTypes = ['retrait_carte_adhesion', 'retrait'];
        $otherInflowTypes = ['virement_caisse_entrant', 'frais_credit_pour_retrait', 'transfert_entrant', 'conversion_devise_credit'];
        $otherOutflowTypes = ['virement_caisse_sortant', 'décaissement', 'annulation_vente_carte_adhesion', 'paiement_salaire', 'transfert_sortant', 'conversion_devise_debit'];

        $allInflowTypes = array_merge($depositTypes, $otherInflowTypes);
        $allOutflowTypes = array_merge($withdrawalTypes, $otherOutflowTypes);

        // Fetch daily transactions
        $dailyTransactions = Transaction::where('user_id', $cloture->user_id)
            ->whereDate('created_at', $cloture->closing_date)
            ->get();

        // 1. Core operations (Deposits & Withdrawals)
        $cloture->deposits = $dailyTransactions->whereIn('type', $depositTypes);
        $cloture->withdrawals = $dailyTransactions->whereIn('type', $withdrawalTypes);

        // 2. Transfers and other flows
        $cloture->other_flows = $dailyTransactions->whereIn('type', array_merge($otherInflowTypes, $otherOutflowTypes));

        // Fetch previous closure for the same agent
        $previousCloture = Cloture::where('user_id', $cloture->user_id)
            ->where('closing_date', '<', $cloture->closing_date)
            ->latest('closing_date')
            ->first();

        $cloture->previous_logical_usd = $previousCloture ? $previousCloture->logical_usd : 0;
        $cloture->previous_logical_cdf = $previousCloture ? $previousCloture->logical_cdf : 0;

        // Totals for specific tables
        $cloture->pure_deposits_usd = $cloture->deposits->where('currency', 'USD')->sum('amount');
        $cloture->pure_deposits_cdf = $cloture->deposits->where('currency', 'CDF')->sum('amount');
        $cloture->pure_withdrawals_usd = $cloture->withdrawals->where('currency', 'USD')->sum('amount');
        $cloture->pure_withdrawals_cdf = $cloture->withdrawals->where('currency', 'CDF')->sum('amount');

        // Sub-totals for Transfer table
        $cloture->other_inflows_usd = $cloture->other_flows->where('currency', 'USD')->whereIn('type', $otherInflowTypes)->sum('amount');
        $cloture->other_inflows_cdf = $cloture->other_flows->where('currency', 'CDF')->whereIn('type', $otherInflowTypes)->sum('amount');
        $cloture->other_outflows_usd = $cloture->other_flows->where('currency', 'USD')->whereIn('type', $otherOutflowTypes)->sum('amount');
        $cloture->other_outflows_cdf = $cloture->other_flows->where('currency', 'CDF')->whereIn('type', $otherOutflowTypes)->sum('amount');

        // Totals for accounting proof (Consolidated)
        $cloture->total_inflows_usd = $dailyTransactions->where('currency', 'USD')->whereIn('type', $allInflowTypes)->sum('amount');
        $cloture->total_inflows_cdf = $dailyTransactions->where('currency', 'CDF')->whereIn('type', $allInflowTypes)->sum('amount');
        $cloture->total_outflows_usd = $dailyTransactions->where('currency', 'USD')->whereIn('type', $allOutflowTypes)->sum('amount');
        $cloture->total_outflows_cdf = $dailyTransactions->where('currency', 'CDF')->whereIn('type', $allOutflowTypes)->sum('amount');

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
