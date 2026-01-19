<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class DepositsExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $deposits;
    protected $total;

    public function __construct($deposits, $total)
    {
        $this->deposits = $deposits;
        $this->total = $total;
    }

    public function collection()
    {
        return $this->deposits;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Code Membre',
            'Nom Complet',
            'Type',
            'Montant',
            'Devise',
            'Description',
        ];
    }

    public function map($transaction): array
    {
        return [
            \Carbon\Carbon::parse($transaction->created_at)->format('d/m/Y H:i'),
            $transaction->user->code ?? '-',
            ($transaction->user->name ?? '') . ' ' . ($transaction->user->postnom ?? '') . ' ' . ($transaction->user->prenom ?? ''),
            ucfirst($transaction->type),
            number_format($transaction->amount, 2, '.', ''),
            $transaction->currency,
            $transaction->description ?? '-',
        ];
    }

    public function title(): string
    {
        return 'Dépôts';
    }
}
