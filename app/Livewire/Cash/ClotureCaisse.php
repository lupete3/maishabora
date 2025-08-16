<?php

namespace App\Livewire\Cash;

use App\Helpers\UserLogHelper;
use App\Models\Cloture;
use App\Models\Transaction;
use App\Models\Billetage;
use App\Models\UserLog;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class ClotureCaisse extends Component
{
    public $date;
    public $logical_usd = 0;
    public $logical_cdf = 0;

    public $billetages_usd = [];
    public $billetages_cdf = [];

    public $physical_usd = 0;
    public $physical_cdf = 0;

    public $gap_usd = 0;
    public $gap_cdf = 0;

    public $denominations_usd = [100, 50, 20, 10, 5, 1];
    public $denominations_cdf = [20000, 10000, 5000, 1000, 500, 200, 100];

    public function mount()
    {
        $this->date = Carbon::now()->format('Y-m-d');

        foreach ($this->denominations_usd as $d) {
            $this->billetages_usd[$d] = 0;
        }

        foreach ($this->denominations_cdf as $d) {
            $this->billetages_cdf[$d] = 0;
        }

        $this->calculateLogicalBalances();
    }

    public function updated($field)
    {
        if (str_starts_with($field, 'billetages_')) {
            $this->calculatePhysicalAndGap();
        }
    }

    public function calculateLogicalBalances()
    {
        $userId = Auth::id();
        $today = Carbon::parse($this->date)->startOfDay();

        $usd = Transaction::where('user_id', $userId)
            ->where('currency', 'USD')
            ->whereDate('created_at', $today)
            ->sum('amount');

        $cdf = Transaction::where('user_id', $userId)
            ->where('currency', 'CDF')
            ->whereDate('created_at', $today)
            ->sum('amount');

        $this->logical_usd = $usd;
        $this->logical_cdf = $cdf;

        $this->calculatePhysicalAndGap();
    }

    public function calculatePhysicalAndGap()
    {
        $usd_total = 0;
        foreach ($this->billetages_usd as $denomination => $qty) {
            $usd_total += $denomination * intval($qty);
        }

        $cdf_total = 0;
        foreach ($this->billetages_cdf as $denomination => $qty) {
            $cdf_total += $denomination * intval($qty);
        }

        $this->physical_usd = $usd_total;
        $this->physical_cdf = $cdf_total;

        $this->gap_usd = $this->physical_usd - $this->logical_usd;
        $this->gap_cdf = $this->physical_cdf - $this->logical_cdf;
    }

    public function submitCloture()
    {
        $this->validate([
            'date' => 'required|date',
        ]);

        $userId = Auth::id();

        $existing = Cloture::where('user_id', $userId)->where('closing_date', $this->date)->first();
        if ($existing) {
            notyf()->error("Clôture déjà enregistrée pour aujourd’hui.");
            return;
        }

        $cloture = Cloture::create([
            'user_id' => $userId,
            'closing_date' => $this->date,
            'logical_usd' => $this->logical_usd,
            'logical_cdf' => $this->logical_cdf,
            'physical_usd' => $this->physical_usd,
            'physical_cdf' => $this->physical_cdf,
            'gap_usd' => $this->gap_usd,
            'gap_cdf' => $this->gap_cdf,
        ]);

        foreach ($this->billetages_usd as $denomination => $qty) {
            if ($qty > 0) {
                Billetage::create([
                    'cloture_id' => $cloture->id,
                    'currency' => 'USD',
                    'denomination' => $denomination,
                    'quantity' => $qty,
                    'total' => $denomination * $qty,
                ]);
            }
        }

        foreach ($this->billetages_cdf as $denomination => $qty) {
            if ($qty > 0) {
                Billetage::create([
                    'cloture_id' => $cloture->id,
                    'currency' => 'CDF',
                    'denomination' => $denomination,
                    'quantity' => $qty,
                    'total' => $denomination * $qty,
                ]);
            }
        }

        UserLogHelper::log_user_activity(
            action: 'cloture_caisse',
            description: "Clôture de caisse pour {$this->date} par l'utilisateur ID {$userId}. Solde logique: {$this->logical_usd} USD, {$this->logical_cdf} CDF. Solde physique: {$this->physical_usd} USD, {$this->physical_cdf} CDF. Écart: {$this->gap_usd} USD, {$this->gap_cdf} CDF."
        );

        notyf()->success("Clôture enregistrée avec succès !");
    }

    public function render()
    {
        return view('livewire.cash.cloture-caisse');
    }
}

