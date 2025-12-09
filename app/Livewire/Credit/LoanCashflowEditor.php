<?php

namespace App\Livewire\Credit;

use Livewire\Component;
use App\Models\LoanCashflow;
use App\Models\LoanApplication;

class LoanCashflowEditor extends Component
{
    public $loan_application_id;
    public $type_activite;
    public $chiffre_affaires_mensuel_estime;
    public $camv_ou_achats_mensuels;
    public $charges_activite_mensuelles;
    public $autres_revenus_mensuels;
    public $charges_menage_mensuelles;
    public $owner_withdrawals_monthly = 0;

    // propriétés calculées (facultatif si tu veux les afficher dans Blade)
    public $revenu_disponible_mensuel;
    public $capacite_remboursement_mensuelle;
    public $date_calcul;

    protected $listeners = ['loanSaved' => 'loadForLoan'];

    protected $rules = [
        'loan_application_id' => 'required|exists:loan_applications,id',
        'type_activite' => 'required|string',
        'chiffre_affaires_mensuel_estime' => 'nullable|numeric',
        'camv_ou_achats_mensuels' => 'nullable|numeric',
        'charges_activite_mensuelles' => 'nullable|numeric',
        'autres_revenus_mensuels' => 'nullable|numeric',
        'charges_menage_mensuelles' => 'nullable|numeric',
        'owner_withdrawals_monthly' => 'nullable|numeric',
    ];

    public function mount($loan_application_id = null)
    {
        $this->loan_application_id = $loan_application_id;

        if ($loan_application_id) {
            $this->loadForLoan($loan_application_id);
        }
    }

    public function loadForLoan($id)
    {
        $this->loan_application_id = $id;

        $cf = LoanCashflow::firstOrNew(['loan_application_id' => $id]);

        // Filtrer uniquement les champs présents dans le composant
        $allowed = [
            'type_activite',
            'chiffre_affaires_mensuel_estime',
            'camv_ou_achats_mensuels',
            'charges_activite_mensuelles',
            'autres_revenus_mensuels',
            'charges_menage_mensuelles',
            'owner_withdrawals_monthly',
            'revenu_disponible_mensuel',
            'capacite_remboursement_mensuelle',
            'date_calcul',
        ];

        $this->fill($cf->only($allowed));
    }

    public function calculate()
    {
        // Calculer le revenu disponible et la capacité de remboursement
        $netActivity = ($this->chiffre_affaires_mensuel_estime ?? 0)
            - ($this->camv_ou_achats_mensuels ?? 0)
            - ($this->charges_activite_mensuelles ?? 0);

        $available = $netActivity
            + ($this->autres_revenus_mensuels ?? 0)
            - ($this->charges_menage_mensuelles ?? 0)
            - ($this->owner_withdrawals_monthly ?? 0);

        // Capacité de remboursement (50% conservatif)
        $capacity = max(0, $available * 0.5);

        // Stocker localement pour affichage
        $this->revenu_disponible_mensuel = $available;
        $this->capacite_remboursement_mensuelle = $capacity;
        $this->date_calcul = now();

        // Émettre un événement si besoin
        $this->dispatch('cashflowCalculated', ['available' => $available, 'capacity' => $capacity]);

        return ['available' => $available, 'capacity' => $capacity];
    }

    public function save()
    {
        $this->validate();

        $res = $this->calculate();

        $data = [
            'type_activite' => $this->type_activite,
            'chiffre_affaires_mensuel_estime' => $this->chiffre_affaires_mensuel_estime,
            'camv_ou_achats_mensuels' => $this->camv_ou_achats_mensuels,
            'charges_activite_mensuelles' => $this->charges_activite_mensuelles,
            'autres_revenus_mensuels' => $this->autres_revenus_mensuels,
            'charges_menage_mensuelles' => $this->charges_menage_mensuelles,
            'owner_withdrawals_monthly' => $this->owner_withdrawals_monthly,
            'revenu_disponible_mensuel' => $res['available'],
            'capacite_remboursement_mensuelle' => $res['capacity'],
            'date_calcul' => now(),
        ];
        
        // Enregistrer ou mettre à jour
        LoanCashflow::updateOrCreate(
            ['loan_application_id' => $this->loan_application_id],
            $data
        );

        session()->flash('message', 'TFR enregistré');
        $this->dispatch('tfrSaved', $this->loan_application_id);
    }

    public function render()
    {
        return view('livewire.credit.loan-cashflow-editor');
    }
}