<?php

// app/Http/Livewire/CreditOverviewReport.php
namespace App\Livewire\Credit;

use Livewire\Component;
use App\Models\Credit;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CreditOverviewReport extends Component
{
    public $credits = [];

    // public function mount()
    // {
    //     $this->credits = Credit::where('is_paid', false)
    //         ->with(['user', 'repayments'])
    //         ->whereHas('user', fn($q) => $q->where('role', 'membre'))
    //         ->get();
    // }
    public $totaux = [];

    public function mount()
    {
        $this->credits = Credit::where('is_paid', false)
            ->with(['user', 'repayments'])
            ->whereHas('user', fn($q) => $q->where('role', 'membre'))
            ->get()
            ->filter(function ($credit) {
                return $credit->repayments->contains(function ($repayment) {
                    return !$repayment->penality && Carbon::parse($repayment->due_date)->lt(now());
                });
            })
            ->values();

        // Initialiser les totaux
        $this->totaux = [
            'credit_amount' => 0,
            'remaining_balance' => 0,
            'total_penalty' => 0,
            'range_1' => 0,
            'range_2' => 0,
            'range_3' => 0,
            'range_4' => 0,
            'range_5' => 0,
            'range_6' => 0,
            'range_7' => 0,
        ];

        foreach ($this->credits as $credit) {
            $details = $this->getCreditDetails($credit);

            $this->totaux['credit_amount'] += $details['credit_amount'];
            $this->totaux['remaining_balance'] += $details['remaining_balance'];
            $this->totaux['total_penalty'] += $details['total_penalty'];
            $this->totaux['range_1'] += $details['range_1'];
            $this->totaux['range_2'] += $details['range_2'];
            $this->totaux['range_3'] += $details['range_3'];
            $this->totaux['range_4'] += $details['range_4'];
            $this->totaux['range_5'] += $details['range_5'];
            $this->totaux['range_6'] += $details['range_6'];
            $this->totaux['range_7'] += $details['range_7'];
        }
    }


    public function getCreditDetails($credit)
    {
        $paidRepayments = $credit->repayments->where('is_paid', true);
        $unpaidRepayments = $credit->repayments->where('is_paid', false);

        $totalPaid = $paidRepayments->sum('paid_amount');
        $totalPenalty = $unpaidRepayments->sum('penalty');
        $remainingBalance = round($credit->amount - $totalPaid, 2);

        // Calcul du jour de retard maximal
        $maxDaysLate = $unpaidRepayments
            ->filter(fn($r) => Carbon::parse($r->due_date)->lt(now()))
            ->max(fn($r) => Carbon::parse($r->due_date)->diffInDays(now()));

        $maxDaysLate = (int) $maxDaysLate ?? 0;

        // Initialiser les tranches
        $ranges = [
            'range_1' => 0,  // 1-30j
            'range_2' => 0,  // 31-60j
            'range_3' => 0,  // 61-90j
            'range_4' => 0,  // 91-180j
            'range_5' => 0,  // 181-360j
            'range_6' => 0,  // 361-720j
            'range_7' => 0,  // >720j
        ];

        if ($maxDaysLate >= 1 && $maxDaysLate <= 30) {
            $ranges['range_1'] = $remainingBalance;
        } elseif ($maxDaysLate >= 31 && $maxDaysLate <= 60) {
            $ranges['range_2'] = $remainingBalance;
        } elseif ($maxDaysLate >= 61 && $maxDaysLate <= 90) {
            $ranges['range_3'] = $remainingBalance;
        } elseif ($maxDaysLate >= 91 && $maxDaysLate <= 180) {
            $ranges['range_4'] = $remainingBalance;
        } elseif ($maxDaysLate >= 181 && $maxDaysLate <= 360) {
            $ranges['range_5'] = $remainingBalance;
        } elseif ($maxDaysLate >= 361 && $maxDaysLate <= 720) {
            $ranges['range_6'] = $remainingBalance;
        } elseif ($maxDaysLate > 720) {
            $ranges['range_7'] = $remainingBalance;
        }

        return [
            'credit_id' => $credit->id,
            'member_name' => $credit->user->name.' '.$credit->user->postnom.' '.$credit->user->prenom.' => '.$credit->user->telephone,
            'credit_date' => $credit->start_date,
            'credit_amount' => $credit->amount,
            'remaining_balance' => $remainingBalance,
            'total_penalty' => $totalPenalty,
            'penalty_percentage' => $remainingBalance > 0 ? round(($totalPenalty / $remainingBalance) * 100, 2) : 0,
            'days_late' => $maxDaysLate,
            'range_1' => $ranges['range_1'],
            'range_2' => $ranges['range_2'],
            'range_3' => $ranges['range_3'],
            'range_4' => $ranges['range_4'],
            'range_5' => $ranges['range_5'],
            'range_6' => $ranges['range_6'],
            'range_7' => $ranges['range_7'],
        ];
    }


    public function render()
    {
        return view('livewire.credit.credit-overview-report');
    }
}
