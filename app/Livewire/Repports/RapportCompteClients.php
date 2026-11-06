<?php

namespace App\Livewire\Repports;

use App\Models\User;
use App\Support\ClientBalanceRanking;
use Livewire\Component;
use Livewire\WithPagination;

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
    public $sortBy = 'name';        // name, total, usd, cdf (voir ClientBalanceRanking::SORTS)

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updated($property)
    {
        // Tout changement de filtre ou de tri repart de la page 1 (le rang reste cohérent)
        if (in_array($property, ['currencyFilter', 'accountType', 'minBalance', 'alphabetRange', 'sortBy'], true)) {
            $this->resetPage();
        }
    }

    /** Membres correspondant aux filtres (recherche, comptes, tranche alphabétique). */
    protected function baseQuery($accountFilter)
    {
        $query = User::where('role', 'membre')
            ->whereHas('accounts', $accountFilter)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('postnom', 'like', "%{$this->search}%")
                    ->orWhere('prenom', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%");
            });

        if ($this->alphabetRange !== 'all') {
            [$start, $end] = explode('-', $this->alphabetRange);
            $query->whereRaw("LEFT(name, 1) BETWEEN ? AND ?", [$start, $end]);
        }

        return $query;
    }

    public function render()
    {
        $this->sortBy = ClientBalanceRanking::normalize($this->sortBy);
        $accountFilter = ClientBalanceRanking::accountFilter($this->accountType, $this->currencyFilter, $this->minBalance);
        $rate = ClientBalanceRanking::usdToCdfRate();

        // Requête principale, classée selon le critère choisi
        $query = ClientBalanceRanking::apply(
            $this->baseQuery($accountFilter)->with(['accounts' => $accountFilter]),
            $this->sortBy,
            $accountFilter,
            $rate
        );

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
        $positiveTotals = ClientBalanceRanking::emptyPositiveTotals();

        $this->baseQuery($accountFilter)->with(['accounts' => $accountFilter])->chunk(200, function ($chunk) use (
            &$globalCurrentUsd,
            &$globalCurrentCdf,
            &$globalSavingsUsd,
            &$globalSavingsCdf,
            &$positiveTotals,
            $rate
        ) {

            foreach ($chunk as $member) {

                ClientBalanceRanking::addPositive($positiveTotals, ClientBalanceRanking::amountsOf($member), $rate);

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

        // Rang et part de l'encours créditeur, quand le classement porte sur les soldes
        $balances = $balances->values()->map(function ($row, $index) use ($members, $positiveTotals, $rate) {
            $amounts = ClientBalanceRanking::amountsOf($row['member']);
            $row['rank'] = $members->firstItem() + $index;
            $row['total_usd_equiv'] = ClientBalanceRanking::value('total', $amounts, $rate);
            $row['share'] = ClientBalanceRanking::share($this->sortBy, $amounts, $positiveTotals, $rate);

            return $row;
        });

        return view('livewire.repports.rapport-compte-clients', [
            'balances' => $balances,
            'members' => $members,
            'globalCurrentUsd' => $globalCurrentUsd,
            'globalCurrentCdf' => $globalCurrentCdf,
            'globalSavingsUsd' => $globalSavingsUsd,
            'globalSavingsCdf' => $globalSavingsCdf,
            'sorts' => ClientBalanceRanking::SORTS,
            'isBalanceSort' => ClientBalanceRanking::isBalanceSort($this->sortBy),
            'rate' => $rate,
        ]);
    }

}
