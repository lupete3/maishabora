<?php

namespace App\Livewire\Credit;

use Livewire\Component;
use App\Models\LoanDecision;

class LoanDecisionForm extends Component
{
    public $loan_application_id;
    public $note_caractere = 5;
    public $note_capacite = 5;
    public $note_capital = 5;
    public $note_caution = 5;
    public $note_caracteristiques_financieres = 5;
    public $commentaire_global;
    public $decision_finale = 'a_revoir';

    public function mount($loan_application_id)
    {
        $this->loan_application_id = $loan_application_id;
        $decision = LoanDecision::where('loan_application_id', $loan_application_id)->first();
        if ($decision) {
            $this->fill($decision->toArray());
        }
    }

    public function submit()
    {
        $data = [
            'loan_application_id' => $this->loan_application_id,
            'note_caractere' => $this->note_caractere,
            'note_capacite' => $this->note_capacite,
            'note_capital' => $this->note_capital,
            'note_caution' => $this->note_caution,
            'note_caracteristiques_financieres' => $this->note_caracteristiques_financieres,
            'commentaire_global' => $this->commentaire_global,
            'decision_finale' => $this->decision_finale,
            'user_id' => auth()->id(),
        ];

        LoanDecision::updateOrCreate(['loan_application_id' => $this->loan_application_id], $data);
        session()->flash('message', 'Décision enregistrée');
        $this->dispatch('decisionSaved', $this->loan_application_id);
    }

    public function render()
    {
        return view('livewire.credit.loan-decision-form');
    }
}