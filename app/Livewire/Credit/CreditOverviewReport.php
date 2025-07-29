<?php

namespace App\Livewire\Credit;

use Livewire\Component;
use App\Models\Credit;
use Carbon\Carbon;

class CreditOverviewReport extends Component
{
    public $credits = [];
    public $totaux = [];
    public $selectedCurrency = 'all';

    public function mount()
    {
        $this->loadCredits();
    }

    public function updatedSelectedCurrency()
    {
        $this->loadCredits();
    }

    public function loadCredits()
    {
        $query = Credit::where('is_paid', false)
            ->with(['user', 'repayments'])
            ->whereHas('user', fn($q) => $q->where('role', 'membre'));

        if ($this->selectedCurrency !== 'all') {
            $query->where('currency', $this->selectedCurrency);
        }

        $this->credits = $query->get()->filter(function ($credit) {
            return $credit->repayments->contains(function ($repayment) {
                return !$repayment->penality && Carbon::parse($repayment->due_date)->lt(now());
            });
        })->values();

        $this->initializeTotals();
    }

    public function initializeTotals()
    {
        $this->totaux = array_fill_keys([
            'credit_amount', 'remaining_balance', 'total_penalty',
            'range_1', 'range_2', 'range_3', 'range_4',
            'range_5', 'range_6', 'range_7'
        ], 0);

        foreach ($this->credits as $credit) {
            $details = $this->getCreditDetails($credit);

            foreach ($this->totaux as $key => $value) {
                $this->totaux[$key] += $details[$key];
            }
        }
    }

    public function getCreditDetails($credit)
    {
        $paid = $credit->repayments->where('is_paid', true);
        $unpaid = $credit->repayments->where('is_paid', false);

        $totalPaid = $paid->sum('paid_amount');
        $totalPenalty = $unpaid->sum('penalty');
        $remaining = round($credit->amount - $totalPaid, 2);

        $maxLate = $unpaid->filter(fn($r) => Carbon::parse($r->due_date)->lt(now()))
            ->max(fn($r) => Carbon::parse($r->due_date)->diffInDays(now()));

        $ranges = array_fill_keys([
            'range_1', 'range_2', 'range_3', 'range_4',
            'range_5', 'range_6', 'range_7'
        ], 0);

        if ($maxLate >= 1 && $maxLate <= 30) $ranges['range_1'] = $remaining;
        elseif ($maxLate <= 60) $ranges['range_2'] = $remaining;
        elseif ($maxLate <= 90) $ranges['range_3'] = $remaining;
        elseif ($maxLate <= 180) $ranges['range_4'] = $remaining;
        elseif ($maxLate <= 360) $ranges['range_5'] = $remaining;
        elseif ($maxLate <= 720) $ranges['range_6'] = $remaining;
        elseif ($maxLate > 720) $ranges['range_7'] = $remaining;

        return array_merge($ranges, [
            'credit_id' => $credit->id,
            'member_name' => $credit->user->name . ' ' . $credit->user->postnom . ' ' . $credit->user->prenom . ' => ' . $credit->user->telephone,
            'credit_date' => $credit->created_at,
            'credit_payment' => $credit->start_date,
            'credit_amount' => $credit->amount,
            'remaining_balance' => $remaining,
            'total_penalty' => $totalPenalty,
            'penalty_percentage' => $remaining > 0 ? round(($totalPenalty / $remaining) * 100, 2) : 0,
            'days_late' => (int) $maxLate,
        ]);
    }

    public function render()
    {
        return view('livewire.credit.credit-overview-report', [
            'currencies' => Credit::distinct()->pluck('currency')->prepend('toutes')
        ]);
    }
}