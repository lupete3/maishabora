<?php

namespace App\Livewire\Comptabilite;

use App\Models\Compte;
use App\Models\Journal;
use Livewire\Component;
use Livewire\WithPagination;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class GeneralLedger extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filtres
    public $compte_id = null;
    public $devise = 'USD';
    public $date_debut;
    public $date_fin;
    public $period_type = 'mois';

    // Données calculées
    public $soldeInitial = 0;
    public $soldeFinal = 0;

    public function mount()
    {
        $this->updatePeriodDates('mois');
    }

    public function updatedPeriodType($value)
    {
        $this->updatePeriodDates($value);
        $this->resetPage();
    }

    public function updatingCompteId()
    {
        $this->resetPage();
    }

    public function updatingDevise()
    {
        $this->resetPage();
    }

    private function updatePeriodDates($periodType)
    {
        $now = now();
        switch ($periodType) {
            case 'jour':
                $this->date_debut = $now->format('Y-m-d');
                $this->date_fin = $now->format('Y-m-d');
                break;
            case 'semaine':
                $this->date_debut = $now->startOfWeek()->format('Y-m-d');
                $this->date_fin = $now->endOfWeek()->format('Y-m-d');
                break;
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
            case 'tout':
                $this->date_debut = null;
                $this->date_fin = null;
                break;
        }
    }

    public function render()
    {
        // Tous les comptes actifs pour le sélecteur
        $comptes = Compte::active()
            ->detailed() // Seulement les comptes de niveau 3 (utilisables)
            ->orderBy('code')
            ->get();

        $journals = collect();
        $compte = null;
        $soldeCourant = 0;

        if ($this->compte_id) {
            $compte = Compte::find($this->compte_id);

            if ($compte) {
                // Solde initial (avant la période)
                if ($this->date_debut) {
                    $this->soldeInitial = $this->calculateBalance($compte, null, $this->date_debut);
                } else {
                    $this->soldeInitial = 0;
                }

                // Écritures de la période avec soldes progressifs
                $query = Journal::where('compte_id', $this->compte_id)
                    ->where('devise', $this->devise)
                    ->orderBy('date_operation', 'asc')
                    ->orderBy('id', 'asc');

                if ($this->date_debut && $this->date_fin) {
                    $query->whereBetween('date_operation', [$this->date_debut, $this->date_fin]);
                }

                $journals = $query->paginate(20);

                // Calculer les soldes progressifs
                $soldeCourant = $this->soldeInitial;
                foreach ($journals as $journal) {
                    if (in_array($compte->type, ['Actif', 'Charge'])) {
                        // Comptes à solde débiteur
                        $soldeCourant += $journal->montant_debit - $journal->montant_credit;
                    } else {
                        // Comptes à solde créditeur
                        $soldeCourant += $journal->montant_credit - $journal->montant_debit;
                    }
                    $journal->solde_progressif = $soldeCourant;
                }

                $this->soldeFinal = $soldeCourant;
            }
        }

        $currencies = ['USD', 'CDF'];

        return view('livewire.comptabilite.general-ledger', [
            'comptes' => $comptes,
            'journals' => $journals,
            'compte' => $compte,
            'currencies' => $currencies,
        ]);
    }

    /**
     * Calculer le solde d'un compte pour une période
     */
    private function calculateBalance(Compte $compte, ?string $dateDebut, ?string $dateFin): float
    {
        $query = Journal::where('compte_id', $compte->id)
            ->where('devise', $this->devise);

        if ($dateDebut) {
            $query->where('date_operation', '>=', $dateDebut);
        }
        if ($dateFin) {
            $query->where('date_operation', '<', $dateFin); // Strictement avant
        }

        $totalDebit = $query->sum('montant_debit');
        $totalCredit = $query->sum('montant_credit');

        if (in_array($compte->type, ['Actif', 'Charge'])) {
            return $totalDebit - $totalCredit;
        } else {
            return $totalCredit - $totalDebit;
        }
    }

    /**
     * Exporter le grand livre en PDF
     */
    public function exportPDF()
    {
        if (!$this->compte_id) {
            notyf()->error('Veuillez sélectionner un compte');
            return;
        }

        $compte = Compte::find($this->compte_id);

        // Récupérer toutes les écritures (sans pagination)
        $query = Journal::where('compte_id', $this->compte_id)
            ->where('devise', $this->devise)
            ->orderBy('date_operation', 'asc')
            ->orderBy('id', 'asc');

        if ($this->date_debut && $this->date_fin) {
            $query->whereBetween('date_operation', [$this->date_debut, $this->date_fin]);
        }

        $journals = $query->get();

        // Calculer soldes progressifs
        $soldeCourant = $this->soldeInitial;
        foreach ($journals as $journal) {
            if (in_array($compte->type, ['Actif', 'Charge'])) {
                $soldeCourant += $journal->montant_debit - $journal->montant_credit;
            } else {
                $soldeCourant += $journal->montant_credit - $journal->montant_debit;
            }
            $journal->solde_progressif = $soldeCourant;
        }

        $pdf = Pdf::loadView('pdf.general-ledger', [
            'compte' => $compte,
            'journals' => $journals,
            'soldeInitial' => $this->soldeInitial,
            'soldeFinal' => $soldeCourant,
            'devise' => $this->devise,
            'dateDebut' => $this->date_debut,
            'dateFin' => $this->date_fin,
            'user' => Auth::user(),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn() => print ($pdf->output()),
            'grand-livre-' . $compte->code . '-' . now()->format('Ymd') . '.pdf'
        );
    }
}
