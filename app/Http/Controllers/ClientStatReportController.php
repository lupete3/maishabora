<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MembershipCard;
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

    public function compteClientsPdf(Request $request)
    {
        $search = $request->query('search', '');
        $accountType = $request->query('accountType', 'all');
        $currencyFilter = $request->query('currencyFilter', 'all');
        $minBalance = $request->query('minBalance', 0);
        $alphabetRange = $request->query('alphabetRange', 'all');

        $query = User::where('role', 'membre')->with('accounts')->orderBy('name', 'asc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('postnom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($alphabetRange !== 'all') {
            [$start, $end] = explode('-', $alphabetRange);
            $query->where(function ($q) use ($start, $end) {
                $q->whereRaw("LEFT(name, 1) BETWEEN ? AND ?", [$start, $end]);
            });
        }

        // 🎯 Apply account and balance filters in SQL
        $query->whereHas('accounts', function ($q) use ($accountType, $currencyFilter, $minBalance) {
            if ($accountType !== 'all') {
                $q->where('type', $accountType);
            }
            if ($currencyFilter !== 'all') {
                $q->where('currency', $currencyFilter);
            }
            if ($minBalance > 0) {
                $q->where('balance', '>=', $minBalance);
            }
        });

        $allMembers = $query->get();

        $balances = $allMembers->map(function ($member) {
            $current_usd = 0;
            $current_cdf = 0;
            $savings_usd = 0;
            $savings_cdf = 0;

            foreach ($member->accounts as $account) {
                if ($account->type === 'current') {
                    if ($account->currency === 'USD')
                        $current_usd += $account->balance;
                    if ($account->currency === 'CDF')
                        $current_cdf += $account->balance;
                } elseif ($account->type === 'savings') {
                    if ($account->currency === 'USD')
                        $savings_usd += $account->balance;
                    if ($account->currency === 'CDF')
                        $savings_cdf += $account->balance;
                }
            }

            return [
                'member' => $member,
                'current_usd' => $current_usd,
                'current_cdf' => $current_cdf,
                'savings_usd' => $savings_usd,
                'savings_cdf' => $savings_cdf,
            ];
        });

        $globalCurrentUsd = $balances->sum('current_usd');
        $globalCurrentCdf = $balances->sum('current_cdf');
        $globalSavingsUsd = $balances->sum('savings_usd');
        $globalSavingsCdf = $balances->sum('savings_cdf');

        $pdf = Pdf::loadView('pdf.rapport-comptes-membres', [
            'balances' => $balances,
            'globalCurrentUsd' => $globalCurrentUsd,
            'globalCurrentCdf' => $globalCurrentCdf,
            'globalSavingsUsd' => $globalSavingsUsd,
            'globalSavingsCdf' => $globalSavingsCdf,
            'accountType' => $accountType,
            'currencyFilter' => $currencyFilter,
            'minBalance' => $minBalance,
            'alphabetRange' => $alphabetRange,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('rapport_comptes_membres.pdf');
    }

    public function compteClientsExcel(Request $request)
    {
        $search = $request->query('search', '');
        $accountType = $request->query('accountType', 'all');
        $currencyFilter = $request->query('currencyFilter', 'all');
        $minBalance = $request->query('minBalance', 0);
        $alphabetRange = $request->query('alphabetRange', 'all');

        $query = User::where('role', 'membre')->orderBy('name', 'asc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('postnom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($alphabetRange !== 'all') {
            [$start, $end] = explode('-', $alphabetRange);
            $query->where(function ($q) use ($start, $end) {
                $q->whereRaw("LEFT(name, 1) BETWEEN ? AND ?", [$start, $end]);
            });
        }

        // Apply filters in SQL
        $query->whereHas('accounts', function ($q) use ($accountType, $currencyFilter, $minBalance) {
            if ($accountType !== 'all') {
                $q->where('type', $accountType);
            }
            if ($currencyFilter !== 'all') {
                $q->where('currency', $currencyFilter);
            }
            if ($minBalance > 0) {
                $q->where('balance', '>=', $minBalance);
            }
        });

        $fileName = 'rapport_comptes_membres_' . now()->format('Ymd_His') . '.csv';

        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function() use ($query, $accountType, $currencyFilter) {
            $handle = fopen('php://output', 'w');
            
            // Excel separator instruction
            fwrite($handle, "sep=;\n");
            
            // Write column headers in Windows-1252 for Excel compatibility
            $headers = [
                'Code Membre',
                'Nom',
                'Postnom',
                'Prenom',
                'Solde Courant USD',
                'Solde Courant CDF',
                'Solde Epargne USD',
                'Solde Epargne CDF'
            ];

            $headers = array_map(function($val) {
                return mb_convert_encoding($val, 'Windows-1252', 'UTF-8');
            }, $headers);
            
            fputcsv($handle, $headers, ';');

            // Chunk process members to keep RAM near 0
            $query->with('accounts')->chunk(200, function($members) use ($handle, $accountType, $currencyFilter) {
                foreach ($members as $member) {
                    $current_usd = 0;
                    $current_cdf = 0;
                    $savings_usd = 0;
                    $savings_cdf = 0;

                    foreach ($member->accounts as $account) {
                        if ($accountType !== 'all' && $account->type !== $accountType) {
                            continue;
                        }
                        if ($currencyFilter !== 'all' && $account->currency !== $currencyFilter) {
                            continue;
                        }

                        if ($account->type === 'current') {
                            if ($account->currency === 'USD') {
                                $current_usd += $account->balance;
                            } elseif ($account->currency === 'CDF') {
                                $current_cdf += $account->balance;
                            }
                        } elseif ($account->type === 'savings') {
                            if ($account->currency === 'USD') {
                                $savings_usd += $account->balance;
                            } elseif ($account->currency === 'CDF') {
                                $savings_cdf += $account->balance;
                            }
                        }
                    }

                    $row = [
                        $member->code,
                        $member->name,
                        $member->postnom,
                        $member->prenom,
                        number_format($current_usd, 2, ',', ''),
                        number_format($current_cdf, 2, ',', ''),
                        number_format($savings_usd, 2, ',', ''),
                        number_format($savings_cdf, 2, ',', '')
                    ];

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
