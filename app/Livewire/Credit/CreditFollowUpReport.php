<?php

namespace App\Livewire\Credit;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Credit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CreditFollowUpReport extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $perPage = 10;
    public $searchMember = '';
    public $currency = '';
    public $status = '';
    public $startDate = '';
    public $endDate = '';
    public $searchAgent = '';

    public function render()
    {
        $query = Credit::with(['user'])->select('credits.*');

        // 🔍 Filtres
        if ($this->searchMember) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', "%{$this->searchMember}%")
                  ->orWhere('id', 'like', "%{$this->searchMember}%")
                  ->orWhere('code', 'like', "%{$this->searchMember}%")
                  ->orWhere('postnom', 'like', "%{$this->searchMember}%")
                  ->orWhere('prenom', 'like', "%{$this->searchMember}%");
            });
        }
        if ($this->searchAgent) {
            $query->whereHas('agent', function ($q) {
                $q->where('name', 'like', "%{$this->searchMember}%")
                  ->orWhere('id', 'like', "%{$this->searchMember}%")
                  ->orWhere('code', 'like', "%{$this->searchMember}%")
                  ->orWhere('postnom', 'like', "%{$this->searchMember}%")
                  ->orWhere('prenom', 'like', "%{$this->searchMember}%");
            });
        }

        if ($this->currency) {
            $query->where('currency', $this->currency);
        }

        if ($this->status === 'paid') {
            $query->where('is_paid', true);
        } elseif ($this->status === 'unpaid') {
            $query->where('is_paid', false);
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('start_date', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->whereDate('start_date', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->whereDate('start_date', '<=', $this->endDate);
        }

        $credits = $query->latest()->paginate($this->perPage);
        $totals = $this->getTotals();

        return view('livewire.credit.credit-follow-up-report', [
            'credits' => $credits,
            'totals' => $totals,
        ]);
    }

    public function getTotals()
    {
        $query = $this->baseFilteredQuery();
        $credits = $query->with(['repayments'])->get();

        return $this->calculateTotals($credits);
    }

    public function exportToPdf()
    {
        $query = $this->baseFilteredQuery();
        $credits = $query->with(['user', 'repayments'])->get();

        // 🧮 Calcul des totaux incluant intérêts et pénalités
        $totals = $this->calculateTotals($credits);

        $pdf = Pdf::loadView('pdf.credits-report', compact('credits', 'totals'))
            ->setPaper('A4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, "rapport_credit_" . now()->format("Ymd_His") . ".pdf");
    }

    private function baseFilteredQuery()
    {
        $query = Credit::query();

        if ($this->searchMember) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', "%{$this->searchMember}%")
                  ->orWhere('id', 'like', "%{$this->searchMember}%")
                  ->orWhere('code', 'like', "%{$this->searchMember}%")
                  ->orWhere('postnom', 'like', "%{$this->searchMember}%")
                  ->orWhere('prenom', 'like', "%{$this->searchMember}%");
            });
        }

        if ($this->searchAgent) {
            $query->whereHas('agent', function ($q) {
                $q->where('name', 'like', "%{$this->searchMember}%")
                  ->orWhere('id', 'like', "%{$this->searchMember}%")
                  ->orWhere('code', 'like', "%{$this->searchMember}%")
                  ->orWhere('postnom', 'like', "%{$this->searchMember}%")
                  ->orWhere('prenom', 'like', "%{$this->searchMember}%");
            });
        }

        if ($this->currency) {
            $query->where('currency', $this->currency);
        }

        if ($this->status === 'paid') {
            $query->where('is_paid', true);
        } elseif ($this->status === 'unpaid') {
            $query->where('is_paid', false);
        }

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('start_date', [$this->startDate, $this->endDate]);
        } elseif ($this->startDate) {
            $query->whereDate('start_date', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->whereDate('start_date', '<=', $this->endDate);
        }

        return $query;
    }

    private function calculateTotals($credits)
    {
        $creditIds = $credits->pluck('id')->toArray();

        $totalByCurrency = ['USD' => 0, 'CDF' => 0];
        $totalPaidByCurrency = ['USD' => 0, 'CDF' => 0];
        $totalUnpaidByCurrency = ['USD' => 0, 'CDF' => 0];
        $penaltyByCurrency = ['USD' => 0, 'CDF' => 0];
        $interestByCurrency = ['USD' => 0, 'CDF' => 0];

        foreach ($credits as $credit) {
            $curr = $credit->currency;
            $totalByCurrency[$curr] += $credit->amount;

            $paid = $credit->repayments->where('is_paid', true)->sum('paid_amount');
            $remaining = max(0, $credit->amount - $paid);
            $totalPaidByCurrency[$curr] += $paid;
            $totalUnpaidByCurrency[$curr] += $remaining;

            $penaltyByCurrency[$curr] += $credit->repayments->sum('penalty');
        }

        // 💰 Calcul des intérêts totaux (selon crédits filtrés)
        foreach (['USD', 'CDF'] as $curr) {
            $interestByCurrency[$curr] = DB::table('repayments')
                ->join('credits', 'repayments.credit_id', '=', 'credits.id')
                ->whereIn('credits.id', $creditIds)
                ->where('credits.currency', $curr)
                ->where('repayments.is_paid', true)
                ->sum(DB::raw('GREATEST((repayments.paid_amount - (credits.amount / credits.installments)), 0)'));
        }

        return [
            'totalByCurrency' => $totalByCurrency,
            'totalPaidByCurrency' => $totalPaidByCurrency,
            'totalUnpaidByCurrency' => $totalUnpaidByCurrency,
            'penaltyByCurrency' => $penaltyByCurrency,
            'interestByCurrency' => $interestByCurrency,
        ];
    }
}
