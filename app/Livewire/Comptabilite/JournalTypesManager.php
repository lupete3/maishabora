<?php

namespace App\Livewire\Comptabilite;

use App\Models\JournalType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class JournalTypesManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $libelle;
    public $journalTypeId;
    public $modalMode = 'create'; // create | edit

    protected $rules = [
        'libelle' => 'required|unique:journal_types,libelle',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $types = JournalType::where('libelle', 'like', "%{$this->search}%")
            ->orderBy('libelle')
            ->paginate(10);

        return view('livewire.comptabilite.journal-types-manager', compact('types'));
    }

    public function openCreateModal()
    {
        $this->resetInput();
        $this->modalMode = 'create';
        $this->dispatch('openModal', name: 'journalTypeModal');
    }

    public function openEditModal($id)
    {
        $type = JournalType::findOrFail($id);
        $this->journalTypeId = $type->id;
        $this->libelle = $type->libelle;

        $this->modalMode = 'edit';
        $this->dispatch('openModal', name: 'journalTypeModal');
    }

    public function save()
    {
        Gate::authorize($this->modalMode === 'create' ? 'ajouter-type-journal' : 'modifier-type-journal', User::class);
        if ($this->modalMode === 'create') {
            $this->validate();
            JournalType::create(['libelle' => $this->libelle]);
            notyf()->success('Type de journal créé avec succès.');
        } else {
            $this->validate([
                'libelle' => 'required|unique:journal_types,libelle,' . $this->journalTypeId,
            ]);
            $type = JournalType::findOrFail($this->journalTypeId);
            $type->update(['libelle' => $this->libelle]);
            notyf()->success('Type de journal mis à jour avec succès.');
        }

        $this->dispatch('closeModal', name: 'journalTypeModal');
        $this->resetInput();
    }

    public function delete($id)
    {
        Gate::authorize('supprimer-type-journal', User::class);
        JournalType::findOrFail($id)->delete();
        notyf()->success('Type de journal supprimé avec succès.');
    }

    private function resetInput()
    {
        $this->journalTypeId = null;
        $this->libelle = '';
    }
}
