<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class MemberTransactionReportController extends Controller
{
    public function generate(Request $request, $memberId)
    {
        $member = User::findOrFail($memberId);
        $accountIds = $member->accounts->pluck('id')->toArray();

        // Récupérer les paramètres de filtre depuis la requête
        $dateFilter = $request->get('filter', '30_days');
        $customDateFrom = $request->get('date_from');
        $customDateTo = $request->get('date_to');

        // Calculer la plage de dates selon le filtre
        [$dateFrom, $dateTo] = $this->getDateRange($dateFilter, $customDateFrom, $customDateTo);

        $transactions = Transaction::whereIn('account_id', $accountIds)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->orderBy('created_at', 'DESC')
            ->get();

        // Récupérer les soldes actuels par devise
        $balances = Account::where('user_id', $member->id)
            ->whereIn('currency', ['USD', 'CDF'])
            ->pluck('balance', 'currency')
            ->toArray();

        $pdf = Pdf::loadView('pdf.member-transactions-report', compact('member', 'transactions', 'balances', 'dateFrom', 'dateTo'));

        return $pdf->stream("rapport_transactions_{$member->id}_" . now()->format('Ymd_His') . ".pdf");
    }

    public function print($id)
    {
        $member = User::findOrFail($id);

        return view('livewire.members.fiche-member', compact('member'));
    }

    /**
     * Calcule la plage de dates selon le filtre
     * @param string $filter Type de filtre ('30_days', '3_months', 'custom')
     * @param string|null $customFrom Date personnalisée de début
     * @param string|null $customTo Date personnalisée de fin
     * @return array [dateFrom, dateTo]
     */
    private function getDateRange($filter, $customFrom = null, $customTo = null)
    {
        $now = now();

        switch ($filter) {
            case '30_days':
                return [
                    $now->copy()->subDays(30)->startOfDay(),
                    $now->copy()->endOfDay()
                ];

            case '3_months':
                return [
                    $now->copy()->subMonths(3)->startOfDay(),
                    $now->copy()->endOfDay()
                ];

            case 'custom':
                if (!$customFrom || !$customTo) {
                    // Fallback sur 30 jours si dates invalides
                    return [
                        $now->copy()->subDays(30)->startOfDay(),
                        $now->copy()->endOfDay()
                    ];
                }

                try {
                    $dateFrom = \Carbon\Carbon::parse($customFrom)->startOfDay();
                    $dateTo = \Carbon\Carbon::parse($customTo)->endOfDay();

                    // Vérifier que date_from <= date_to
                    if ($dateFrom->gt($dateTo)) {
                        return [
                            $now->copy()->subDays(30)->startOfDay(),
                            $now->copy()->endOfDay()
                        ];
                    }

                    return [$dateFrom, $dateTo];
                } catch (\Exception $e) {
                    return [
                        $now->copy()->subDays(30)->startOfDay(),
                        $now->copy()->endOfDay()
                    ];
                }

            default:
                return [
                    $now->copy()->subDays(30)->startOfDay(),
                    $now->copy()->endOfDay()
                ];
        }
    }
}
