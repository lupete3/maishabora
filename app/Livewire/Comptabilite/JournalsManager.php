<?php

namespace App\Livewire\Comptabilite;

use App\Models\Compte;
use App\Models\Journal;
use App\Models\JournalType;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class JournalsManager extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $date_operation, $libelle, $reference, $devise = 'USD', $type_journal_id;

    // 🔹 Filtres
    public $filter_journal_type = null; // filtre auxiliaire
    public $filter_account = null;      // filtre grand livre
    public $filter_currency = null;     // devise


    // Session d’écritures temporaires
    public $lines = []; // [ ['compte_id'=>, 'type_operation'=>, 'montant'=>], ... ]

    public function mount()
    {
        $this->date_operation = now()->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function updatingFilterJournalType()
    {
        $this->resetPage();
    }
    public function updatingFilterAccount()
    {
        $this->resetPage();
    }
    public function updatingFilterCurrency()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Journal::with(['account', 'journalType', 'user'])
            ->when($this->search, fn($q) => $q->where(function ($s) {
                $s->where('libelle', 'like', "%{$this->search}%")
                    ->orWhere('reference', 'like', "%{$this->search}%");
            }))
            ->when($this->filter_journal_type, fn($q) => $q->where('type_journal_id', $this->filter_journal_type))
            ->when($this->filter_account, fn($q) => $q->where('compte_id', $this->filter_account))
            ->when($this->filter_currency, fn($q) => $q->where('devise', $this->filter_currency))
            ->orderBy('date_operation', 'desc');


        $journals = (clone $query)->paginate(10);
        $accounts = Compte::orderBy('code')->get();
        $journalTypes = JournalType::orderBy('libelle')->get();
        $currencies = Journal::select('devise')->distinct()->pluck('devise'); // pour alimenter le select
        return view('livewire.comptabilite.journals-manager', compact('journals', 'accounts', 'journalTypes', 'currencies'));
    }

    /** Ajouter une ligne temporaire */
    public function addLine()
    {
        $this->lines[] = ['compte_id' => '', 'type_operation' => 'debit', 'montant' => 0];
    }

    /** Supprimer une ligne */
    public function removeLine($index)
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    /** Vérifier équilibre */
    public function getIsBalancedProperty()
    {
        $debit = collect($this->lines)->where('type_operation', 'debit')->sum('montant');
        $credit = collect($this->lines)->where('type_operation', 'credit')->sum('montant');
        return $debit == $credit && $debit > 0;
    }

    /** Validation finale */
    public function save()
    {
        Gate::authorize('ajouter-ecriture-journal', User::class);
        if (!$this->isBalanced) {
            notyf()->error("L'écriture n'est pas équilibrée !");
            return;
        }

        foreach ($this->lines as $line) {
            Journal::create([
                'date_operation' => $this->date_operation,
                'libelle' => $this->libelle,
                'reference' => $this->reference,
                'devise' => $this->devise,
                'montant_debit' => $line['type_operation'] === 'debit' ? $line['montant'] : 0,
                'montant_credit' => $line['type_operation'] === 'credit' ? $line['montant'] : 0,
                'type_operation' => $line['type_operation'],
                'compte_id' => $line['compte_id'],
                'type_journal_id' => $this->type_journal_id,
                'user_id' => Auth::user()->id,
            ]);
        }

        notyf()->success("Écriture comptable enregistrée !");
        $this->resetForm();
        $this->dispatch('closeModal', name: 'journalModal');
    }

    private function resetForm()
    {
        $this->date_operation = now()->format('Y-m-d');
        $this->libelle = '';
        $this->reference = '';
        $this->devise = 'USD';
        $this->type_journal_id = null;
        $this->lines = [];
    }

    public function export()
    {
        $query = Journal::with(['account', 'journalType', 'user'])
            ->when($this->search, fn($q) => $q->where(function ($s) {
                $s->where('libelle', 'like', "%{$this->search}%")
                    ->orWhere('reference', 'like', "%{$this->search}%");
            }))
            ->when($this->filter_journal_type, fn($q) => $q->where('type_journal_id', $this->filter_journal_type))
            ->when($this->filter_account, fn($q) => $q->where('compte_id', $this->filter_account))
            ->when($this->filter_currency, fn($q) => $q->where('devise', $this->filter_currency))
            ->orderBy('date_operation', 'desc');

        $journals = $query->get();

        // ✅ Totaux par devise (débit/credit/net)
        $totalByCurrency = $journals->groupBy('devise')->map(function ($rows) {
            $debit  = $rows->sum(fn($j) => (float) $j->montant_debit);
            $credit = $rows->sum(fn($j) => (float) $j->montant_credit);
            return [
                'debit'  => $debit,
                'credit' => $credit,
                'net'    => $debit - $credit,
            ];
        });

        $user = Auth::user();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.journals-report', [
            'journals'         => $journals,
            'user'             => $user,
            'totalByCurrency'  => $totalByCurrency,
            'transactionCount' => $journals->count(),
            'filter'           => $this->filter_journal_type ? "Journal"
                : ($this->filter_account ? "Grand Livre"
                    : ($this->filter_currency ? "Devise" : "Tous")),
            'filter_currency'  => $this->filter_currency,
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'rapport-journaux-' . now()->format('Ymd-His') . '.pdf'
        );
    }
}