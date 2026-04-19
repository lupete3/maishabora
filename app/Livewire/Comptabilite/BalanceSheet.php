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
        // On récupère tous les comptes actifs qui ont des écritures dans la devise sélectionnée
        $comptes = Compte::where('is_active', true)
            ->where(function ($q) {
                $q->where('code', 'like', '1%')
                    ->orWhere('code', 'like', '2%')
                    ->orWhere('code', 'like', '3%')
                    ->orWhere('code', 'like', '4%')
                    ->orWhere('code', 'like', '5%');
            })
            ->orderBy('code')
            ->get();

        $this->actifs = [];
        $this->passifs = [];

        foreach ($comptes as $compte) {
            $solde = $this->calculateAccountBalance($compte);

            if (abs($solde) < 0.01)
                continue;

            $classe = substr($compte->code, 0, 1);

            // LOGIQUE SYSCOHADA :
            // Un compte est à l'ACTIF s'il a un solde DÉBITEUR (positif dans notre calcul pour les actifs)
            // Un compte est au PASSIF s'il a un solde CRÉDITEUR (positif dans notre calcul pour les passifs, donc négatif ici)

            // Note: calculateAccountBalance renvoie (Débit - Crédit) pour les types 'Actif'
            // et (Crédit - Débit) pour les types 'Passif'.
            // Pour harmoniser, on va regarder le solde brut si possible, 
            // ou adapter selon le type actuel tout en respectant le signe.

            if ($compte->type === 'Actif') {
                if ($solde > 0) {
                    // C'est bien un Actif (solde débiteur)
                    $this->addToSection('actifs', $compte, $solde);
                } elseif ($solde < 0) {
                    // C'est un Actif avec solde créditeur (ex: banque à découvert) -> va au PASSIF
                    $this->addToSection('passifs', $compte, abs($solde));
                }
            } else {
                // Type Passif (Classe 1, 4...)
                if ($solde > 0) {
                    // C'est bien un Passif (solde créditeur)
                    $this->addToSection('passifs', $compte, $solde);
                } elseif ($solde < 0) {
                    // C'veut dire solde débiteur pour un compte de passif -> va à l'ACTIF
                    $this->addToSection('actifs', $compte, abs($solde));
                }
            }
        }

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
        $this->isBalanced = abs($this->totalActifs - $this->totalPassifs) < 0.05; // Marge de 0.05 pour les micro-arrondis
    }

    /**
     * Helper pour ajouter un compte à une section (actifs ou passifs)
     */
    private function addToSection(string $section, Compte $compte, float $montant)
    {
        $classe = substr($compte->code, 0, 1);
        if (!isset($this->{$section}[$classe])) {
            $this->{$section}[$classe] = [];
        }

        $this->{$section}[$classe][] = [
            'code' => $compte->code,
            'intitule' => $compte->intitule,
            'montant' => $montant,
            'level' => $compte->level,
            'classe' => $classe,
        ];
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
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn() => print ($pdf->output()),
            'bilan-' . now()->format('Ymd') . '.pdf'
        );
    }
}
