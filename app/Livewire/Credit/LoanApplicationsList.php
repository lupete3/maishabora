<?php

namespace App\Livewire\Credit;

use App\Models\LoanApplication;
use Livewire\Component;
use Livewire\WithPagination;

class LoanApplicationsList extends Component
{
  use WithPagination;


    protected $paginationTheme = 'bootstrap';

    public $search;
    public $statusFilter = null;

    protected $listeners = ['loanSaved' => '$refresh'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = LoanApplication::query();
        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('first_name', 'like', "%{$this->search}%")->orWhere('last_name', 'like', "%{$this->search}%"); });
        }
        if ($this->statusFilter)
            $query->where('statut', $this->statusFilter);

        $loans = $query->orderBy('id', 'desc')->paginate(10);
        return view('livewire.credit.loan-applications-list', compact('loans'));
    }
}
