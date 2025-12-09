<?php

namespace App\Livewire\Repports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\MembershipCard;
use Barryvdh\DomPDF\Facade\Pdf;

class RapportCarnetsComponent extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $currency = '';
    public $periodFilter = '';
    public $minDaysFilled = null;
    public $maxDaysFilled = null;
    public $exactDaysFilled = null;
    public $search = '';
    public $paginate = 10;

    public $totalCarnets;
    public $carnetsUSD;
    public $carnetsCDF;
    public $totalToSave;
    public $totalSaved;
    public $totalContributedDays;
    public $status = ''; // 'open' ou 'closed'


    public function mount()
    {
        $this->updateStats();
    }

    public function updated($field)
    {
        if (in_array($field, ['currency', 'periodFilter', 'minDaysFilled', 'maxDaysFilled', 'exactDaysFilled', 'search', 'status'])) {
            $this->resetPage();
        }
        $this->updateStats();
    }

    public function updateStats()
    {
        $query = MembershipCard::query();

        if ($this->status === 'open') {
            $query->where('is_active', true);
        } elseif ($this->status === 'closed') {
            $query->where('is_active', false);
        }

        if ($this->currency) {
            $query->where('currency', $this->currency);
        }

        if ($this->periodFilter) {
            switch ($this->periodFilter) {
                case 'today':
                    $query->whereDate('created_at', now()->toDateString());
                    break;
                case 'this_week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                    break;
                case 'this_year':
                    $query->whereYear('created_at', now()->year);
                    break;
            }
        }

        if ($this->search) {
            $query->whereHas('member', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('postnom', 'like', '%' . $this->search . '%')
                  ->orWhere('prenom', 'like', '%' . $this->search . '%');
            });
        }

        $carnets = $query->withCount(['contributions as contributed_days_count' => function ($q) {
            $q->where('is_paid', true);
        }])->get();

        if ($this->minDaysFilled !== null) {
            $carnets = $carnets->filter(fn($c) => $c->contributed_days_count >= $this->minDaysFilled);
        }

        if ($this->maxDaysFilled !== null) {
            $carnets = $carnets->filter(fn($c) => $c->contributed_days_count <= $this->maxDaysFilled);
        }

        if ($this->exactDaysFilled !== null && is_numeric($this->exactDaysFilled)) {
            $carnets = $carnets->filter(fn($c) => $c->contributed_days_count == $this->exactDaysFilled);
        }

        $this->totalCarnets = $carnets->count();
        $this->carnetsUSD = $carnets->where('currency', 'USD')->count();
        $this->carnetsCDF = $carnets->where('currency', 'CDF')->count();
        $this->totalToSave = $carnets->sum(fn($c) => $c->subscription_amount * 31);
        $this->totalSaved = $carnets->sum(fn($c) => $c->contributed_days_count * $c->subscription_amount);
        $this->totalContributedDays = $carnets->sum('contributed_days_count');
    }

    public function getFilteredCarnetsProperty()
    {
        $query = MembershipCard::query()
            ->with('member')
            ->withCount(['contributions as contributed_days_count' => function ($q) {
                $q->where('is_paid', true);
            }]);
        
        if ($this->status === 'open') {
            $query->where('is_active', true);
        } elseif ($this->status === 'closed') {
            $query->where('is_active', false);
        }

        if ($this->currency) {
            $query->where('currency', $this->currency);
        }

        if ($this->periodFilter) {
            switch ($this->periodFilter) {
                case 'today':
                    $query->whereDate('created_at', now()->toDateString());
                    break;
                case 'this_week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                    break;
                case 'this_year':
                    $query->whereYear('created_at', now()->year);
                    break;
            }
        }

        if ($this->search) {
            $query->whereHas('member', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('postnom', 'like', '%' . $this->search . '%')
                  ->orWhere('prenom', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->minDaysFilled !== null) {
            $query->having('contributed_days_count', '>=', $this->minDaysFilled);
        }

        if ($this->maxDaysFilled !== null) {
            $query->having('contributed_days_count', '<=', $this->maxDaysFilled);
        }

        if ($this->exactDaysFilled !== null && is_numeric($this->exactDaysFilled)) {
            $query->having('contributed_days_count', '=', $this->exactDaysFilled);
        }

        return $query->paginate($this->paginate);
    }


    public function exportPdf()
    {
        $carnets = MembershipCard::query()
            ->with('member')
            ->withCount(['contributions as contributed_days_count' => function ($q) {
                $q->where('is_paid', true);
            }]);

        if ($this->status === 'open') {
            $carnets->where('is_active', true);
        } elseif ($this->status === 'closed') {
            $carnets->where('is_active', false);
        }

        if ($this->currency) {
            $carnets->where('currency', $this->currency);
        }

        if ($this->periodFilter) {
            switch ($this->periodFilter) {
                case 'today':
                    $carnets->whereDate('created_at', now()->toDateString());
                    break;
                case 'this_week':
                    $carnets->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $carnets->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year);
                    break;
                case 'this_year':
                    $carnets->whereYear('created_at', now()->year);
                    break;
            }
        }

        if ($this->search) {
            $carnets->whereHas('member', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('postnom', 'like', '%' . $this->search . '%')
                ->orWhere('prenom', 'like', '%' . $this->search . '%');
            });
        }

        // Filtrage par nombre de jours de contribution
        if ($this->minDaysFilled !== null) {
            $carnets->having('contributed_days_count', '>=', $this->minDaysFilled);
        }

        if ($this->maxDaysFilled !== null) {
            $carnets->having('contributed_days_count', '<=', $this->maxDaysFilled);
        }

        if ($this->exactDaysFilled !== null && is_numeric($this->exactDaysFilled)) {
            $carnets->having('contributed_days_count', '=', $this->exactDaysFilled);
        }

        $carnets = $carnets->get();

        $pdf = Pdf::loadView('pdf.carnets_pdf', [
            'titre' => 'Liste des carnets filtrés',
            'carnets' => $carnets,
            'filters' => [
                'currency' => $this->currency,
                'period' => $this->periodFilter,
                'min' => $this->minDaysFilled,
                'max' => $this->maxDaysFilled,
                'exact' => $this->exactDaysFilled,
            ]
        ])->setPaper('A4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'rapport-carnets.pdf');
    }


    public function render()
    {
        return view('livewire.repports.rapport-carnets-component', [
            'carnets' => $this->filteredCarnets,
        ]);
    }
}