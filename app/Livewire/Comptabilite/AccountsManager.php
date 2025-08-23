<?php

namespace App\Livewire\Comptabilite;

use App\Models\Compte;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class AccountsManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $code, $intitule, $type;
    public $accountId;
    public $modalMode = 'create'; // create | edit

    protected $rules = [
        'code' => 'required|unique:comptes,code',
        'intitule' => 'required|string|max:255',
        'type' => 'required|in:Actif,Passif',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $accounts = Compte::where('code', 'like', "%{$this->search}%")
            ->orWhere('intitule', 'like', "%{$this->search}%")
            ->orderBy('code')
            ->paginate(10);

        return view('livewire.comptabilite.accounts-manager', compact('accounts'));
    }

    public function openCreateModal()
    {
        $this->resetInput();
        $this->modalMode = 'create';
        $this->dispatch('openModal', name: 'accountModal');
    }

    public function openEditModal($id)
    {
        $account = Compte::findOrFail($id);
        $this->accountId = $account->id;
        $this->code = $account->code;
        $this->intitule = $account->intitule;
        $this->type = $account->type;

        $this->modalMode = 'edit';
        $this->dispatch('openModal', name: 'accountModal');
    }

    public function save()
    {
        Gate::authorize($this->modalMode === 'create' ? 'ajouter-compte-comptable' : 'modifier-compte-comptable', User::class);
        if ($this->modalMode === 'create') {
            $this->validate();
            Compte::create([
                'code' => $this->code,
                'intitule' => $this->intitule,
                'type' => $this->type,
            ]);
            notyf()->success('Compte créé avec succès.');
        } else {
            $this->validate([
                'code' => 'required|unique:comptes,code,' . $this->accountId,
                'intitule' => 'required|string|max:255',
                'type' => 'required|in:Actif,Passif',
            ]);
            $account = Compte::findOrFail($this->accountId);
            $account->update([
                'code' => $this->code,
                'intitule' => $this->intitule,
                'type' => $this->type,
            ]);
            notyf()->success('Compte mis à jour avec succès.');
        }

        $this->dispatch('closeModal', name: 'accountModal');
        $this->resetInput();
    }

    public function delete($id)
    {
        Gate::authorize('supprimer-compte-comptable', User::class);
        Compte::findOrFail($id)->delete();
        notyf()->success('Compte supprimé avec succès.');
    }

    private function resetInput()
    {
        $this->accountId = null;
        $this->code = '';
        $this->intitule = '';
        $this->type = '';
    }
}

