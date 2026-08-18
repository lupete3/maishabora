<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CreditsReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected Collection $credits;

    public function __construct(Collection $credits)
    {
        $this->credits = $credits;
    }

    public function collection()
    {
        return $this->credits;
    }

    public function headings(): array
    {
        return [
            'ID', 'Code membre', 'Nom membre', 'Créé le', 'Date de début', 'Date d\'échéance', 'Devise', 'Montant', 'Montant payé', 'Intérêts (est)', 'Pénalités', 'Agent', 'Statut', 'Principal restant', 'Intérêts restants', 'Pénalités restantes', 'Total restant'
        ];
    }

    public function map($credit): array
    {
        $paidAmount = (float) $credit->repayments->sum('paid_amount');
        $penalties = (float) $credit->repayments->sum('penalty');

        // Compute remaining splits
        $remPrincipal = 0.0;
        $remInterest = 0.0;
        $remPenalty = 0.0;

        foreach ($credit->repayments as $r) {
            $principalAmount = floatval($r->principal_amount ?? $r->expected_amount);
            $interestAmount = floatval($r->interest_amount ?? max(0, $r->expected_amount - ($r->principal_amount ?? 0)));

            $paidPrincipal = floatval($r->paid_principal ?? 0);
            $paidInterest = floatval($r->paid_interest ?? 0);
            $paidPenalty = floatval($r->paid_penalty ?? 0);

            $remPrincipal += max(0.0, $principalAmount - $paidPrincipal);
            $remInterest += max(0.0, $interestAmount - $paidInterest);
            $remPenalty += max(0.0, floatval($r->penalty ?? 0) - $paidPenalty);
        }

        $remainingTotal = $remPrincipal + $remInterest + $remPenalty;

        return [
            $credit->id,
            $credit->user->code ?? '',
            trim(implode(' ', array_filter([ $credit->user->name ?? '', $credit->user->postnom ?? '', $credit->user->prenom ?? '' ]))),
            $credit->created_at ? Carbon::parse($credit->created_at)->format('d-m-Y') : '',
            $credit->start_date ? Carbon::parse($credit->start_date)->format('d-m-Y') : '',
            $credit->due_date ? Carbon::parse($credit->due_date)->format('d-m-Y') : '',
            $credit->currency ?? 'USD',
            (float) $credit->amount,
            $paidAmount,
            (float) ($credit->amount * ($credit->interest_rate ?? 0) / 100),
            $penalties,
            $credit->agent?->name . ' ' . $credit->agent?->postnom,
            $credit->is_paid ? 'PAYÉ' : 'EN COURS',
            $remPrincipal,
            $remInterest,
            $remPenalty,
            $remainingTotal,
        ];
    }
}
