<?php

namespace App\Livewire\Credit;

use Livewire\Component;
use App\Models\LoanBalance;

class LoanBalanceEditor extends Component
{
    public $loan_application_id;
    public $cash;
    public $creances;
    public $stock;
    public $actifs_immobilises;
    public $dettes_formelles_ct;
    public $dettes_formelles_mt;
    public $dettes_formelles_lt;
    public $dettes_informelles_ct;
    public $dettes_informelles_mt;
    public $dettes_informelles_lt;
    public $fonds_propres;

    public function mount($loan_application_id = null)
    {
        $this->loan_application_id = $loan_application_id;
        if ($loan_application_id)
            $this->loadForLoan($loan_application_id);
    }

    public function loadForLoan($id)
    {
        $b = LoanBalance::firstOrNew(['loan_application_id' => $id]);
        $this->fill($b->toArray());
    }

    public function calculateTotals()
    {
        $totalAssets = ($this->cash ?? 0) + ($this->creances ?? 0) + ($this->stock ?? 0) + ($this->actifs_immobilises ?? 0);
        $totalDettes = ($this->dettes_formelles_ct ?? 0) + ($this->dettes_formelles_mt ?? 0) + ($this->dettes_formelles_lt ?? 0)
            + ($this->dettes_informelles_ct ?? 0) + ($this->dettes_informelles_mt ?? 0) + ($this->dettes_informelles_lt ?? 0);
        $totalPassif = $totalDettes + ($this->fonds_propres ?? 0);

        return compact('totalAssets', 'totalDettes', 'totalPassif');
    }

    public function save()
    {
        $cols = [
            'cash',
            'creances',
            'stock',
            'actifs_immobilises',
            'dettes_formelles_ct',
            'dettes_formelles_mt',
            'dettes_formelles_lt',
            'dettes_informelles_ct',
            'dettes_informelles_mt',
            'dettes_informelles_lt',
            'fonds_propres'
        ];

        $data = [];
        foreach ($cols as $c)
            $data[$c] = $this->{$c} ?? 0;

        $totals = $this->calculateTotals();
        $data['total_actif'] = $totals['totalAssets'];
        $data['total_dettes'] = $totals['totalDettes'];
        $data['total_passif'] = $totals['totalPassif'];
        $data['date_calcul'] = now();

        LoanBalance::updateOrCreate(['loan_application_id' => $this->loan_application_id], $data);
        notyf()->success('Bilan enregistré');
        $this->dispatch('balanceSaved', $this->loan_application_id);
    }

    public function render()
    {
        return view('livewire.credit.loan-balance-editor');
    }
}