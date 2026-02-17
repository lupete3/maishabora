<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class CurrencyConversionExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Effectué par',
            'Type',
            'Montant Sortie',
            'Devise Sortie',
            'Montant Entrée',
            'Devise Entrée',
            'Taux',
            'Description'
        ];
    }

    public function map($t): array
    {
        $date = $t->created_at instanceof Carbon ? $t->created_at : Carbon::parse($t->created_at);

        // Find the paired entry for more complete data in Excel
        $entree = \App\Models\Transaction::where('type', 'like', 'conversion_entree%')
            ->where('user_id', $t->user_id)
            ->where('created_at', '>=', $t->created_at)
            ->orderBy('created_at')
            ->first();

        return [
            $date->format('d/m/Y H:i'),
            $t->user->name ?? 'N/A',
            str_contains($t->type, 'client') ? 'Client' : 'Caisse',
            $t->amount,
            $t->currency,
            $entree ? $entree->amount : '-',
            $entree ? $entree->currency : '-',
            $t->exchange_rate ?? '-',
            $t->description,
        ];
    }
}
