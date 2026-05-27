<?php

namespace App\Livewire\Credit;

use App\Models\LoanApplication;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class LoanApplicationsList extends Component
{
    use WithPagination;


    protected $paginationTheme = 'bootstrap';

    public $search;
    public $statusFilter = null;

    protected $listeners = ['loanSaved' => '$refresh'];

    public function mount()
    {
        Gate::authorize('afficher-demandes-credit', User::class);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function delete($id)
    {
        $loan = LoanApplication::find($id);
        if ($loan) {
            $loan->delete();
            session()->flash('message', 'Demande de crédit supprimée avec succès.');
        }
    }

    public function render()
    {
        $query = LoanApplication::query();
        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('postnom', 'like', "%{$this->search}%")
                    ->orWhere('prenom', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%");
            });
        }
        if ($this->statusFilter)
            $query->where('statut', $this->statusFilter);

        $stats = [
            'total' => LoanApplication::count(),
            'pending' => LoanApplication::where('statut', 'en_analyse')->count(),
            'total_usd' => LoanApplication::where('currency', 'USD')->sum('montant_demande'),
            'total_cdf' => LoanApplication::where('currency', 'CDF')->sum('montant_demande'),
        ];

        $loans = $query->orderBy('id', 'desc')->paginate(10);
        return view('livewire.credit.loan-applications-list', [
            'loans' => $loans,
            'stats' => $stats
        ]);
    }
}
