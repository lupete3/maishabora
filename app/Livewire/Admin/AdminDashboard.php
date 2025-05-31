<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\CashRegister;
use Illuminate\Support\Facades\DB;

class AdminDashboard extends Component
{
    public $totalAdhesion = 0;
    public $totalContributions = 0;
    public $totalWithdrawals = 0;
    public $totalBalance = 0;

    public $monthlyReport = [];
    public $monthlyPie = [];
    public $monthlyPieChart = [];

    public $latestTransactions = [];


    public function mount()
    {
        // Total adhésions
        $this->totalAdhesion = CashRegister::where('type_operation', 'Adhésion')->sum('montant');

        // Total contributions quotidiennes
        $this->totalContributions = CashRegister::where('type_operation', 'Contribution')->sum('montant');

        // Total retraits
        $this->totalWithdrawals = CashRegister::whereIn('type_operation', ['Retrait', 'Terminé'])->sum('montant');

        // Solde net
        $this->totalBalance = $this->totalAdhesion + $this->totalContributions - $this->totalWithdrawals;


        // Rapport mensuel
        $this->monthlyReport = CashRegister::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as mois"),
            DB::raw("SUM(CASE WHEN type_operation = 'Adhésion' THEN montant ELSE 0 END) as adhesions"),
            DB::raw("SUM(CASE WHEN type_operation = 'Contribution' THEN montant ELSE 0 END) as contributions"),
            DB::raw("SUM(CASE WHEN type_operation IN ('Retrait', 'Terminé') THEN montant ELSE 0 END) as retraits")
        )
        ->groupBy('mois')
        ->orderBy('mois')
        ->get()
        ->map(function ($item) {
            return [
                'mois' => $item->mois,
                'adhesions' => (float) $item->adhesions,
                'contributions' => (float) $item->contributions,
                'retraits' => (float) $item->retraits,
                'solde' => (float) ($item->adhesions + $item->contributions - $item->retraits),
            ];
        });


        $this->monthlyPie = CashRegister::select(
            DB::raw("SUM(CASE WHEN type_operation = 'Adhésion' THEN montant ELSE 0 END) as adhesions"),
            DB::raw("SUM(CASE WHEN type_operation = 'Contribution' THEN montant ELSE 0 END) as contributions"),
            DB::raw("SUM(CASE WHEN type_operation IN ('Retrait', 'Terminé') THEN montant ELSE 0 END) as retraits")
        )->first();

        $solde = (float) ($this->monthlyPie->adhesions + $this->monthlyPie->contributions - $this->monthlyPie->retraits);

        $this->monthlyPieChart = [
            'labels' => ['Adhésions', 'Contributions', 'Retraits', 'Solde'],
            'data' => [
                (float) $this->monthlyPie->adhesions,
                (float) $this->monthlyPie->contributions,
                (float) $this->monthlyPie->retraits,
                $solde
            ]
        ];


        // Récupère les 5 dernières transactions globales
        $this->latestTransactions = CashRegister::latest()
            ->take(5)
            ->get()
            ->map(function ($transaction) {
                return [
                    'type' => $transaction->type_operation,
                    'amount' => $transaction->montant,
                    'direction' => in_array($transaction->type_operation, ['Adhésion', 'Contribution']) ? 'in' : 'out',
                    'reference_type' => class_basename($transaction->reference_type),
                    'created_at' => $transaction->created_at->format('d/m/Y'),
                ];
            });


    }

    public function render()
    {
        $first = $this->monthlyReport->first();
        $last = $this->monthlyReport->last();
        $growthRate = $first && $last && $first['solde'] > 0
            ? round((($last['solde'] - $first['solde']) / $first['solde']) * 100, 2)
            : 1;

        return view('livewire.admin.admin-dashboard', [
            'growthRate' => $growthRate,
        ]);
    }
}
