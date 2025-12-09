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
        // Charger tous les comptes groupés par type
        $accounts = Compte::with(['journals' => function($q) {
                if ($this->filter_currency) {
                    $q->where('devise', $this->filter_currency);
                }
            }])
            ->when($this->search, function($q) {
                $q->where('code', 'like', "%{$this->search}%")
                  ->orWhere('intitule', 'like', "%{$this->search}%");
            })
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        // Totaux par type
        $totals = [
            'Actif'    => ['debit' => 0, 'credit' => 0, 'solde' => 0],
            'Passif'   => ['debit' => 0, 'credit' => 0, 'solde' => 0],
            'Produit' => ['debit' => 0, 'credit' => 0, 'solde' => 0],
            'Charge'  => ['debit' => 0, 'credit' => 0, 'solde' => 0],
        ];

        foreach ($accounts as $account) {
            $debit  = $account->journals->sum('montant_debit');
            $credit = $account->journals->sum('montant_credit');
            $solde  = $debit - $credit;

            if (isset($totals[$account->type])) {
                $totals[$account->type]['debit']  += $debit;
                $totals[$account->type]['credit'] += $credit;
                $totals[$account->type]['solde']  += $solde;
            }
        }

        // Différences
        $differences = [
            'bilan'   => $totals['Actif']['solde'] - $totals['Passif']['solde'],
            'resultat' => $totals['Produit']['solde'] - $totals['Charge']['solde'],
        ];

        return view('livewire.comptabilite.financial-result', [
            'accounts'    => $accounts,
            'totals'      => $totals,
            'differences' => $differences,
        ]);
    }

    public function export()
    {
        $accounts = Compte::with(['journals' => function($q) {
                if ($this->filter_currency) {
                    $q->where('devise', $this->filter_currency);
                }
            }])
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        $totals = [
            'Actif'    => ['debit' => 0, 'credit' => 0, 'solde' => 0],
            'Passif'   => ['debit' => 0, 'credit' => 0, 'solde' => 0],
            'Produit' => ['debit' => 0, 'credit' => 0, 'solde' => 0],
            'Charge'  => ['debit' => 0, 'credit' => 0, 'solde' => 0],
        ];

        foreach ($accounts as $account) {
            $debit  = $account->journals->sum('montant_debit');
            $credit = $account->journals->sum('montant_credit');
            $solde  = $debit - $credit;

            if (isset($totals[$account->type])) {
                $totals[$account->type]['debit']  += $debit;
                $totals[$account->type]['credit'] += $credit;
                $totals[$account->type]['solde']  += $solde;
            }
        }

        $differences = [
            'bilan'   => $totals['Actif']['solde'] - $totals['Passif']['solde'],
            'resultat' => $totals['Produit']['solde'] - $totals['Charge']['solde'],
        ];

        $pdf = Pdf::loadView('pdf.financial-result', [
            'accounts'    => $accounts,
            'totals'      => $totals,
            'differences' => $differences,
            'currency'    => $this->filter_currency ?? "Toutes devises",
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'resultat-financier-'.now()->format('Ymd-His').'.pdf'
        );
    }
}
