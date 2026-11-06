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

    /** Nombres de lignes proposés par page */
    public const PER_PAGE_OPTIONS = [10, 25, 50];

    public $perPage = 10;
    public $search = '';
    public $currencyFilter = 'all'; // all, USD, CDF
    public $accountType = 'all';    // all, current, savings
    public $minBalance = 0;         // Filtrer par solde minimum
    public $alphabetRange = 'all';  // all, A-D, E-H, etc.
    public $sortBy = 'name';        // name, total, usd, cdf (voir ClientBalanceRanking::SORTS)
    public $lotSize = ClientBalanceRanking::DEFAULT_LOT_SIZE; // taille d'un lot PDF

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updated($property)
    {
        // Tout changement de filtre ou de tri repart de la page 1 (le rang reste cohérent)
        if (in_array($property, ['currencyFilter', 'accountType', 'minBalance', 'alphabetRange', 'sortBy', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    /** Affiché pendant le chargement différé (lazy) du composant. */
    public function placeholder()
    {
        return <<<'HTML'
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <span class="spinner-border spinner-border-sm me-2"></span> Chargement des soldes des membres…
            </div>
        </div>
        HTML;
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
        $this->perPage = in_array((int) $this->perPage, self::PER_PAGE_OPTIONS, true) ? (int) $this->perPage : 10;
        $this->lotSize = ClientBalanceRanking::normalizeLotSize($this->lotSize);

        $accountFilter = ClientBalanceRanking::accountFilter($this->accountType, $this->currencyFilter, $this->minBalance);
        $rate = ClientBalanceRanking::usdToCdfRate();

        // Totaux de tous les membres filtrés, en une passe SQL
        $totals = ClientBalanceRanking::totals($this->baseQuery($accountFilter), $accountFilter, $rate);

        // Requête principale, classée selon le critère choisi
        $query = ClientBalanceRanking::apply(
            $this->baseQuery($accountFilter)->with(['accounts' => $accountFilter]),
            $this->sortBy,
            $accountFilter,
            $rate
        );

        $members = $query->paginate($this->perPage);

        // Soldes affichés, rang et part de l'encours créditeur
        $balances = $members->getCollection()->values()->map(function ($member, $index) use ($members, $totals, $rate) {
            $row = [
                'member' => $member,
                'current_usd' => 0,
                'current_cdf' => 0,
                'savings_usd' => 0,
                'savings_cdf' => 0,
            ];

            foreach ($member->accounts as $account) {
                $type = in_array($account->type, ['current', 'savings'], true) ? $account->type : null;
                $currency = in_array($account->currency, ['USD', 'CDF'], true) ? strtolower($account->currency) : null;
                if ($type && $currency) {
                    $row["{$type}_{$currency}"] += $account->balance;
                }
            }

            $amounts = ClientBalanceRanking::amountsOf($member);
            $row['rank'] = $members->firstItem() + $index;
            $row['total_usd_equiv'] = ClientBalanceRanking::value('total', $amounts, $rate);
            $row['share'] = ClientBalanceRanking::share($this->sortBy, $amounts, $totals['positive'], $rate);

            return $row;
        });

        // Lots PDF disponibles pour les filtres courants
        $lots = [];
        for ($from = 1, $n = 1; $from <= $members->total(); $from += $this->lotSize, $n++) {
            $lots[] = ['number' => $n, 'from' => $from, 'to' => min($members->total(), $from + $this->lotSize - 1)];
        }

        return view('livewire.repports.rapport-compte-clients', [
            'balances' => $balances,
            'members' => $members,
            'globalCurrentUsd' => $totals['current_usd'],
            'globalCurrentCdf' => $totals['current_cdf'],
            'globalSavingsUsd' => $totals['savings_usd'],
            'globalSavingsCdf' => $totals['savings_cdf'],
            'sorts' => ClientBalanceRanking::SORTS,
            'isBalanceSort' => ClientBalanceRanking::isBalanceSort($this->sortBy),
            'rate' => $rate,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'lotSizes' => ClientBalanceRanking::LOT_SIZES,
            'lots' => $lots,
            'exportParams' => [
                'search' => $this->search,
                'accountType' => $this->accountType,
                'currencyFilter' => $this->currencyFilter,
                'minBalance' => $this->minBalance,
                'alphabetRange' => $this->alphabetRange,
                'sortBy' => $this->sortBy,
            ],
        ]);
    }

}
