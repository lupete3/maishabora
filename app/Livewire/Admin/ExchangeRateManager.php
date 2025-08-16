<?php

namespace App\Livewire\Admin;

use App\Helpers\UserLogHelper;
use App\Models\ExchangeRate;
use Livewire\Component;
use Carbon\Carbon;

class ExchangeRateManager extends Component
{
    public $rates;
    public $editId;
    public $from_currency = 'USD';
    public $to_currency = 'CDF';
    public $rate;
    public $applied_at;

    public function mount()
    {
        $this->loadRates();
    }

    public function loadRates()
    {
        $this->rates = ExchangeRate::orderByDesc('created_at')->get();
    }

    public function save()
    {
        $this->validate([
            'from_currency' => 'required|in:USD,CDF',
            'to_currency' => 'required|in:USD,CDF|different:from_currency',
            'rate' => 'required|numeric|min:0.0001',
            'applied_at' => 'nullable|date',
        ]);

        ExchangeRate::create([
            'from_currency' => $this->from_currency,
            'to_currency' => $this->to_currency,
            'rate' => $this->rate,
            'applied_at' => $this->applied_at ?? now(),
        ]);

        UserLogHelper::log_user_activity(
            action: 'ajout_taux_de_change',
            description: "Ajout du taux de change de {$this->from_currency} vers {$this->to_currency} : {$this->rate} à partir de {$this->applied_at}"
        );

        notyf()->success( 'Taux ajouté avec succès.');

        $this->resetForm();
        $this->loadRates();
    }

    public function edit($id)
    {
        $rate = ExchangeRate::findOrFail($id);
        $this->editId = $id;
        $this->from_currency = $rate->from_currency;
        $this->to_currency = $rate->to_currency;
        $this->rate = $rate->rate;
        $this->applied_at = optional($rate->applied_at)->format('Y-m-d\TH:i');
    }

    public function update()
    {
        $this->validate([
            'from_currency' => 'required|in:USD,CDF',
            'to_currency' => 'required|in:USD,CDF|different:from_currency',
            'rate' => 'required|numeric|min:0.0001',
            'applied_at' => 'nullable|date',
        ]);

        $rate = ExchangeRate::findOrFail($this->editId);
        $rate->update([
            'from_currency' => $this->from_currency,
            'to_currency' => $this->to_currency,
            'rate' => $this->rate,
            'applied_at' => $this->applied_at ?? now(),
        ]);

        notyf()->success( 'Taux mis à jour avec succès.');

        $this->resetForm();
        $this->loadRates();
    }

    public function cancelEdit()
    {
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->editId = null;
        $this->from_currency = 'USD';
        $this->to_currency = 'CDF';
        $this->rate = null;
        $this->applied_at = null;
    }

    public function render()
    {
        return view('livewire.admin.exchange-rate-manager');
    }
}

