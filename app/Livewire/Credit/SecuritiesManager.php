<?php

namespace App\Livewire\Credit;

use Livewire\Component;
use App\Models\Security;

class SecuritiesManager extends Component
{
    public $loan_application_id;
    public $securities = [];

    public $type;
    public $description;
    public $valeur_estimee;
    public $nature_bien;
    public $proprietaire;

    public function mount($loan_application_id)
    {
        $this->loan_application_id = $loan_application_id;
        $this->loadList();
    }

    public function loadList()
    {
        $this->securities = Security::where('loan_application_id', $this->loan_application_id)->get()->toArray();
    }

    public function add()
    {
        $this->validate([
            'type' => 'required',
            'valeur_estimee' => 'required|numeric',
        ]);

        Security::create([
            'loan_application_id' => $this->loan_application_id,
            'type' => $this->type,
            'description' => $this->description,
            'valeur_estimee' => $this->valeur_estimee,
            'nature_bien' => $this->nature_bien,
            'proprietaire' => $this->proprietaire,
        ]);

        $this->reset(['type', 'description', 'valeur_estimee', 'nature_bien', 'proprietaire']);
        $this->loadList();
        $this->dispatch('securitiesUpdated', $this->loan_application_id);
    }

    public function delete($id)
    {
        Security::find($id)?->delete();
        $this->loadList();
        $this->dispatch('securitiesUpdated', $this->loan_application_id);
    }

    public function render()
    {
        return view('livewire.credit.securities-manager');
    }
}
