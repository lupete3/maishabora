<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class CreditReportPdfController extends Controller
{
    public function export()
    {
        $credits = Credit::where('is_paid', false)
            ->with(['user', 'repayments'])
            ->whereHas('user', fn($q) => $q->where('role', 'membre'))
            ->get()
            ->filter(function ($credit) {
                return $credit->repayments->contains(function ($r) {
                    return !$r->is_paid && Carbon::parse($r->due_date)->lt(now());
                });
            })
            ->values();

        // Calculer les détails pour chaque crédit
        $details = [];
        $totaux = [
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

        foreach ($credits as $credit) {
            $paid = $credit->repayments->where('is_paid', true);
            $unpaid = $credit->repayments->where('is_paid', false);
            $totalPaid = $paid->sum('paid_amount');
            $totalPenalty = $unpaid->sum('penalty');
            $remaining = $credit->amount - $totalPaid;

            $maxDaysLate = $unpaid->filter(fn($r) => Carbon::parse($r->due_date)->lt(now()))
                ->max(fn($r) => Carbon::parse($r->due_date)->diffInDays(now())) ?? 0;

            // $maxLate = number_format((float) $maxDaysLate, 0) ?? 0;
            $maxLate = round( $maxDaysLate, 0) ?? 0;

            $ranges = [
                'range_1' => 0, 'range_2' => 0, 'range_3' => 0,
                'range_4' => 0, 'range_5' => 0, 'range_6' => 0, 'range_7' => 0,
            ];

            if ($maxLate >= 0 && $maxLate <= 30) $ranges['range_1'] = $remaining;
            elseif ($maxLate <= 60) $ranges['range_2'] = $remaining;
            elseif ($maxLate <= 90) $ranges['range_3'] = $remaining;
            elseif ($maxLate <= 180) $ranges['range_4'] = $remaining;
            elseif ($maxLate <= 360) $ranges['range_5'] = $remaining;
            elseif ($maxLate <= 720) $ranges['range_6'] = $remaining;
            elseif ($maxLate > 720) $ranges['range_7'] = $remaining;

            $totaux['credit_amount'] += $credit->amount;
            $totaux['remaining_balance'] += $remaining;
            $totaux['total_penalty'] += $totalPenalty;

            foreach ($ranges as $k => $v) {
                $totaux[$k] += $v;
            }

            $details[] = [
                'id' => $credit->id,
                'member' => $credit->user->name . ' ' . $credit->user->postnom,
                'date' => Carbon::parse($credit->start_date)->format('d/m/Y'),
                'amount' => $credit->amount,
                'remaining' => $remaining,
                'penalty' => $totalPenalty,
                'penalty_percent' => $remaining > 0 ? round(($totalPenalty / $remaining) * 100, 2) : 0,
                'days_late' => $maxLate,
                ...$ranges,
            ];
        }

        $pdf = PDF::loadView('pdf.credit-en-retard', compact('details', 'totaux'))->setPaper('a4', 'landscape');
        return $pdf->download('rapport_credits_retard.pdf');
    }
}
