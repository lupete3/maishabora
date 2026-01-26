<?php

namespace App\Livewire\Comptabilite;

use App\Models\Compte;
use App\Models\Journal;
use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class IncomeStatement extends Component
{
    public $devise = 'USD';
    public $date_debut;
    public $date_fin;
    public $period_type = 'mois';

    // Données calculées
    public $produits = [];
    public $charges = [];
    public $totalProduits = 0;
    public $totalCharges = 0;
    public $resultatNet = 0;

    public function mount()
    {
        $this->updatePeriodDates('mois');
    }

    public function updatedPeriodType($value)
    {
        $this->updatePeriodDates($value);
    }

    private function updatePeriodDates($periodType)
    {
        $now = now();
        switch ($periodType) {
            case 'mois':
                $this->date_debut = $now->startOfMonth()->format('Y-m-d');
                $this->date_fin = $now->endOfMonth()->format('Y-m-d');
                break;
            case 'trimestre':
                $this->date_debut = $now->startOfQuarter()->format('Y-m-d');
                $this->date_fin = $now->endOfQuarter()->format('Y-m-d');
                break;
            case 'annee':
                $this->date_debut = $now->startOfYear()->format('Y-m-d');
                $this->date_fin = $now->endOfYear()->format('Y-m-d');
                break;
            case 'intervalle':
                if (!$this->date_debut)
                    $this->date_debut = $now->startOfMonth()->format('Y-m-d');
                if (!$this->date_fin)
                    $this->date_fin = $now->endOfMonth()->format('Y-m-d');
                break;
        }
    }

    public function render()
    {
        $this->calculateIncomeStatement();

        return view('livewire.comptabilite.income-statement', [
            'currencies' => ['USD', 'CDF'],
        ]);
    }

    /**
     * Calculer le compte de résultat
     */
    private function calculateIncomeStatement()
    {
        // Récupérer tous les comptes de produits (Classe 7)
        $comptesProduits = Compte::where('type', 'Produit')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        // Récupérer tous les comptes de charges (Classe 6)
        $comptesCharges = Compte::where('type', 'Charge')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        // Calculer les produits
        $this->produits = $comptesProduits->map(function ($compte) {
            $solde = $this->calculateAccountBalance($compte);
            return [
                'code' => $compte->code,
                'intitule' => $compte->intitule,
                'montant' => $solde,
                'level' => $compte->level,
            ];
        })->filter(function ($item) {
            return $item['montant'] != 0; // Afficher seulement les comptes avec mouvements
        })->values();

        // Calculer les charges
        $this->charges = $comptesCharges->map(function ($compte) {
            $solde = $this->calculateAccountBalance($compte);
            return [
                'code' => $compte->code,
                'intitule' => $compte->intitule,
                'montant' => abs($solde), // Charges en positif pour affichage
                'level' => $compte->level,
            ];
        })->filter(function ($item) {
            return $item['montant'] != 0;
        })->values();

        // Totaux
        $this->totalProduits = $this->produits->sum('montant');
        $this->totalCharges = $this->charges->sum('montant');
        $this->resultatNet = $this->totalProduits - $this->totalCharges;
    }

    /**
     * Calculer le solde d'un compte pour la période
     */
    private function calculateAccountBalance(Compte $compte): float
    {
        $query = Journal::where('compte_id', $compte->id)
            ->where('devise', $this->devise);

        if ($this->date_debut && $this->date_fin) {
            $query->whereBetween('date_operation', [$this->date_debut, $this->date_fin]);
        }

        $totalDebit = $query->sum('montant_debit');
        $totalCredit = $query->sum('montant_credit');

        // Pour les produits: Crédit - Débit
        // Pour les charges: Débit - Crédit
        if ($compte->type === 'Produit') {
            return $totalCredit - $totalDebit;
        } else {
            return $totalDebit - $totalCredit;
        }
    }

    /**
     * Exporter en PDF
     */
    public function exportPDF()
    {
        $this->calculateIncomeStatement();

        $pdf = Pdf::loadView('pdf.income-statement', [
            'produits' => $this->produits,
            'charges' => $this->charges,
            'totalProduits' => $this->totalProduits,
            'totalCharges' => $this->totalCharges,
            'resultatNet' => $this->resultatNet,
            'devise' => $this->devise,
            'dateDebut' => $this->date_debut,
            'dateFin' => $this->date_fin,
            'user' => Auth::user(),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(
            fn() => print ($pdf->output()),
            'compte-resultat-' . now()->format('Ymd') . '.pdf'
        );
    }
}
