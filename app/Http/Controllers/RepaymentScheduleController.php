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

    // public function generate($creditId)
    // {
    //     // Récupération du crédit
    //     $credit = Credit::with(['user', 'repayments'])->findOrFail($creditId);
    //     $member = $credit->user;
    //     $agent = Auth::user();

    //     // Tri des échéances
    //     $repayments = $credit->repayments->sortBy('due_date');

    //     $detailedRepayments = [];
    //     $remainingCapital = $credit->amount;

    //     $countRepayments = $repayments->count();
    //     $index = 0;

    //     $totalCapital = 0;
    //     $totalInterest = 0;
    //     $totalPenalty = 0;
    //     $totalDue = 0;

    //     // Capital remboursé à chaque échéance
    //     $capitalPart = round($credit->amount / $credit->installments, 2);
    //     // Intérêt constant par échéance
    //     $interestPart = round($credit->amount * ($credit->interest_rate / 100), 2);
    //     // Mensualité de base (Capital + Intérêt)
    //     $mensualite = $capitalPart + $interestPart;

    //     foreach ($repayments as $repayment) {
    //         $index++;

    //         // Dernière ligne
    //         if ($index == $countRepayments) {
    //             $capitalRepaid = round($remainingCapital, 2);
    //             $due = $capitalRepaid + $interestPart + ($repayment->penalty ?? 0);
    //         } else {
    //             $capitalRepaid = $capitalPart;
    //             $due = $mensualite + ($repayment->penalty ?? 0);
    //         }

    //         $penalty = $repayment->penalty ?? 0;

    //         $detailedRepayments[] = [
    //             'repayment_id'      => $repayment->id,
    //             'due_date'           => $repayment->due_date,
    //             'opening_capital'    => $remainingCapital,
    //             'capital_repaid'     => $capitalRepaid,
    //             'interest'           => $interestPart,
    //             'penalty'            => $penalty,
    //             'due'                => $due,
    //             'remaining_capital'  => round($remainingCapital - $capitalRepaid, 2),
    //         ];

    //         $totalCapital += $capitalRepaid;
    //         $totalInterest += $interestPart;
    //         $totalPenalty += $penalty;
    //         $totalDue += $due;

    //         $remainingCapital = round($remainingCapital - $capitalRepaid, 2);
    //     }

    //     $data = [
    //         'credit'        => $credit,
    //         'member'        => $member,
    //         'agent'         => $agent,
    //         'repayments'    => $detailedRepayments,
    //         'totalCapital'  => $totalCapital,
    //         'totalInterest' => $totalInterest,
    //         'totalPenalty'  => $totalPenalty,
    //         'totalDue'      => $totalDue,
    //     ];

    //     $pdf = Pdf::loadView('pdf.repayment-schedule', $data);
    //     return $pdf->stream("plan_rem_{$creditId}.pdf");
    // }

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

    public function simulation()
    {
        return view('simulation');
    }


    // public function generate($creditId)
    // {
    //     // Récupérer le crédit et les données associées
    //     $credit = Credit::with(['user', 'repayments'])->findOrFail($creditId);
    //     $member = $credit->user;
    //     $agent = Auth::user(); // ou récupère depuis un champ si nécessaire
    //     $repayments = $credit->repayments->sortBy('due_date');

    //     // Données à passer à la vue
    //     $data = compact('credit', 'member', 'agent', 'repayments');

    //     // Générer le PDF
    //     $pdf = Pdf::loadView('pdf.repayment-schedule', $data);

    //     return $pdf->stream("plan_rem_{$creditId}.pdf");
    // }

}
