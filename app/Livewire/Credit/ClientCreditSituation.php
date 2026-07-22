<?php

namespace App\Livewire\Credit;

use Livewire\Component;
use App\Models\User;
use App\Models\Credit;
use App\Models\Repayment;
use Carbon\Carbon;

class ClientCreditSituation extends Component
{
    public $user;
    public $selectedRepayments = [];
    public $selectedCreditId;
    public $selectedCredit;
    public $selectedRepaymentEdit = [];
    public $edit_due_date;
    public $edit_paid_date;
    public $edit_expected_amount;
    public $edit_penalty;
    public $edit_total_due;

    public function mount($userId)
    {
        $this->user = User::with(['credits.repayments'])->findOrFail($userId);
    }

    public function closeRepaymentsModal()
    {
        $this->dispatch('closeModal', name: 'repaymentsModal');
    }

    public function closeRepaymentsEditModal()
    {
        $this->dispatch('closeModal', name: 'editRepaymentModal');
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

    public function showEditRepaymentMaodal($repaymentId)
    {
        $repayment = Repayment::findOrFail($repaymentId);
        $this->selectedRepaymentEdit = $repayment;
        $this->edit_due_date = \Carbon\Carbon::parse($repayment->due_date)->format('Y-m-d');
        $this->edit_paid_date = \Carbon\Carbon::parse($repayment->paid_date)->format('Y-m-d');
        $this->edit_expected_amount = $repayment->expected_amount;
        $this->edit_penalty = $repayment->penalty;
        $this->edit_total_due = $repayment->total_due;
        $this->dispatch('openModal', name: 'editRepaymentModal');
    }

    public function updateRepayment($repaymentId)
    {
        $repayment = Repayment::findOrFail($repaymentId);

        $this->validate([
            'edit_due_date' => 'required|date',
            'edit_paid_date' => 'nullable|date',
            'edit_expected_amount' => 'required|numeric|min:0',
            'edit_penalty' => 'nullable|numeric|min:0',
            'edit_total_due' => 'nullable|numeric|min:0',
        ]);

        $repayment->update([
            'due_date' => $this->edit_due_date,
            'paid_date' => $this->edit_paid_date,
            'expected_amount' => $this->edit_expected_amount,
            'penalty' => $this->edit_penalty,
            'total_due' => (float)$this->edit_expected_amount + (float)$this->edit_penalty,
        ]);

        notyf()->success("Remboursement mis à jour avec succès.");

        // Refresh the selected repayments after update
        $this->showRepayments($repayment->credit_id);

        $this->closeRepaymentsEditModal();
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


