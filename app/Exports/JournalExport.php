<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JournalExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    protected $journals;

    public function __construct($journals)
    {
        $this->journals = $journals;
    }

    public function collection()
    {
        return $this->journals;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Référence',
            'Libellé',
            'Type Journal',
            'Compte',
            'Débit',
            'Crédit',
            'Devise',
            'Utilisateur',
        ];
    }

    public function map($journal): array
    {
        return [
            $journal->date_operation,
            $journal->reference,
            $journal->libelle,
            $journal->journalType->libelle ?? '-',
            ($journal->account->code ?? '') . ' - ' . ($journal->account->intitule ?? ''),
            number_format($journal->montant_debit, 2, '.', ''),
            number_format($journal->montant_credit, 2, '.', ''),
            $journal->devise,
            $journal->user->name ?? '-',
        ];
    }

    public function title(): string
    {
        return 'Journaux Comptables';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
