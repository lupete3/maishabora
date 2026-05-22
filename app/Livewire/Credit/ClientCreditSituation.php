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

        $this->selectedRepayments = $credit->repayments->map(function ($repayment) {
            $daysLate = 0;
            if (!$repayment->is_paid && $repayment->due_date < now()) {
                $daysLate = Carbon::parse($repayment->due_date)->diffInDays(now());
            } elseif ($repayment->is_paid && $repayment->paid_date && $repayment->paid_date > $repayment->due_date) {
                $daysLate = Carbon::parse($repayment->due_date)->diffInDays($repayment->paid_date);
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
                'principal_amount' => floatval($repayment->principal_amount ?? $repayment->expected_amount),
                'interest_amount' => floatval($repayment->interest_amount ?? 0),
                'paid_principal' => floatval($repayment->paid_principal),
                'paid_interest' => floatval($repayment->paid_interest),
                'paid_penalty' => floatval($repayment->paid_penalty),
            ];
        })->toArray();

        $this->dispatch('openModal', name: 'repaymentsModal');

    }

    public function render()
    {
        $credits = $this->user->credits->map(function ($credit) {
            $totalPaid = $credit->repayments->sum('paid_amount');
            $penalties = $credit->repayments->sum('penalty');
            
            // Le montant restant est la somme des restants dus par échéance (total_due - paid_amount)
            $remaining = $credit->repayments->sum(fn($r) => max(0.0, floatval($r->total_due) - floatval($r->paid_amount)));

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


