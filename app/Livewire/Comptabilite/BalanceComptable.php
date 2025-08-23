<?php

namespace App\Livewire\Comptabilite;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Compte;
use App\Models\Journal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class BalanceComptable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $filter_devise = null; // 'USD', 'CDF', ...

    public function updatingSearch()        { $this->resetPage(); }
    public function updatingFilterDevise()  { $this->resetPage(); }

    public function render()
    {
        // 1) Pagination des comptes (filtrables par code/intitulé)
        $comptes = Compte::query()
            ->when($this->search, function ($q) {
                $q->where(function ($qq) {
                    $qq->where('code', 'like', "%{$this->search}%")
                       ->orWhere('intitule', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('code')
            ->paginate(15);

        // 2) Agrégats Débit/Crédit par compte, filtrés éventuellement par devise
        $aggregats = Journal::selectRaw('compte_id, SUM(montant_debit) as total_debit, SUM(montant_credit) as total_credit')
            ->when($this->filter_devise, fn ($q) => $q->where('devise', $this->filter_devise))
            ->groupBy('compte_id')
            ->get()
            ->keyBy('compte_id');

        // 3) Construire la balance pour les comptes de la page courante
        $comptesData = $comptes->getCollection()->map(function ($compte) use ($aggregats) {
            $agg = $aggregats->get($compte->id);
            $totalDebit  = $agg?->total_debit  ?? 0.0;
            $totalCredit = $agg?->total_credit ?? 0.0;

            return [
                'code'            => $compte->code,
                'intitule'        => $compte->intitule,
                'total_debit'     => (float) $totalDebit,
                'total_credit'    => (float) $totalCredit,
                'solde_debiteur'  => $totalDebit  > $totalCredit ? $totalDebit - $totalCredit : 0.0,
                'solde_crediteur' => $totalCredit > $totalDebit  ? $totalCredit - $totalDebit : 0.0,
            ];
        });

        // 4) Pour alimenter le sélecteur des devises
        $currencies = Journal::select('devise')->distinct()->pluck('devise');

        return view('livewire.comptabilite.balance-comptable', [
            'comptes'     => $comptes,
            'comptesData' => $comptesData,
            'currencies'  => $currencies,
        ]);
    }

    public function exportPdf()
    {
        // Reprendre la même logique que dans render()
        $query = Compte::with(['journals']);

        if ($this->search) {
            $query->where(function($q){
                $q->where('code', 'like', "%{$this->search}%")
                ->orWhere('intitule', 'like', "%{$this->search}%");
            });
        }

        $comptes = $query->get()->map(function($compte) {
            $totalDebit = $compte->journals()
                ->when($this->filter_devise, function($q){
                    $q->where('devise', $this->filter_devise);
                })
                ->sum('montant_debit');

            $totalCredit = $compte->journals()
                ->when($this->filter_devise, function($q){
                    $q->where('devise', $this->filter_devise);
                })
                ->sum('montant_credit');

            return [
                'code' => $compte->code,
                'intitule' => $compte->intitule,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'solde_debiteur' => $totalDebit > $totalCredit ? $totalDebit - $totalCredit : 0,
                'solde_crediteur' => $totalCredit > $totalDebit ? $totalCredit - $totalDebit : 0,
            ];
        });

        $pdf = Pdf::loadView('pdf.balance-report', [
            'comptes' => $comptes,
            'user' => Auth::user(),
            'currency' => $this->filter_devise,
            'date' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'balance-'.now()->format('Ymd-His').'.pdf'
        );
    }
}