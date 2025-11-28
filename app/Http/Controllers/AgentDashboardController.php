<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        // Augmenter la limite de mémoire et le temps d'exécution pour les gros exports
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $user = User::findOrFail($userId);

        // Construction de la requête de base
        $query = Transaction::where('user_id', $userId);
        $query = $this->applyDateFilter($query, $filter);

        // 1. Calculer les totaux via SQL (beaucoup plus léger que de charger tous les modèles)
        // On clone la requête pour ne pas affecter la requête principale
        $totalByCurrency = $query->clone()
            ->selectRaw('currency, sum(amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');

        // 2. Compter le nombre de transactions (SQL)
        $transactionCount = $query->count();

        // 3. Récupérer les transactions
        // Utilisation de toBase() pour éviter l'hydratation des modèles Eloquent (gain mémoire important)
        // Sélection uniquement des colonnes nécessaires
        $transactions = $query->select(['created_at', 'type', 'amount', 'currency', 'description'])
            ->orderByDesc('created_at')
            ->toBase()
            ->get();

        $agentAccounts = User::where('id', $user->id)
            ->with([
                'agentAccounts' => function ($query) {
                    $query->orderBy('currency');
                }
            ])->get();

        // Génération PDF
        $pdf = Pdf::loadView('pdf.agent-transactions', compact(
            'user',
            'transactions',
            'filter',
            'totalByCurrency',
            'transactionCount',
            'agentAccounts'
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
