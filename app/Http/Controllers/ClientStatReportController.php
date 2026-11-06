<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MembershipCard;
use App\Support\ClientBalanceRanking;
use Barryvdh\DomPDF\Facade\Pdf;

class ClientStatReportController extends Controller
{
    public function rapportClient()
    {
        return view('rapports.clients');
    }
    public function rapportCarnets()
    {
        return view('rapports.carnets');
    }

    /**
     * Membres filtrés et classés comme sur la page /comptes-membres.
     * @return array{0: \Illuminate\Database\Eloquent\Builder, 1: array, 2: array} requête classée,
     *         paramètres, totaux (calculés en SQL sur tous les membres filtrés)
     */
    private function compteClientsQuery(Request $request): array
    {
        $params = [
            'search'         => $request->query('search', ''),
            'accountType'    => $request->query('accountType', 'all'),
            'currencyFilter' => $request->query('currencyFilter', 'all'),
            'minBalance'     => $request->query('minBalance', 0),
            'alphabetRange'  => $request->query('alphabetRange', 'all'),
            'sortBy'         => ClientBalanceRanking::normalize($request->query('sortBy', 'name')),
            'rate'           => ClientBalanceRanking::usdToCdfRate(),
        ];

        $accountFilter = ClientBalanceRanking::accountFilter($params['accountType'], $params['currencyFilter'], $params['minBalance']);

        $query = User::where('role', 'membre')
            ->whereHas('accounts', $accountFilter);

        if ($params['search']) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('postnom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($params['alphabetRange'] !== 'all') {
            [$start, $end] = explode('-', $params['alphabetRange']);
            $query->whereRaw("LEFT(name, 1) BETWEEN ? AND ?", [$start, $end]);
        }

        $totals = ClientBalanceRanking::totals($query, $accountFilter, $params['rate']);
        $params['count'] = (clone $query)->count();

        $query->with(['accounts' => $accountFilter]);
        ClientBalanceRanking::apply($query, $params['sortBy'], $accountFilter, $params['rate']);

        return [$query, $params, $totals];
    }

    /** Soldes d'un membre ventilés par type de compte et devise. */
    private function memberBalances(User $member): array
    {
        $row = ['member' => $member, 'current_usd' => 0, 'current_cdf' => 0, 'savings_usd' => 0, 'savings_cdf' => 0];

        foreach ($member->accounts as $account) {
            $type = in_array($account->type, ['current', 'savings'], true) ? $account->type : null;
            $currency = in_array($account->currency, ['USD', 'CDF'], true) ? strtolower($account->currency) : null;
            if ($type && $currency) {
                $row["{$type}_{$currency}"] += $account->balance;
            }
        }

        return $row;
    }

    /** Montants USD / CDF d'une ligne de soldes (même base que le tri). */
    private function amounts(array $row): array
    {
        return ClientBalanceRanking::amountsOf($row['member']);
    }

