<?php

namespace App\Livewire\Credit;

use App\Helpers\UserLogHelper;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Credit;
use App\Models\User;
use App\Models\UserLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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

        // If dataset is large, export XLSX to avoid PDF memory issues
        if ($credits->count() > 150) {
            // Use Excel export
            try {
                return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\CreditsReportExport($credits), "rapport_credit_" . now()->format("Ymd_His") . ".xlsx");
            } catch (\Exception $e) {
                // Fallback to PDF if Excel export fails
            }
        }

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
        // Separate remaining parts
        $remainingPrincipalByCurrency = ['USD' => 0, 'CDF' => 0];
        $remainingInterestByCurrency = ['USD' => 0, 'CDF' => 0];
        $remainingPenaltyByCurrency = ['USD' => 0, 'CDF' => 0];
        // Collected amounts
        $penaltyByCurrency = ['USD' => 0, 'CDF' => 0]; // collected penalties
        $interestByCurrency = ['USD' => 0, 'CDF' => 0]; // collected interests
        $collectedPrincipalByCurrency = ['USD' => 0, 'CDF' => 0];
        $recoveryRateByCurrency = ['USD' => 0, 'CDF' => 0];
        $interestMarginByCurrency = ['USD' => 0, 'CDF' => 0];
        $debtRatioByCurrency = ['USD' => 0, 'CDF' => 0];

        if ($credits->isEmpty()) {
            return compact(
                'totalByCurrency',
                'totalPaidByCurrency',
                'totalUnpaidByCurrency',
                'penaltyByCurrency',
                'interestByCurrency',
                'recoveryRateByCurrency',
                'interestMarginByCurrency',
                'debtRatioByCurrency',
                'collectedPrincipalByCurrency',
                'remainingPrincipalByCurrency',
                'remainingInterestByCurrency',
                'remainingPenaltyByCurrency'
            );
        }

        $creditIds = $credits->pluck('id')->toArray();

        foreach ($credits as $credit) {
            $curr = $credit->currency;
            if (!isset($totalByCurrency[$curr])) {
                $totalByCurrency[$curr] = 0;
                $totalPaidByCurrency[$curr] = 0;
                $totalUnpaidByCurrency[$curr] = 0;
                $remainingPrincipalByCurrency[$curr] = 0;
                $remainingInterestByCurrency[$curr] = 0;
                $remainingPenaltyByCurrency[$curr] = 0;
                $penaltyByCurrency[$curr] = 0;
                $interestByCurrency[$curr] = 0;
            }

            $totalByCurrency[$curr] += (float) $credit->amount;

            // Collected totals (as before)
            $paidTotal = (float) $credit->repayments->sum('paid_amount');
            $totalPaidByCurrency[$curr] += $paidTotal;

            // For each repayment we compute remaining principal/interest/penalty using explicit columns
            $repRemainingPrincipal = 0.0;
            $repRemainingInterest = 0.0;
            $repRemainingPenalty = 0.0;
            $collectedInterest = 0.0;
            $collectedPenalty = 0.0;

            foreach ($credit->repayments as $r) {
                $principalAmount = floatval($r->principal_amount ?? $r->expected_amount);
                $interestAmount = floatval($r->interest_amount ?? max(0, $r->expected_amount - ($r->principal_amount ?? 0)));
                $penaltyAmount = floatval($r->penalty ?? 0);

                $paidPrincipal = floatval($r->paid_principal ?? 0);
                $paidInterest = floatval($r->paid_interest ?? 0);
                $paidPenalty = floatval($r->paid_penalty ?? 0);

                $remainingP = max(0.0, $principalAmount - $paidPrincipal);
                $remainingI = max(0.0, $interestAmount - $paidInterest);
                $remainingPen = max(0.0, $penaltyAmount - $paidPenalty);

                $repRemainingPrincipal += $remainingP;
                $repRemainingInterest += $remainingI;
                $repRemainingPenalty += $remainingPen;

                $collectedInterest += $paidInterest;
                $collectedPenalty += $paidPenalty;
                $collectedPrincipalByCurrency[$curr] += $paidPrincipal;
            }

            $remainingPrincipalByCurrency[$curr] += $repRemainingPrincipal;
            $remainingInterestByCurrency[$curr] += $repRemainingInterest;
            $remainingPenaltyByCurrency[$curr] += $repRemainingPenalty;

            // total unpaid is sum of remaining parts
            $totalUnpaidByCurrency[$curr] += ($repRemainingPrincipal + $repRemainingInterest + $repRemainingPenalty);

            // Collected parts
            $interestByCurrency[$curr] += $collectedInterest;
            $penaltyByCurrency[$curr] += $collectedPenalty;
            $totalPaidByCurrency[$curr] = ($totalPaidByCurrency[$curr] ?? 0) ;
            $collectedPrincipalByCurrency[$curr] = $collectedPrincipalByCurrency[$curr] ?? 0;
        }

        // 📊 Calcul des ratios finaux
        foreach ($totalByCurrency as $curr => $principal) {
            // Compute total expected (principal + interest + penalty) for filtered credits and currency
            $totalExpected = DB::table('repayments')
                ->join('credits', 'repayments.credit_id', '=', 'credits.id')
                ->whereIn('credits.id', $creditIds)
                ->where('credits.currency', $curr)
                ->selectRaw('SUM(COALESCE(repayments.principal_amount, repayments.expected_amount) + COALESCE(repayments.interest_amount, 0) + COALESCE(repayments.penalty, 0)) as total')
                ->value('total');

            $totalExpected = floatval($totalExpected ?: 0);

            if ($totalExpected > 0) {
                $recoveryRateByCurrency[$curr] = ($totalPaidByCurrency[$curr] / $totalExpected) * 100;
                $debtRatioByCurrency[$curr] = ($totalUnpaidByCurrency[$curr] / $totalExpected) * 100;
            }

            if ($principal > 0) {
                $interestMarginByCurrency[$curr] = ($interestByCurrency[$curr] / $principal) * 100;
            }
        }

        return [
            'totalByCurrency' => $totalByCurrency,
            'totalPaidByCurrency' => $totalPaidByCurrency,
            'totalUnpaidByCurrency' => $totalUnpaidByCurrency,
            'penaltyByCurrency' => $penaltyByCurrency,
            'interestByCurrency' => $interestByCurrency,
            'collectedPrincipalByCurrency' => $collectedPrincipalByCurrency,
            'remainingPrincipalByCurrency' => $remainingPrincipalByCurrency,
            'remainingInterestByCurrency' => $remainingInterestByCurrency,
            'remainingPenaltyByCurrency' => $remainingPenaltyByCurrency,
            'recoveryRateByCurrency' => $recoveryRateByCurrency,
            'interestMarginByCurrency' => $interestMarginByCurrency,
            'debtRatioByCurrency' => $debtRatioByCurrency,
        ];
    }

    public function toggleCreditStatus($creditId)
    {
        Gate::authorize('modifier-credit', User::class);

        try {
            $credit = Credit::findOrFail($creditId);

            $credit->update([
                'is_paid' => !$credit->is_paid,
            ]);
            UserLogHelper::log_user_activity(
                action: 'Changement du statut du crédit',
                description: "Le statut du crédit ID: {$credit->id} a été changé à " . ($credit->is_paid ? 'soldé' : 'en cours'),
            );

            notyf()->success($credit->is_paid
                    ? 'Le crédit a été marqué comme soldé.'
                    : 'Le crédit a été remis en cours.');

        } catch (\Exception $e) {
            notyf()->error('Erreur lors de la mise à jour du statut du crédit: ' . $e->getMessage());
        }
    }
}
