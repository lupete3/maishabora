<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RepaymentsReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Code',
            'Membre',
            'Montant Remboursé',
            'Pénalité',
            'Devise',
        ];
    }

    public function map($repayment): array
    {
        return [
            \Carbon\Carbon::parse($repayment->paid_at)->format('d/m/Y'),
            $repayment->credit->user->code,
            $repayment->credit->user->name. ' ' . $repayment->credit->user->postnom . ' ' . $repayment->credit->user->prenom,
            $repayment->expected_amount,
            $repayment->total_penalty,
            $repayment->credit->currency,
        ];
    }
}
