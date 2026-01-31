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
        $query = $this->baseFilteredQuery()->with(['user']);

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
                $q->where('name', 'like', "%{$this->searchAgent}%")
                    ->orWhere('id', 'like', "%{$this->searchAgent}%")
                    ->orWhere('code', 'like', "%{$this->searchAgent}%")
                    ->orWhere('postnom', 'like', "%{$this->searchAgent}%")
                    ->orWhere('prenom', 'like', "%{$this->searchAgent}%");
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

        // 📅 Filtre de date : Crédits ACTIFS durant la période
        if ($this->startDate && $this->endDate) {
            $query->where('start_date', '<=', $this->endDate)
                ->where('due_date', '>=', $this->startDate);
        } elseif ($this->startDate) {
            $query->where('due_date', '>=', $this->startDate);
        } elseif ($this->endDate) {
            $query->where('start_date', '<=', $this->endDate);
        }

        return $query;
    }

    private function calculateTotals($credits)
    {
        $totalByCurrency = ['USD' => 0, 'CDF' => 0];
        $totalPaidByCurrency = ['USD' => 0, 'CDF' => 0];
        $totalUnpaidByCurrency = ['USD' => 0, 'CDF' => 0];
        $penaltyByCurrency = ['USD' => 0, 'CDF' => 0];
        $interestByCurrency = ['USD' => 0, 'CDF' => 0];

        if ($credits->isEmpty()) {
            return compact('totalByCurrency', 'totalPaidByCurrency', 'totalUnpaidByCurrency', 'penaltyByCurrency', 'interestByCurrency');
        }

        $creditIds = $credits->pluck('id')->toArray();

        foreach ($credits as $credit) {
            $curr = $credit->currency;
            if (!isset($totalByCurrency[$curr])) {
                $totalByCurrency[$curr] = 0;
                $totalPaidByCurrency[$curr] = 0;
                $totalUnpaidByCurrency[$curr] = 0;
                $penaltyByCurrency[$curr] = 0;
                $interestByCurrency[$curr] = 0;
            }

            $totalByCurrency[$curr] += $credit->amount;

            $paid = $credit->repayments->where('is_paid', true)->sum('paid_amount');
            $remaining = max(0, $credit->amount - $paid);
            $totalPaidByCurrency[$curr] += $paid;
            $totalUnpaidByCurrency[$curr] += $remaining;

            $penaltyByCurrency[$curr] += $credit->repayments->sum('penalty');
        }

        // 💰 Calcul des intérêts totaux (selon crédits filtrés)
        $interests = DB::table('repayments')
            ->join('credits', 'repayments.credit_id', '=', 'credits.id')
            ->whereIn('credits.id', $creditIds)
            ->where('repayments.is_paid', true)
            ->select('credits.currency', DB::raw('SUM(GREATEST((repayments.paid_amount - (credits.amount / credits.installments)), 0)) as total_interest'))
            ->groupBy('credits.currency')
            ->get();

        foreach ($interests as $interest) {
            $interestByCurrency[$interest->currency] = $interest->total_interest;
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
