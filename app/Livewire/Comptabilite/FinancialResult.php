<?php

namespace App\Livewire\Comptabilite;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Compte;
use Barryvdh\DomPDF\Facade\Pdf;

class FinancialResult extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $filter_currency = null; // USD, CDF...

    public function render()
    {
        // Charger uniquement les comptes de type Produit et Charge
        $accounts = Compte::with([
            'journals' => function ($q) {
                if ($this->filter_currency) {
                    $q->where('devise', $this->filter_currency);
                }
            }
        ])
            ->whereIn('type', ['Produit', 'Charge'])
            ->when($this->search, function ($q) {
                $q->where(function ($qq) {
                    $qq->where('code', 'like', "%{$this->search}%")
                        ->orWhere('intitule', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        // Totaux
        $totals = [
            'Produit' => 0,
            'Charge' => 0,
        ];

        // Préparer les données pour la vue
        $data = [
            'Produit' => [],
            'Charge' => [],
        ];

        foreach ($accounts as $account) {
            $debit = $account->journals->sum('montant_debit');
            $credit = $account->journals->sum('montant_credit');

            // Calcul du solde selon la nature du compte
            if ($account->type === 'Charge') {
                // Charges : Solde Débiteur naturel
                $solde = $debit - $credit;
            } else {
                // Produits : Solde Créditeur naturel
                $solde = $credit - $debit;
            }

            $totals[$account->type] += $solde;

            $data[$account->type][] = [
                'code' => $account->code,
                'intitule' => $account->intitule,
                'solde' => $solde
            ];
        }

        // Résultats
        $resultat = $totals['Produit'] - $totals['Charge'];

        return view('livewire.comptabilite.financial-result', [
            'data' => $data,
            'totals' => $totals,
            'resultat' => $resultat,
        ]);
    }

    public function export()
    {
        $accounts = Compte::with([
            'journals' => function ($q) {
                if ($this->filter_currency) {
                    $q->where('devise', $this->filter_currency);
                }
            }
        ])
            ->whereIn('type', ['Produit', 'Charge'])
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        $totals = [
            'Produit' => 0,
            'Charge' => 0,
        ];

        $data = [
            'Produit' => [],
            'Charge' => [],
        ];

        foreach ($accounts as $account) {
            $debit = $account->journals->sum('montant_debit');
            $credit = $account->journals->sum('montant_credit');

            if ($account->type === 'Charge') {
                $solde = $debit - $credit;
            } else {
                $solde = $credit - $debit;
            }

            $totals[$account->type] += $solde;

            $data[$account->type][] = [
                'code' => $account->code,
                'intitule' => $account->intitule,
                'solde' => $solde
            ];
        }

        $resultat = $totals['Produit'] - $totals['Charge'];

        $pdf = Pdf::loadView('pdf.financial-result', [
            'data' => $data,
            'totals' => $totals,
            'resultat' => $resultat,
            'currency' => $this->filter_currency ?? "Toutes devises",
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn() => print ($pdf->output()),
            'compte-de-resultat-' . now()->format('Ymd-His') . '.pdf'
        );
    }
}
