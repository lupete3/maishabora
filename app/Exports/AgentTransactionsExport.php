<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Carbon\Carbon;

class AgentTransactionsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    /**
     * Utiliser FromQuery permet à Excel de "chunker" les résultats 
     * (traiter par paquets) pour ne pas exploser la mémoire.
     */
    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Heure',
            'Type',
            'Devise',
            'Montant',
            'Solde Après',
            'Description'
        ];
    }

    /**
     * Formater chaque ligne avant l'écriture dans le fichier Excel
     */
    public function map($t): array
    {
        // On sécurise la date car si la requête utilise toBase(), created_at est une string
        $date = $t->created_at instanceof Carbon ? $t->created_at : Carbon::parse($t->created_at);

        return [
            $date->format('d/m/Y'),
            $date->format('H:i'),
            ucfirst($t->type),
            $t->currency,
            $t->amount,
            $t->balance_after,
            $t->description,
        ];
    }
}