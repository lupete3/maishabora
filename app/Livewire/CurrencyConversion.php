<?php

namespace App\Livewire;

use App\Models\ExchangeRate;
use Livewire\Component;
use App\Models\MainCashRegister;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CurrencyConversion extends Component
{
    public $from_currency = 'USD';
    public $to_currency = 'CDF';
    public $amount;
    public $exchange_rate;

    public $rates = [
        'USD' => 'CDF',
        'CDF' => 'USD',
    ];

    public function convert()
    {
        $this->validate([
            'from_currency' => 'required|in:USD,CDF',
            'to_currency' => 'required|in:USD,CDF|different:from_currency',
            'amount' => 'required|numeric|min:0.01',
        ]);

        // 🔥 Récupérer automatiquement le dernier taux enregistré
        $rateRecord = ExchangeRate::getLatestRate($this->from_currency, $this->to_currency);

        if (!$rateRecord) {
            $this->addError('amount', 'Aucun taux de change défini pour cette conversion.');
            return;
        }

        $this->exchange_rate = $rateRecord->rate;

        DB::transaction(function () {
            // Récupérer les caisses
            $fromRegister = MainCashRegister::getByCurrency($this->from_currency);
            $toRegister = MainCashRegister::getByCurrency($this->to_currency);

            // Vérifier le solde disponible
            if ($fromRegister->balance < $this->amount) {
                $this->addError('amount', 'Solde insuffisant dans la caisse ' . $this->from_currency);
                throw new \Exception('Solde insuffisant');
            }

            // Calcul conversion
            $convertedAmount = $this->amount * $this->exchange_rate;

            // Débiter la caisse source
            $fromRegister->balance -= $this->amount;
            $fromRegister->save();

            // Créditer la caisse cible
            $toRegister->balance += $convertedAmount;
            $toRegister->save();

            // User effectuant l'opération
            $admin = Auth::user();

            // Enregistrer la transaction (sortie)
            Transaction::create([
                'user_id' => $admin->id,
                'type' => 'conversion_sortie',
                'currency' => $this->from_currency,
                'amount' => $this->amount,
                'exchange_rate' => $this->exchange_rate,
                'balance_after' => $fromRegister->balance,
                'description' => "Conversion de {$this->amount} {$this->from_currency} vers {$this->to_currency}",
            ]);

            // Enregistrer la transaction (entrée)
            Transaction::create([
                'user_id' => $admin->id,
                'type' => 'conversion_entree',
                'currency' => $this->to_currency,
                'amount' => $convertedAmount,
                'exchange_rate' => $this->exchange_rate,
                'balance_after' => $toRegister->balance,
                'description' => "Conversion depuis {$this->from_currency} vers {$this->to_currency} : reçu {$convertedAmount} {$this->to_currency}",
            ]);
        });

        notyf()->success('Conversion effectuée avec succès.');

        $this->reset(['amount']);
        $this->dispatch('$refresh');
    }

    public function render()
    {
        return view('livewire.currency-conversion', [
            'balances' => MainCashRegister::all()->keyBy('currency'),
        ]);
    }
}

