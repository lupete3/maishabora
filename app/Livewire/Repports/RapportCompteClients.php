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
    public $accountType = 'all';    // all, current, savings
    public $minBalance = 0;         // Filtrer par solde minimum
    public $alphabetRange = 'all';  // all, A-D, E-H, etc.
    public $sortByBalance = false;  // true = classer par solde le plus élevé

    public function updatingSearch()
    {
        $this->resetPage();
    }


    public function render()
    {
        // Filtre commun des comptes
        $accountFilter = function ($q) {
            if ($this->accountType !== 'all') {
                $q->where('type', $this->accountType);
            }

            if ($this->currencyFilter !== 'all') {
                $q->where('currency', $this->currencyFilter);
            }

            if ($this->minBalance > 0) {
                $q->where('balance', '>=', $this->minBalance);
            }
        };

        // Requête principale
        $query = User::where('role', 'membre')
            ->with(['accounts' => $accountFilter])
            ->whereHas('accounts', $accountFilter)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('postnom', 'like', "%{$this->search}%")
                    ->orWhere('prenom', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%");
            });

        // Filtre alphabétique
        if ($this->alphabetRange !== 'all') {
            [$start, $end] = explode('-', $this->alphabetRange);

            $query->where(function ($q) use ($start, $end) {
                $q->whereRaw("LEFT(name, 1) BETWEEN ? AND ?", [$start, $end]);
            });
        }

        // Pagination
        $members = $query->paginate($this->perPage);

        // Calcul des soldes affichés
        $balances = $members->getCollection()->map(function ($member) {

            $current_usd = 0;
            $current_cdf = 0;
            $savings_usd = 0;
            $savings_cdf = 0;

            foreach ($member->accounts as $account) {

                if ($account->type === 'current') {

                    if ($account->currency === 'USD') {
                        $current_usd += $account->balance;
                    }

                    if ($account->currency === 'CDF') {
                        $current_cdf += $account->balance;
                    }

                } elseif ($account->type === 'savings') {

                    if ($account->currency === 'USD') {
                        $savings_usd += $account->balance;
                    }

                    if ($account->currency === 'CDF') {
                        $savings_cdf += $account->balance;
                    }
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

        // Totaux globaux
        $globalCurrentUsd = 0;
        $globalCurrentCdf = 0;
        $globalSavingsUsd = 0;
        $globalSavingsCdf = 0;

        $totalQuery = User::where('role', 'membre')
            ->with(['accounts' => $accountFilter])
            ->whereHas('accounts', $accountFilter)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('postnom', 'like', "%{$this->search}%")
                    ->orWhere('prenom', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%");
            });

        if ($this->alphabetRange !== 'all') {
            [$start, $end] = explode('-', $this->alphabetRange);

            $totalQuery->where(function ($q) use ($start, $end) {
                $q->whereRaw("LEFT(name, 1) BETWEEN ? AND ?", [$start, $end]);
            });
        }

        $totalQuery->chunk(200, function ($chunk) use (
            &$globalCurrentUsd,
            &$globalCurrentCdf,
            &$globalSavingsUsd,
            &$globalSavingsCdf
        ) {

            foreach ($chunk as $member) {

                foreach ($member->accounts as $account) {

                    if ($account->type === 'current') {

                        if ($account->currency === 'USD') {
                            $globalCurrentUsd += $account->balance;
                        }

                        if ($account->currency === 'CDF') {
                            $globalCurrentCdf += $account->balance;
                        }

                    } elseif ($account->type === 'savings') {

                        if ($account->currency === 'USD') {
                            $globalSavingsUsd += $account->balance;
                        }

                        if ($account->currency === 'CDF') {
                            $globalSavingsCdf += $account->balance;
                        }
                    }
                }
            }
        });

        return view('livewire.repports.rapport-compte-clients', [
            'balances' => $balances,
            'members' => $members,
            'globalCurrentUsd' => $globalCurrentUsd,
            'globalCurrentCdf' => $globalCurrentCdf,
            'globalSavingsUsd' => $globalSavingsUsd,
            'globalSavingsCdf' => $globalSavingsCdf,
        ]);
    }

}
