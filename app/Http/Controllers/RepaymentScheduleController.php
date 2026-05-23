<?php

// app/Http/Controllers/RepaymentScheduleController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Credit;
use App\Models\User;
use App\Models\Repayment;
use Illuminate\Support\Facades\Auth;

class RepaymentScheduleController extends Controller
{

    public function generate($creditId)
    {
        // Récupération du crédit
        $credit = Credit::with(['user', 'repayments'])->findOrFail($creditId);
        $member = $credit->user;
        $agent = Auth::user();
        // Tri des échéances
        $repayments = $credit->repayments->sortBy('due_date');

        if($credit->credit_type == 'degressif'){
            return $this->generateDegressiveSchedule($credit, $member, $agent, $repayments);
        } else {
            return $this->generateConstantSchedule($credit, $member, $agent, $repayments);
        }
    }

    public function generateDegressiveSchedule($credit, $member, $agent, $repayments)
    {
        $detailedRepayments = [];
        $remainingCapital = $credit->amount;

        $countRepayments = $repayments->count();
        $index = 0;

        $totalCapital = 0;
        $totalInterest = 0;
        $totalPenalty = 0;
        $totalDue = 0;

        foreach ($repayments as $repayment) {
            $index++;

            // Capital remboursé à chaque échéance
            $capitalPart = round($credit->amount / $credit->installments, 2);
            // Intérêt dégressif par échéance
            $interestPart = round($remainingCapital * ($credit->interest_rate / 100), 2);
            // Mensualité de base (Capital + Intérêt)
            $mensualite = $capitalPart + $interestPart;

            // Dernière ligne
            if ($index == $countRepayments) {
                $capitalRepaid = round($remainingCapital, 2);
                $due = $capitalRepaid + $interestPart + ($repayment->penalty ?? 0);
            } else {
                $capitalRepaid = $capitalPart;
                $due = $mensualite + ($repayment->penalty ?? 0);
            }

            $penalty = $repayment->penalty ?? 0;

            $detailedRepayments[] = [
                'repayment_id'      => $repayment->id,
                'due_date'           => $repayment->due_date,
                'opening_capital'    => $remainingCapital,
                'capital_repaid'     => $capitalRepaid,
                'interest'           => $interestPart,
                'penalty'            => $penalty,
                'due'                => $due,
                'remaining_capital'  => round($remainingCapital - $capitalRepaid, 2),
            ];

            $totalCapital += $capitalRepaid;
            $totalInterest += $interestPart;
            $totalPenalty += $penalty;
            $totalDue += $due;

            $remainingCapital = round($remainingCapital - $capitalRepaid, 2);
        }

        $data = [
            'credit'        => $credit,
            'member'        => $member,
            'agent'         => $agent,
            'repayments'    => $detailedRepayments,
            'totalCapital'  => $totalCapital,
            'totalInterest' => $totalInterest,
            'totalPenalty'  => $totalPenalty,
            'totalDue'      => $totalDue,
        ];
        $pdf = Pdf::loadView('pdf.repayment-schedule', $data);
        return $pdf->stream("plan_rem_{$credit->id}.pdf");
    }

    public function generateConstantSchedule($credit, $member, $agent, $repayments)
    {
        $detailedRepayments = [];
        $remainingCapital = $credit->amount;

        $countRepayments = $repayments->count();
        $index = 0;

        $totalCapital = 0;
        $totalInterest = 0;
        $totalPenalty = 0;
        $totalDue = 0;

        // Capital remboursé à chaque échéance
        $capitalPart = round($credit->amount / $credit->installments, 2);
        // Intérêt constant par échéance
        $interestPart = round($credit->amount * ($credit->interest_rate / 100), 2);
        // Mensualité de base (Capital + Intérêt)
        $mensualite = $capitalPart + $interestPart;

        foreach ($repayments as $repayment) {
            $index++;

            // Dernière ligne
            if ($index == $countRepayments) {
                $capitalRepaid = round($remainingCapital, 2);
                $due = $capitalRepaid + $interestPart + ($repayment->penalty ?? 0);
            } else {
                $capitalRepaid = $capitalPart;
                $due = $mensualite + ($repayment->penalty ?? 0);
            }

            $penalty = $repayment->penalty ?? 0;

            $detailedRepayments[] = [
                'repayment_id'      => $repayment->id,
                'due_date'           => $repayment->due_date,
                'opening_capital'    => $remainingCapital,
                'capital_repaid'     => $capitalRepaid,
                'interest'           => $interestPart,
                'penalty'            => $penalty,
                'due'                => $due,
                'remaining_capital'  => round($remainingCapital - $capitalRepaid, 2),
            ];

            $totalCapital += $capitalRepaid;
            $totalInterest += $interestPart;
            $totalPenalty += $penalty;
            $totalDue += $due;

            $remainingCapital = round($remainingCapital - $capitalRepaid, 2);
        }

        $data = [
            'credit'        => $credit,
            'member'        => $member,
            'agent'         => $agent,
            'repayments'    => $detailedRepayments,
            'totalCapital'  => $totalCapital,
            'totalInterest' => $totalInterest,
            'totalPenalty'  => $totalPenalty,
            'totalDue'      => $totalDue,
        ];
        $pdf = Pdf::loadView('pdf.repayment-schedule', $data);
        return $pdf->stream("plan_rem_{$credit->id}.pdf");
    }

