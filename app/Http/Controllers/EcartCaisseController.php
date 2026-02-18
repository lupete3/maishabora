<?php

namespace App\Http\Controllers;

use App\Models\EcartCaisse;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EcartCaisseController extends Controller
{
    /**
     * Affiche le dashboard de gestion des écarts de caisse.
     */
    public function index()
    {
        return view('ecarts-caisse');
    }

    public function exportPdf(Request $request)
    {
        $query = EcartCaisse::with(['user', 'cloture', 'resolvedBy']);

        // Apply same filters as Livewire component
        if ($request->filterAgent) {
            $query->where('user_id', $request->filterAgent);
        }
        if ($request->filterStatus) {
            $query->where('status', $request->filterStatus);
        }
        if ($request->filterCurrency) {
            $query->where('currency', $request->filterCurrency);
        }
        if ($request->filterType) {
            $query->where('type', $request->filterType);
        }
        if ($request->filterDateFrom) {
            $query->whereDate('created_at', '>=', $request->filterDateFrom);
        }
        if ($request->filterDateTo) {
            $query->whereDate('created_at', '<=', $request->filterDateTo);
        }

        // Permissions
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'comptable', 'caissier'])) {
            $query->where('user_id', $user->id);
        }

        $ecarts = $query->latest()->get();

        $filters = [
            'agent_name' => $request->filterAgent ? User::find($request->filterAgent)?->name : null,
            'status' => $request->filterStatus,
            'currency' => $request->filterCurrency,
            'type' => $request->filterType,
            'date_from' => $request->filterDateFrom,
            'date_to' => $request->filterDateTo,
        ];

        $pdf = Pdf::loadView('pdf.ecarts-caisse', compact('ecarts', 'filters'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('rapport_ecarts_caisse_' . date('Ymd_His') . '.pdf');
    }
}
