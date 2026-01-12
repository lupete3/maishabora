<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
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

        $balances = $allMembers->map(function ($member) use ($accountType, $currencyFilter) {
            $usd = 0;
            $cdf = 0;

            foreach ($member->accounts as $account) {
                if ($accountType !== 'all' && $account->type !== $accountType) {
                    continue;
                }

                if ($currencyFilter !== 'all' && $account->currency !== $currencyFilter) {
                    continue;
                }

                if ($account->currency === 'USD') {
                    $usd += $account->balance;
                } elseif ($account->currency === 'CDF') {
                    $cdf += $account->balance;
                }
            }

            return [
                'member' => $member,
                'usd_balance' => $usd,
                'cdf_balance' => $cdf,
            ];
        });

        $globalUsd = $balances->sum('usd_balance');
        $globalCdf = $balances->sum('cdf_balance');

        $pdf = Pdf::loadView('pdf.rapport-comptes-membres', [
            'balances' => $balances,
            'globalUsd' => $globalUsd,
            'globalCdf' => $globalCdf,
            'accountType' => $accountType,
            'currencyFilter' => $currencyFilter,
            'minBalance' => $minBalance,
            'alphabetRange' => $alphabetRange,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('rapport_comptes_membres.pdf');
    }
}
