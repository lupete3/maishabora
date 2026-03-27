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
    public $stats = [];

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
        // 🚀 Optimisation : Filtrage direct en SQL au lieu de filter() en PHP
        $query = Credit::where('is_paid', false)
            ->with(['user', 'repayments'])
            ->whereHas('user', fn($q) => $q->where('role', 'membre'))
            ->whereHas('repayments', function ($q) {
                $q->where('is_paid', false)
                    ->where('due_date', '<', now());
            });

        if ($this->selectedCurrency !== 'all') {
            $query->where('currency', $this->selectedCurrency);
        }

        $rawCredits = $query->get();

        $this->credits = [];
        $this->initializeTotals();

        $totalLateDays = 0;
        $count = 0;

        foreach ($rawCredits as $credit) {
            $details = $this->calculateCreditDetails($credit);
            $this->credits[] = $details;

            // Cumul des totaux
            foreach ($this->totaux as $key => $value) {
                if (isset($details[$key])) {
                    $this->totaux[$key] += $details[$key];
                }
            }

            $totalLateDays += $details['days_late'];
            $count++;
        }

        // 📊 Données pour les KPI Cards
        $this->stats = [
            'total_late_amount' => $this->totaux['remaining_balance'],
            'case_count' => $count,
            'avg_late_days' => $count > 0 ? round($totalLateDays / $count) : 0,
            'penalty_impact' => $this->totaux['remaining_balance'] > 0
                ? round(($this->totaux['total_penalty'] / $this->totaux['remaining_balance']) * 100, 1)
                : 0
        ];
    }

    public function initializeTotals()
    {
        $this->totaux = array_fill_keys([
            'credit_amount',
            'remaining_balance',
            'total_penalty',
            'range_1',
            'range_2',
            'range_3',
            'range_4',
            'range_5',
            'range_6',
            'range_7'
        ], 0);
    }

    private function calculateCreditDetails($credit)
    {
        $unpaid = $credit->repayments->where('is_paid', false);
        $totalPaid = $credit->repayments->where('is_paid', true)->sum('paid_amount');

        $totalPenalty = $unpaid->sum('penalty');
        $remaining = round($credit->amount - $totalPaid, 2);

        $maxLate = $unpaid->filter(fn($r) => $r->due_date->lt(now()))
            ->max(fn($r) => $r->due_date->diffInDays(now()));

        $ranges = array_fill_keys([
            'range_1',
            'range_2',
            'range_3',
            'range_4',
            'range_5',
            'range_6',
            'range_7'
        ], 0);

        if ($maxLate >= 1 && $maxLate <= 30)
            $ranges['range_1'] = $remaining;
        elseif ($maxLate <= 60)
            $ranges['range_2'] = $remaining;
        elseif ($maxLate <= 90)
            $ranges['range_3'] = $remaining;
        elseif ($maxLate <= 180)
            $ranges['range_4'] = $remaining;
        elseif ($maxLate <= 360)
            $ranges['range_5'] = $remaining;
        elseif ($maxLate <= 720)
            $ranges['range_6'] = $remaining;
        elseif ($maxLate > 720)
            $ranges['range_7'] = $remaining;

        return array_merge($ranges, [
            'credit_id' => $credit->id,
            'currency' => $credit->currency,
            'member_name' => $credit->user->name . ' ' . $credit->user->postnom . ' ' . $credit->user->prenom,
            'member_phone' => $credit->user->telephone,
            'member_code' => $credit->user->code,
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