<?php

namespace App\Livewire\Repports;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Barryvdh\DomPDF\Facade\Pdf;

class RapportCompteClients extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $perPage = 10;
    public $search = '';
    public $currencyFilter = 'all'; // all, USD, CDF
    public $sortByBalance = false;  // true = classer par solde le plus élevé

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function exportPdf()
    {
        // On récupère tous les membres avec leurs soldes
        $members = User::with(['accounts'])->where('role', 'membre')->get();

        $balances = $members->map(function ($member) {
            $usd = 0;
            $cdf = 0;

            foreach ($member->accounts as $account) {
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

        // Totaux globaux
        $globalUsd = $balances->sum('usd_balance');
        $globalCdf = $balances->sum('cdf_balance');

        // Génération du PDF
        $pdf = Pdf::loadView('pdf.rapport-comptes-membres', [
            'balances' => $balances,
            'globalUsd' => $globalUsd,
            'globalCdf' => $globalCdf,
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'rapport_comptes_membres.pdf');
    }

    public function render()
    {
        // 🔍 Base query
        $query = User::with('accounts')
            ->where('role', 'membre')
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('postnom', 'like', "%{$this->search}%")
                  ->orWhere('prenom', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%");
            });

        $members = $query->paginate($this->perPage);

        // Soldes par membre (applique le filtre devise + tri solde)
        $balances = $members->map(function ($member) {
            $usd = 0;
            $cdf = 0;

            foreach ($member->accounts as $account) {
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

        // Totaux globaux
        $globalUsd = 0;
        $globalCdf = 0;

        User::with('accounts')->chunk(100, function ($chunk) use (&$globalUsd, &$globalCdf) {
            foreach ($chunk as $member) {
                foreach ($member->accounts as $account) {
                    if ($account->currency === 'USD') {
                        $globalUsd += $account->balance;
                    } elseif ($account->currency === 'CDF') {
                        $globalCdf += $account->balance;
                    }
                }
            }
        });

        return view('livewire.repports.rapport-compte-clients', [
            'balances' => $balances,
            'members' => $members,
            'globalUsd' => $globalUsd,
            'globalCdf' => $globalCdf,
        ]);
    }

}