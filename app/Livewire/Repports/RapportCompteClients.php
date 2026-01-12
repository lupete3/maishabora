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
        // 🔍 Base query
        $query = User::with('accounts')
            ->where('role', 'membre')
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('postnom', 'like', "%{$this->search}%")
                    ->orWhere('prenom', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%");
            });

        if ($this->alphabetRange !== 'all') {
            [$start, $end] = explode('-', $this->alphabetRange);
            $query->where(function ($q) use ($start, $end) {
                $q->whereRaw("LEFT(name, 1) BETWEEN ? AND ?", [$start, $end]);
            });
        }

        // 🎯 Apply account and balance filters in SQL for correct pagination
        $query->whereHas('accounts', function ($q) {
            if ($this->accountType !== 'all') {
                $q->where('type', $this->accountType);
            }
            if ($this->currencyFilter !== 'all') {
                $q->where('currency', $this->currencyFilter);
            }
            if ($this->minBalance > 0) {
                $q->where('balance', '>=', $this->minBalance);
            }
        });

        $members = $query->paginate($this->perPage);

        // Soldes par membre (applique le filtre devise + tri solde)
        $balances = $members->getCollection()->map(function ($member) {
            $usd = 0;
            $cdf = 0;

            foreach ($member->accounts as $account) {
                if ($this->accountType !== 'all' && $account->type !== $this->accountType) {
                    continue;
                }

                if ($this->currencyFilter !== 'all' && $account->currency !== $this->currencyFilter) {
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

        // Repaginate manually if balance filtering is applied on the page
        // Note: For large datasets, filtering by balance should ideally be done in SQL, 
        // but since balances are in a related table, we have a challenge without complex joins/subqueries.
        // For now, we filter in memory for the *paginated* results.

        // Totaux globaux basés sur les mêmes filtres
        $globalUsd = 0;
        $globalCdf = 0;

        $totalQuery = User::where('role', 'membre')
            ->whereHas('accounts', function ($q) {
                if ($this->accountType !== 'all') {
                    $q->where('type', $this->accountType);
                }
                if ($this->currencyFilter !== 'all') {
                    $q->where('currency', $this->currencyFilter);
                }
                if ($this->minBalance > 0) {
                    $q->where('balance', '>=', $this->minBalance);
                }
            });

        $totalQuery->with('accounts')->chunk(200, function ($chunk) use (&$globalUsd, &$globalCdf) {
            foreach ($chunk as $member) {
                foreach ($member->accounts as $account) {
                    if ($this->accountType !== 'all' && $account->type !== $this->accountType) {
                        continue;
                    }
                    if ($this->currencyFilter !== 'all' && $account->currency !== $this->currencyFilter) {
                        continue;
                    }

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