    /**
     * PDF par lot (50 / 100 / 200 membres) : un PDF de tous les membres dépasse les
     * ressources du serveur. Le rang reste global et les totaux portent sur tous les
     * membres filtrés.
     */
    public function compteClientsPdf(Request $request)
    {
        @set_time_limit(120);

        [$query, $params, $totals] = $this->compteClientsQuery($request);

        $lotSize = ClientBalanceRanking::normalizeLotSize($request->query('lotSize'));
        $lotCount = max(1, (int) ceil($params['count'] / $lotSize));
        $lot = min($lotCount, max(1, (int) $request->query('lot', 1)));
        $offset = ($lot - 1) * $lotSize;

        $balances = $query->skip($offset)->take($lotSize)->get()
            ->values()
            ->map(function ($member, $index) use ($params, $totals, $offset) {
                $row = $this->memberBalances($member);
                $amounts = $this->amounts($row);
                $row['rank'] = $offset + $index + 1;
                $row['total_usd_equiv'] = ClientBalanceRanking::value('total', $amounts, $params['rate']);
                $row['share'] = ClientBalanceRanking::share($params['sortBy'], $amounts, $totals['positive'], $params['rate']);

                return $row;
            });

        $pdf = Pdf::loadView('pdf.rapport-comptes-membres', [
            'balances' => $balances,
            'lot' => [
                'number' => $lot,
                'count'  => $lotCount,
                'from'   => $balances->isEmpty() ? 0 : $offset + 1,
                'to'     => $offset + $balances->count(),
                'total'  => $params['count'],
            ],
            'globalCurrentUsd' => $totals['current_usd'],
            'globalCurrentCdf' => $totals['current_cdf'],
            'globalSavingsUsd' => $totals['savings_usd'],
            'globalSavingsCdf' => $totals['savings_cdf'],
            'accountType' => $params['accountType'],
            'currencyFilter' => $params['currencyFilter'],
            'minBalance' => $params['minBalance'],
            'alphabetRange' => $params['alphabetRange'],
            'sortBy' => $params['sortBy'],
            'sortLabel' => ClientBalanceRanking::SORTS[$params['sortBy']],
            'isBalanceSort' => ClientBalanceRanking::isBalanceSort($params['sortBy']),
            'rate' => $params['rate'],
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("rapport_comptes_membres_lot{$lot}_sur_{$lotCount}.pdf");
    }

    public function compteClientsExcel(Request $request)
    {
        @set_time_limit(300);

        [$query, $params, $allTotals] = $this->compteClientsQuery($request);
        $isBalanceSort = ClientBalanceRanking::isBalanceSort($params['sortBy']);
        $withTotal = $params['sortBy'] === 'total';

        // Totaux positifs (calculés en SQL) pour la part de l'encours
        $totals = $allTotals['positive'];

        $fileName = 'rapport_comptes_membres_' . now()->format('Ymd_His') . '.csv';

        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($query, $params, $isBalanceSort, $withTotal, $totals) {
            $handle = fopen('php://output', 'w');

            // Excel separator instruction
            fwrite($handle, "sep=;\n");

            // Write column headers in Windows-1252 for Excel compatibility
            $headers = array_merge(
                $isBalanceSort ? ['Rang'] : [],
                [
                    'Code Membre',
                    'Nom',
                    'Postnom',
                    'Prenom',
                    'Solde Courant USD',
                    'Solde Courant CDF',
                    'Solde Epargne USD',
                    'Solde Epargne CDF',
                ],
                $withTotal ? ['Total equiv. USD'] : [],
                $isBalanceSort ? ["Part de l'encours (%)"] : []
            );

            $headers = array_map(function ($val) {
                return mb_convert_encoding($val, 'Windows-1252', 'UTF-8');
            }, $headers);

            fputcsv($handle, $headers, ';');

            $rank = 0;

            // Chunk process members to keep RAM near 0
            $query->chunk(200, function ($members) use ($handle, $params, $isBalanceSort, $withTotal, $totals, &$rank) {
                foreach ($members as $member) {
                    $balances = $this->memberBalances($member);
                    $amounts = $this->amounts($balances);
                    $rank++;

                    $row = array_merge(
                        $isBalanceSort ? [$rank] : [],
                        [
                            $member->code,
                            $member->name,
                            $member->postnom,
                            $member->prenom,
                            number_format($balances['current_usd'], 2, ',', ''),
                            number_format($balances['current_cdf'], 2, ',', ''),
                            number_format($balances['savings_usd'], 2, ',', ''),
                            number_format($balances['savings_cdf'], 2, ',', ''),
                        ],
                        $withTotal
                            ? [number_format((float) ClientBalanceRanking::value('total', $amounts, $params['rate']), 2, ',', '')]
                            : [],
                        $isBalanceSort
                            ? [($share = ClientBalanceRanking::share($params['sortBy'], $amounts, $totals, $params['rate'])) !== null ? number_format($share, 2, ',', '') : '']
                            : []
                    );

                    // Convert each element to Windows-1252 to ensure French accents display correctly in Excel
                    $row = array_map(function($val) {
                        return mb_convert_encoding($val ?? '', 'Windows-1252', 'UTF-8');
                    }, $row);

                    fputcsv($handle, $row, ';');
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=Windows-1252',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);

        return $response;
    }

    public function carnetOverviewPdf(Request $request)
    {
        $search = $request->query('search', null);
        $anomalies = MembershipCard::getAnomalies($search);

        // Calcul des totaux identiques à la vue Livewire
        $totalSavedUSD = $anomalies->where('currency', 'USD')->sum(function ($c) {
            $total = $c->contributions->sum('amount');
            $first = $c->contributions->sortBy('created_at')->first();
            return $first ? $total - $first->amount : $total;
        });
        $totalSavedCDF = $anomalies->where('currency', 'CDF')->sum(function ($c) {
            $total = $c->contributions->sum('amount');
            $first = $c->contributions->sortBy('created_at')->first();
            return $first ? $total - $first->amount : $total;
        });

        $totalBalanceUSD = $anomalies->where('currency', 'USD')->sum(function ($c) {
            $acc = $c->member->accounts->where('currency', 'USD')->where('type', 'savings')->first()
                ?? $c->member->accounts->where('currency', 'USD')->where('type', 'current')->first();
            return $acc ? $acc->balance : 0;
        });
        $totalBalanceCDF = $anomalies->where('currency', 'CDF')->sum(function ($c) {
            $acc = $c->member->accounts->where('currency', 'CDF')->where('type', 'savings')->first()
                ?? $c->member->accounts->where('currency', 'CDF')->where('type', 'current')->first();
            return $acc ? $acc->balance : 0;
        });

        $pdf = Pdf::loadView('pdf.carnet-overview', [
            'anomalies' => $anomalies,
            'search' => $search,
            'totalCount' => $anomalies->count(),
            'totalSavedUSD' => $totalSavedUSD,
            'totalSavedCDF' => $totalSavedCDF,
            'totalBalanceUSD' => $totalBalanceUSD,
            'totalBalanceCDF' => $totalBalanceCDF,
            'ecartUSD' => $totalSavedUSD - $totalBalanceUSD,
            'ecartCDF' => $totalSavedCDF - $totalBalanceCDF,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('rapport_anomalies_carnets.pdf');
    }
}
