<?php

// app/Http/Livewire/CreditFollowUpReport.php
namespace App\Livewire\Credit;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Credit;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

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

    public function render()
    {
        $query = Credit::with(['user'])
            ->select('credits.*');

        // Recherche par membre
        if ($this->searchMember) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', "%{$this->searchMember}%")
                  ->orWhere('id', 'like', "%{$this->searchMember}%");
            });
        }

        // Filtre par devise
        if ($this->currency) {
            $query->where('currency', $this->currency);
        }

        // Filtre par statut
        if ($this->status === 'paid') {
            $query->where('is_paid', true);
        } elseif ($this->status === 'unpaid') {
            $query->where('is_paid', false);
        }

        // Filtre par période
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
    $query = Credit::query();

    // 🧭 Application des filtres
    if ($this->searchMember) {
        $query->whereHas('user', function ($q) {
            $q->where('name', 'like', "%{$this->searchMember}%")
              ->orWhere('id', 'like', "%{$this->searchMember}%");
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

    // 🔎 Récupération des crédits filtrés
    $credits = $query->with(['repayments'])->get();

    // 🔹 IDs des crédits sélectionnés (pour le filtre global)
    $creditIds = $credits->pluck('id')->toArray();

    // Initialisation
    $totalByCurrency = ['USD' => 0, 'CDF' => 0];
    $totalPaidByCurrency = ['USD' => 0, 'CDF' => 0];
    $totalUnpaidByCurrency = ['USD' => 0, 'CDF' => 0];

    foreach ($credits as $credit) {
        $curr = $credit->currency;
        $totalByCurrency[$curr] += $credit->amount;
        $paid = $credit->repayments->where('is_paid', true)->sum('paid_amount');
        $remaining = max(0, $credit->amount - $paid);
        $totalPaidByCurrency[$curr] += $paid;
        $totalUnpaidByCurrency[$curr] += $remaining;
    }

    // 💰 Calcul des intérêts selon les crédits filtrés
    $interestByCurrency = [
        'USD' => DB::table('repayments')
            ->join('credits', 'repayments.credit_id', '=', 'credits.id')
            ->whereIn('credits.id', $creditIds)
            ->where('credits.currency', 'USD')
            ->where('repayments.is_paid', true)
            ->sum(DB::raw('GREATEST((repayments.paid_amount - (credits.amount / credits.installments)), 0)')),

        'CDF' => DB::table('repayments')
            ->join('credits', 'repayments.credit_id', '=', 'credits.id')
            ->whereIn('credits.id', $creditIds)
            ->where('credits.currency', 'CDF')
            ->where('repayments.is_paid', true)
            ->sum(DB::raw('GREATEST((repayments.paid_amount - (credits.amount / credits.installments)), 0)')),
    ];

    // ⚠️ Même correction pour les pénalités
    $penaltyByCurrency = [
        'USD' => DB::table('repayments')
            ->join('credits', 'repayments.credit_id', '=', 'credits.id')
            ->whereIn('credits.id', $creditIds)
            ->where('credits.currency', 'USD')
            ->sum('repayments.penalty'),

        'CDF' => DB::table('repayments')
            ->join('credits', 'repayments.credit_id', '=', 'credits.id')
            ->whereIn('credits.id', $creditIds)
            ->where('credits.currency', 'CDF')
            ->sum('repayments.penalty'),
    ];

    return [
        'totalByCurrency' => $totalByCurrency,
        'totalPaidByCurrency' => $totalPaidByCurrency,
        'totalUnpaidByCurrency' => $totalUnpaidByCurrency,
        'interestByCurrency' => $interestByCurrency,
        'penaltyByCurrency' => $penaltyByCurrency,
    ];
}



    public function exportToPdf()
    {
        $query = Credit::with(['user'])->latest();

        if ($this->searchMember) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', "%{$this->searchMember}%")
                ->orWhere('id', 'like', "%{$this->searchMember}%");
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

        $credits = $query->get();
        $totals = $this->calculateTotals($credits);

        $pdf = Pdf::loadView('pdf.credits-report', compact('credits', 'totals'))->setPaper('A4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, "rapport_credit_".now()->format("Ymd_His").".pdf");
    }

    private function calculateTotals($credits)
    {
        // Initialisation des totaux par devise
        $totalByCurrency = ['USD' => 0, 'CDF' => 0];
        $totalPaidByCurrency = ['USD' => 0, 'CDF' => 0];
        $penaltyByCurrency = ['USD' => 0, 'CDF' => 0];
        $totalUnpaidByCurrency = ['USD' => 0, 'CDF' => 0];

        foreach ($credits as $credit) {
            $curr = $credit->currency;
            $totalByCurrency[$curr] += $credit->amount;

            $paidAmount = $credit->repayments->where('is_paid', true)->sum('paid_amount');
            $totalPaidByCurrency[$curr] += $paidAmount;

            $remaining = $credit->amount - $paidAmount;
            $totalUnpaidByCurrency[$curr] += max(0, $remaining);

            $penaltyByCurrency[$curr] += $credit->repayments->sum('penalty');
        }

        return [
            'totalByCurrency' => $totalByCurrency,
            'totalPaidByCurrency' => $totalPaidByCurrency,
            'totalUnpaidByCurrency' => $totalUnpaidByCurrency,
            'penaltyByCurrency' => $penaltyByCurrency,
        ];
    }
}
