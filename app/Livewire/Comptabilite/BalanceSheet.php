<?php

namespace App\Livewire\Comptabilite;

use App\Models\Compte;
use App\Models\Journal;
use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class BalanceSheet extends Component
{
    public $devise = 'USD';
    public $date_reference;

    // Données calculées
    public $actifs = [];
    public $passifs = [];
    public $totalActifs = 0;
    public $totalPassifs = 0;
    public $isBalanced = false;

    public function mount()
    {
        $this->date_reference = now()->format('Y-m-d');
    }

    public function render()
    {
        $this->calculateBalanceSheet();

        return view('livewire.comptabilite.balance-sheet', [
            'currencies' => ['USD', 'CDF'],
        ]);
    }

    /**
     * Calculer le bilan
     */
    private function calculateBalanceSheet()
    {
        // ACTIF: Comptes de type Actif (Classes 1, 2, 3, 4, 5)
        $comptesActifs = Compte::where('type', 'Actif')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $this->actifs = $comptesActifs->map(function ($compte) {
            $solde = $this->calculateAccountBalance($compte);
            return [
                'code' => $compte->code,
                'intitule' => $compte->intitule,
                'montant' => $solde,
                'level' => $compte->level,
                'classe' => substr($compte->code, 0, 1),
            ];
        })->filter(fn($item) => $item['montant'] != 0)
            ->groupBy('classe')
            ->map(fn($groupe) => $groupe->values())
            ->toArray();

        // PASSIF: Comptes de type Passif
        $comptesPassifs = Compte::where('type', 'Passif')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $this->passifs = $comptesPassifs->map(function ($compte) {
            $solde = $this->calculateAccountBalance($compte);
            return [
                'code' => $compte->code,
                'intitule' => $compte->intitule,
                'montant' => abs($solde), // Passif en positif pour affichage
                'level' => $compte->level,
                'classe' => substr($compte->code, 0, 1),
            ];
        })->filter(fn($item) => $item['montant'] != 0)
            ->groupBy('classe')
            ->map(fn($groupe) => $groupe->values())
            ->toArray();

        // Calculer résultat net et l'ajouter au passif
        $resultatNet = $this->calculateNetIncome();
        if ($resultatNet != 0) {
            if (!isset($this->passifs['1'])) {
                $this->passifs['1'] = [];
            }
            $this->passifs['1'][] = [
                'code' => '131/139',
                'intitule' => $resultatNet >= 0 ? 'Résultat net (Bénéfice)' : 'Résultat net (Perte)',
                'montant' => abs($resultatNet),
                'level' => 2,
                'classe' => '1',
            ];
        }

        // Totaux
        $this->totalActifs = collect($this->actifs)->flatten(1)->sum('montant');
        $this->totalPassifs = collect($this->passifs)->flatten(1)->sum('montant');
        $this->isBalanced = abs($this->totalActifs - $this->totalPassifs) < 0.01; // Tolérance 1 centime
    }

    /**
     * Calculer le solde d'un compte à la date de référence
     */
    private function calculateAccountBalance(Compte $compte): float
    {
        $query = Journal::where('compte_id', $compte->id)
            ->where('devise', $this->devise)
            ->where('date_operation', '<=', $this->date_reference);

        $totalDebit = $query->sum('montant_debit');
        $totalCredit = $query->sum('montant_credit');

        // Actifs: Débit - Crédit
        // Passifs: Crédit - Débit
        if ($compte->type === 'Actif') {
            return $totalDebit - $totalCredit;
        } else {
            return $totalCredit - $totalDebit;
        }
    }

    /**
     * Calculer le résultat net (pour l'intégrer au passif)
     */
    private function calculateNetIncome(): float
    {
        // Produits (Classe 7)
        $produits = Journal::whereHas('account', fn($q) => $q->where('type', 'Produit'))
            ->where('devise', $this->devise)
            ->where('date_operation', '<=', $this->date_reference)
            ->selectRaw('SUM(montant_credit) - SUM(montant_debit) as total')
            ->value('total') ?? 0;

        // Charges (Classe 6)
        $charges = Journal::whereHas('account', fn($q) => $q->where('type', 'Charge'))
            ->where('devise', $this->devise)
            ->where('date_operation', '<=', $this->date_reference)
            ->selectRaw('SUM(montant_debit) - SUM(montant_credit) as total')
            ->value('total') ?? 0;

        return $produits - $charges;
    }

    /**
     * Exporter en PDF
     */
    public function exportPDF()
    {
        $this->calculateBalanceSheet();

        $pdf = Pdf::loadView('pdf.balance-sheet', [
            'actifs' => $this->actifs,
            'passifs' => $this->passifs,
            'totalActifs' => $this->totalActifs,
            'totalPassifs' => $this->totalPassifs,
            'isBalanced' => $this->isBalanced,
            'devise' => $this->devise,
            'dateReference' => $this->date_reference,
            'user' => Auth::user(),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn() => print ($pdf->output()),
            'bilan-' . now()->format('Ymd') . '.pdf'
        );
    }
}
