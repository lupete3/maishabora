<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AgentDashboardController extends Controller
{
    public function index()
    {
        return view('agent-dashboard');
    }

    /**
     * Export transactions for a specific user with date filter.
     *
     * @param int $userId
     * @param string $filter
     * @return \Illuminate\Http\Response
     */
    public function exportTransactions(Request $request, $userId, $filter = 'day')
    {
        $user = User::findOrFail($userId);
        $now = now();

        $query = Transaction::where('user_id', $userId);

        $query = $this->applyDateFilter($query, $filter);   

        switch ($filter) {
            case 'day':
                $query->whereDate('created_at', $now->toDateString());
                break;
            case 'month':
                $query->whereMonth('created_at', $now->month)
                    ->whereYear('created_at', $now->year);
                break;
            case 'year':
                $query->whereYear('created_at', $now->year);
                break;
        }

        $transactions = $query->orderByDesc('created_at')->get();

        // Compter le nombre de transactions filtrées
        $transactionCount = $transactions->count();

        // Totaux par devise
        $totalByCurrency = $transactions->groupBy('currency')->map(function ($group) {
            return $group->sum('amount');
        });

        // Génération PDF avec tous les paramètres
        $pdf = Pdf::loadView('pdf.agent-transactions', compact(
            'user',
            'transactions',
            'filter',
            'totalByCurrency',
            'transactionCount'
        ));
        return $pdf->download("transactions_{$user->id}_{$filter}.pdf");
    }

    protected function applyDateFilter($query, $filter)
    {
        $now = now();

        return match ($filter) {
            'day' => $query->whereDate('created_at', $now->toDateString()),
            'month' => $query->whereMonth('created_at', $now->month)
                        ->whereYear('created_at', $now->year),
            'year' => $query->whereYear('created_at', $now->year),
            default => $query,
        };
    }
}
