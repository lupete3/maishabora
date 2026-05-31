<?php

namespace App\Http\Controllers;

use App\Models\LoanApplication;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CreditPrintController extends Controller
{
    /**
     * Print blank field data collection sheet.
     */
    public function printBlank()
    {
        Gate::authorize('afficher-demandes-credit', User::class);

        return view('credit.print.blank');
    }

    /**
     * Print pre-filled field and analysis summary sheet.
     */
    public function printFilled($id)
    {
        Gate::authorize('afficher-demandes-credit', User::class);

        $loan = LoanApplication::with([
            'user', 
            'business', 
            'cashflow', 
            'balance', 
            'securities', 
            'decision'
        ])->findOrFail($id);

        return view('credit.print.filled', compact('loan'));
    }
}
