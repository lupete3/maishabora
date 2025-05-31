<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Facades\Auth;
use App\Models\CashRegister;

class MemberFinancialHistoryExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function collection()
    {
        $userId = Auth::id();

        return CashRegister::where(function ($query) use ($userId) {
                $query->where('reference_type', 'App\Models\MembershipCard')
                      ->whereHasMorph('reference', 'App\Models\MembershipCard', fn($q) => $q->where('user_id', $userId));
            })
            ->orWhere(function ($query) use ($userId) {
                $query->where('reference_type', 'App\Models\ContributionLine')
                      ->whereHasMorph('reference', 'App\Models\ContributionLine', fn($q) => $q->whereHas('contributionBook.subscription', fn($q2) => $q2->where('user_id', $userId)));
            })
            ->orWhere(function ($query) use ($userId) {
                $query->where('reference_type', 'App\Models\ContributionBook')
                      ->whereHasMorph('reference', 'App\Models\ContributionBook', fn($q) => $q->whereHas('subscription', fn($q2) => $q2->where('user_id', $userId)));
            })
            ->latest()
            ->get()
            ->map(function ($operation) {
                $entree = in_array($operation->type_operation, ['Adhésion', 'Contribution']) ? $operation->montant : 0;
                $sortie = in_array($operation->type_operation, ['Retrait', 'Terminé']) ? $operation->montant : 0;

                return [
                    'Date' => $operation->created_at->format('d/m/Y H:i'),
                    'Type' => $operation->type_operation,
                    'Description' => match ($operation->reference_type) {
                        'App\Models\MembershipCard' => 'Achat du carnet ' . optional($operation->reference)->code,
                        'App\Models\ContributionLine' => 'Dépôt quotidien - Carnet ' . optional(optional($operation->reference)->contributionBook)->code,
                        'App\Models\ContributionBook' => 'Retrait du carnet ' . optional($operation->reference)->code,
                        default => 'Opération inconnue',
                    },
                    'Entrée (FC)' => number_format($entree, 0, ',', '.'),
                    'Sortie (FC)' => number_format($sortie, 0, ',', '.'),
                    'Référence' => match ($operation->reference_type) {
                        'App\Models\MembershipCard' => optional($operation->reference)->code,
                        'App\Models\ContributionLine' => optional(optional($operation->reference)->contributionBook)->code . ' - Ligne ' . $operation->reference->numero_ligne,
                        'App\Models\ContributionBook' => optional($operation->reference)->code,
                        default => '-',
                    }
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Date',
            'Type',
            'Description',
            'Entrée (FC)',
            'Sortie (FC)',
            'Référence'
        ];
    }
}
