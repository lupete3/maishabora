<?php

namespace App\Livewire\Credit;

use Livewire\Component;
use App\Models\User;
use App\Models\Credit;
use Carbon\Carbon;

class ClientCreditSituation extends Component
{
    public $user;
    public $selectedRepayments = [];
    public $selectedCreditId;
    public $selectedCredit;

    public function mount($userId)
    {
        $this->user = User::with(['credits.repayments'])->findOrFail($userId);
    }


    public function closeRepaymentsModal()
    {
        $this->dispatch('closeModal', name: 'repaymentsModal');
    }

    public function showRepayments($creditId)
    {
        $credit = Credit::with('repayments')->findOrFail($creditId);

        $this->selectedCreditId = $creditId;
        $this->selectedCredit = $credit;

        $this->selectedRepayments = $credit->repayments->map(function ($repayment) {
            $daysLate = null;
            if (!$repayment->is_paid && $repayment->due_date < now()) {
                $daysLate = Carbon::parse($repayment->due_date)->diffInDays(now());
            }

            return [
                'id' => $repayment->id,
                'due_date' => $repayment->due_date,
                'expected_amount' => $repayment->expected_amount,
                'paid_amount' => $repayment->paid_amount,
                'penalty' => $repayment->penalty,
                'total_due' => $repayment->total_due,
                'is_paid' => $repayment->is_paid,
                'paid_date' => $repayment->paid_date,
                'days_late' => $daysLate,
            ];
        })->toArray();

        $this->dispatch('openModal', name: 'repaymentsModal');

    }

    public function render()
    {
        $credits = $this->user->credits->map(function ($credit) {
            $totalPaid = $credit->repayments->sum('paid_amount');
            $totalDue = $credit->repayments->sum('total_due');
            $penalties = $credit->repayments->sum('penalty');
            $remaining = $totalDue - ($totalPaid + $penalties);

            $lateCount = $credit->repayments->where('is_paid', false)
                ->where('due_date', '<', now())
                ->count();

            return [
                'id' => $credit->id,
                'amount' => $credit->amount,
                'currency' => $credit->currency,
                'interest_rate' => $credit->interest_rate,
                'start_date' => $credit->start_date,
                'due_date' => $credit->due_date,
                'total_paid' => $totalPaid,
                'remaining' => $remaining,
                'penalties' => $penalties,
                'late_count' => $lateCount,
                'is_paid' => $credit->is_paid,
            ];
        });

        return view('livewire.credit.client-credit-situation', [
            'credits' => $credits,
        ]);
    }
}


