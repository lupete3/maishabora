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

    public function exportTransactions($userId, $filter)
    {
        $user = User::findOrFail($userId);
        $now = now();
        $query = Transaction::where('user_id', $userId);

        switch ($filter) {
            case 'day':
                $query->whereDate('created_at', $now->toDateString());
                break;
            case 'month':
                $query->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year);
                break;
            case 'year':
                $query->whereYear('created_at', $now->year);
                break;
        }

        $transactions = $query->orderByDesc('created_at')->get();

        $totalByCurrency = $transactions->groupBy('currency')->map(function ($group) {
            return $group->sum('amount');
        });

        $pdf = Pdf::loadView('pdf.agent-transactions', compact('user', 'transactions', 'filter', 'totalByCurrency'));
        return $pdf->download("transactions_{$user->id}_{$filter}.pdf");
    }
}
