<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Export Excel des clients à relancer (carnets retirés et non renouvelés).
 */
class CardRenewalExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithCustomValueBinder
{
    public function __construct(private Collection $clients, private string $periode)
    {
    }

    /**
     * Code client, téléphone et numéro de carnet restent du texte :
     * sinon Excel retire le « + » des téléphones et abrège les longs codes.
     */
    public function bindValue(Cell $cell, $value): bool
    {
        if (in_array($cell->getColumn(), ['A', 'C', 'E'], true) && $value !== null) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function collection(): Collection
    {
        return $this->clients;
    }

    public function title(): string
    {
        return 'Clients à relancer';
    }

    public function headings(): array
    {
        return [
            'Code client', 'Client', 'Téléphone', 'Agent collecteur', 'Carnet', 'Type', 'Devise',
            'Mise journalière', 'Mises versées', 'Retiré le', 'Jours depuis le retrait',
            'Carnets terminés', 'Autre carnet actif', 'Période analysée',
        ];
    }

    public function map($r): array
    {
        return [
            $r->client_code,
            $r->client,
            $r->telephone,
            $r->agent,
            $r->code,
            $r->card_type === 'simple' ? 'Simple' : 'Épargne',
            $r->currency,
            (float) $r->subscription_amount,
            $r->mises,
            $r->retire_le->format('d/m/Y'),
            $r->jours,
            (int) $r->carnets_termines,
            $r->autre_actif ? 'Oui' : 'Non',
            $this->periode,
        ];
    }
}
