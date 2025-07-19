<?php

namespace App\Http\Controllers;

use App\Models\Cloture;
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

        $pdf = Pdf::loadView('receipts.cloture', compact('cloture'));
        return $pdf->download('Fiche-Cloture-'.$cloture->id.'.pdf');
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
