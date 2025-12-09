<?php

namespace App\Livewire\Credit;

use Livewire\Component;
use App\Models\LoanApplication;
use App\Models\Business;
use Illuminate\Support\Facades\Auth;

class LoanApplicationCreate extends Component
{
    public $loanApplicationId;
    public $user_id; // borrower
    public $business_id;
    public $montant_demande;
    public $duree_mois = 12;
    public $produit_credit_id;
    public $date_demande;
    public $statut = 'en_analyse';

    protected $rules = [
        'user_id' => 'required|exists:users,id',
        'montant_demande' => 'required|numeric|min:1',
        'duree_mois' => 'required|integer|min:1',
        'date_demande' => 'required|date',
    ];

    public function mount($loanApplicationId = null)
    {
        $this->loanApplicationId = $loanApplicationId;
        $this->date_demande = now()->toDateString();

        if ($loanApplicationId) {
            $loan = LoanApplication::findOrFail($loanApplicationId);
            $this->user_id = $loan->user_id;
            $this->business_id = $loan->business_id;
            $this->montant_demande = $loan->montant_demande;
            $this->duree_mois = $loan->duree_mois;
            $this->produit_credit_id = $loan->produit_credit_id;
            $this->statut = $loan->statut;
            $this->date_demande = optional($loan->date_demande)->toDateString();
        }
    }

    public function save()
    {
        $this->validate();

        $data = [
            'user_id' => $this->user_id,
            'business_id' => $this->business_id,
            'montant_demande' => $this->montant_demande,
            'duree_mois' => $this->duree_mois,
            'produit_credit_id' => $this->produit_credit_id,
            'date_demande' => $this->date_demande,
            'statut' => $this->statut,
            'agent_id' => Auth::id(),
        ];

        $loan = LoanApplication::updateOrCreate(['id' => $this->loanApplicationId], $data);

        $this->dispatch('loanSaved', $loan->id);
        session()->flash('message', 'Demande enregistrée.');
        return redirect()->route('credit.applications.show', $loan->id);
    }

    public function render()
    {
        // $businesses = Business::where('user_id', $this->user_id)->get();
        $businesses = Business::all();
        return view('livewire.credit.loan-application-create', compact('businesses'));
    }
}