    public function exportSituationPdf($creditId)
    {
        $credit = Credit::with(['user', 'repayments'])->findOrFail($creditId);
        $member = $credit->user;
        $agent = Auth::user();

        $repayments = $credit->repayments->sortBy('due_date');

        $totalPrincipalExpected = 0;
        $totalInterestExpected  = 0;
        $totalPenaltyCumulative = 0;
        $totalDueCumulative     = 0;

        $totalPrincipalPaid = 0;
        $totalInterestPaid  = 0;
        $totalPenaltyPaid   = 0;
        $totalPaid          = 0;

        $totalRemaining     = 0;

        $detailedRepayments = [];

        foreach ($repayments as $r) {
            $principal = floatval($r->principal_amount ?? $r->expected_amount);
            $interest  = floatval($r->interest_amount ?? 0);
            $penalty   = floatval($r->penalty);
            $totalDue  = floatval($r->total_due);

            $paidTotal = floatval($r->paid_amount);
            $paidPri   = floatval($r->paid_principal);
            $paidInt   = floatval($r->paid_interest);
            $paidPen   = floatval($r->paid_penalty);

            $remaining = max(0.0, $totalDue - $paidTotal);

            $daysLate = 0;
            if (!$r->is_paid && $r->due_date < now()) {
                $daysLate = \Carbon\Carbon::parse($r->due_date)->diffInDays(now());
            } elseif ($r->is_paid && $r->paid_date && $r->paid_date > $r->due_date) {
                $daysLate = \Carbon\Carbon::parse($r->due_date)->diffInDays($r->paid_date);
            }

            $detailedRepayments[] = [
                'due_date'          => $r->due_date,
                'principal_amount'  => $principal,
                'interest_amount'   => $interest,
                'penalty'           => $penalty,
                'total_due'         => $totalDue,
                'paid_amount'       => $paidTotal,
                'paid_principal'    => $paidPri,
                'paid_interest'     => $paidInt,
                'paid_penalty'      => $paidPen,
                'remaining'         => $remaining,
                'is_paid'           => $r->is_paid,
                'days_late'         => $daysLate,
            ];

            $totalPrincipalExpected += $principal;
            $totalInterestExpected  += $interest;
            $totalPenaltyCumulative += $penalty;
            $totalDueCumulative     += $totalDue;

            $totalPrincipalPaid     += $paidPri;
            $totalInterestPaid      += $paidInt;
            $totalPenaltyPaid       += $paidPen;
            $totalPaid              += $paidTotal;

            $totalRemaining         += $remaining;
        }

        $data = [
            'credit'                 => $credit,
            'member'                 => $member,
            'agent'                  => $agent,
            'repayments'             => $detailedRepayments,
            'totalPrincipalExpected' => $totalPrincipalExpected,
            'totalInterestExpected'  => $totalInterestExpected,
            'totalPenaltyCumulative' => $totalPenaltyCumulative,
            'totalDueCumulative'     => $totalDueCumulative,
            'totalPrincipalPaid'     => $totalPrincipalPaid,
            'totalInterestPaid'      => $totalInterestPaid,
            'totalPenaltyPaid'       => $totalPenaltyPaid,
            'totalPaid'              => $totalPaid,
            'totalRemaining'         => $totalRemaining,
        ];

        $pdf = Pdf::loadView('pdf.credit-situation-export', $data);
        return $pdf->stream("situation_credit_{$credit->id}_{$member->code}.pdf");
    }

    public function simulation()
    {
        return view('simulation');
    }

}
