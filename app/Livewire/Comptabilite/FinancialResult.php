<?php

namespace App\Livewire\Comptabilite;


use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Compte;
use App\Models\Journal;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class FinancialResult extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $filter_currency = null; // USD, CDF...

    public function render()
    {
        // Récupérer tous les comptes classés par type
        $accounts = Compte::with(['journals' => function($q){
                if ($this->filter_currency) {
                    $q->where('devise', $this->filter_currency);
                }
            }])
            ->when($this->search, function($q){
                $q->where('code', 'like', "%{$this->search}%")
                  ->orWhere('intitule', 'like', "%{$this->search}%");
            })
            ->orderBy('type')
            ->orderBy('code')
            ->paginate(15);

        // Totaux Actif et Passif
        $totals = [
            'Actif' => ['debit' => 0, 'credit' => 0, 'solde' => 0],
            'Passif' => ['debit' => 0, 'credit' => 0, 'solde' => 0],
        ];

        foreach ($accounts as $account) {
            $debit = $account->journals->sum('montant_debit');
            $credit = $account->journals->sum('montant_credit');
            $solde = $debit - $credit;

            $totals[$account->type]['debit'] += $debit;
            $totals[$account->type]['credit'] += $credit;
            $totals[$account->type]['solde'] += $solde;
        }

        return view('livewire.comptabilite.financial-result', [
            'accounts' => $accounts,
            'totals' => $totals,
        ]);
    }

    public function export()
    {
        $accounts = Compte::with(['journals' => function($q){
                if ($this->filter_currency) {
                    $q->where('devise', $this->filter_currency);
                }
            }])
            ->orderBy('type')
            ->orderBy('code')
            ->get();

        $totals = [
            'Actif' => ['debit' => 0, 'credit' => 0, 'solde' => 0],
            'Passif' => ['debit' => 0, 'credit' => 0, 'solde' => 0],
        ];

        foreach ($accounts as $account) {
            $debit = $account->journals->sum('montant_debit');
            $credit = $account->journals->sum('montant_credit');
            $solde = $debit - $credit;

            $totals[$account->type]['debit'] += $debit;
            $totals[$account->type]['credit'] += $credit;
            $totals[$account->type]['solde'] += $solde;
        }

        $pdf = Pdf::loadView('pdf.financial-result', [
            'accounts' => $accounts,
            'totals' => $totals,
            'currency' => $this->filter_currency ?? "Toutes devises",
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'resultat-financier-'.now()->format('Ymd-His').'.pdf'
        );
    }
}

