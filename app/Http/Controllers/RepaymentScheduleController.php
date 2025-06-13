<?php

// app/Http/Controllers/RepaymentScheduleController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Credit;
use App\Models\User;
use App\Models\Repayment;
use Illuminate\Support\Facades\Auth;

class RepaymentScheduleController extends Controller
{
    public function generate($creditId)
    {
        // Récupérer le crédit et les données associées
        $credit = Credit::with(['user', 'repayments'])->findOrFail($creditId);
        $member = $credit->user;
        $agent = Auth::user(); // ou récupère depuis un champ si nécessaire
        $repayments = $credit->repayments->sortBy('due_date');

        // Données à passer à la vue
        $data = compact('credit', 'member', 'agent', 'repayments');

        // Générer le PDF
        $pdf = Pdf::loadView('pdf.repayment-schedule', $data);

        return $pdf->stream("plan_rem_{$creditId}.pdf");
    }
}
