<?php

namespace App\Services;

use App\Models\Compte;
use App\Models\Journal;
use App\Models\Credit;
use App\Models\Repayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ManagementRatioService
{
    /**
     * Get all management ratios for a given currency and date.
     */
    public function getRatios(string $currency, string $dateReference)
    {
        $totals = $this->getFinancialTotals($currency, $dateReference);
        $creditStats = $this->getCreditStats($currency, $dateReference);
        $productivity = $this->getProductivityStats();

        $ratios = [];

        // 1. Ratio de solvabilité: Fonds propres / Total actif (Simplified Risk Weighted)
        // Note: In a real IMF, "Actif pondéré" is complex. We'll use Total Actif as proxy or a simple weight.
        $ratios['solvency'] = $totals['actif'] > 0 ? ($totals['equity'] / $totals['actif']) * 100 : 0;

        // 2. Ratio de liquidité: Actifs liquides / Dépôts à vue
        // Liquid Assets = Classe 5. Dépôts = accounts starting with 4 (Simplified)
        $ratios['liquidity'] = $totals['short_term_liabilities'] > 0 ? ($totals['liquid_assets'] / $totals['short_term_liabilities']) * 100 : 0;

        // 3. Couverture des immobilisations: Immobilisations nettes / Fonds propres
        $ratios['fixed_asset_coverage'] = $totals['equity'] > 0 ? ($totals['fixed_assets'] / $totals['equity']) * 100 : 0;

        // 4. Taux d’immobilisation: Immobilisations nettes / Total actif
        $ratios['fixed_asset_ratio'] = $totals['actif'] > 0 ? ($totals['fixed_assets'] / $totals['actif']) * 100 : 0;

        // 5. Couverture des emplois à Moyen et Long Terme: Ressources stables / Crédits MLT
        // Using Equity + Long Term Liabilities as Stable Resources.
        $ratios['long_term_coverage'] = $creditStats['long_term_portfolio'] > 0 ? ($totals['stable_resources'] / $creditStats['long_term_portfolio']) * 100 : 0;

        // 6. Taux d’encours de crédit: Encours total de crédit / Total actif
        $ratios['credit_to_asset_ratio'] = $totals['actif'] > 0 ? ($creditStats['total_portfolio'] / $totals['actif']) * 100 : 0;

        // 7. Taux d’encaisse oisive: (Encaisse + Banque) / Total actif
        $ratios['idle_cash_ratio'] = $totals['actif'] > 0 ? ($totals['liquid_assets'] / $totals['actif']) * 100 : 0;

        // 8. Portefeuille à risque (PAR30): Encours de crédit en retard > 30 jours / Encours total
        $ratios['par30'] = $creditStats['total_portfolio'] > 0 ? ($creditStats['overdue_30_portfolio'] / $creditStats['total_portfolio']) * 100 : 0;

        // 9. Taux de remboursement: Montant remboursé / Montant exigible
        $ratios['repayment_rate'] = $creditStats['total_due'] > 0 ? ($creditStats['total_repaid'] / $creditStats['total_due']) * 100 : 0;

        // 10. Taux de provisionnement: Provisions / Crédits en retard
        $ratios['provisioning_rate'] = $creditStats['total_overdue'] > 0 ? ($totals['provisions'] / $creditStats['total_overdue']) * 100 : 0;

        // 11. Autosuffisance opérationnelle: Produits d’exploitation / Charges d’exploitation
        $ratios['operational_self_sufficiency'] = $totals['operating_expenses'] > 0 ? ($totals['operating_income'] / $totals['operating_expenses']) * 100 : 0;

        // 12. Rendement du portefeuille: Produits financiers / Encours moyen de crédit
        $ratios['portfolio_yield'] = $creditStats['total_portfolio'] > 0 ? ($totals['financial_income'] / $creditStats['total_portfolio']) * 100 : 0;

        // 13. Rentabilité des actifs (ROA): Résultat net / Total actif
        $ratios['roa'] = $totals['actif'] > 0 ? ($totals['net_income'] / $totals['actif']) * 100 : 0;

        // 14. Rentabilité des fonds propres (ROE): Résultat net / Fonds propres
        $ratios['roe'] = $totals['equity'] > 0 ? ($totals['net_income'] / $totals['equity']) * 100 : 0;

        // 15. Productivité des agents de crédit: Nombre d’emprunteurs / Nombre d’agents crédit
        $ratios['agent_productivity'] = $productivity['agent_count'] > 0 ? ($productivity['borrower_count'] / $productivity['agent_count']) : 0;

        // 16. Coût opérationnel: Charges d’exploitation / Encours moyen de crédit
        $ratios['operational_cost'] = $creditStats['total_portfolio'] > 0 ? ($totals['operating_expenses'] / $creditStats['total_portfolio']) * 100 : 0;

        // 17. Coût par emprunteur: Charge d’exploitation / Nombre d’emprunteurs
        $ratios['cost_per_borrower'] = $productivity['borrower_count'] > 0 ? ($totals['operating_expenses'] / $productivity['borrower_count']) : 0;

        return [
            'ratios' => $ratios,
            'totals' => $totals,
            'creditStats' => $creditStats,
            'productivity' => $productivity,
        ];
    }

    /**
     * Get accounting totals based on Syscohada classes.
     */
    private function getFinancialTotals(string $currency, string $dateReference)
    {
        $end = Carbon::parse($dateReference)->endOfDay();

        // Assets (Classes 2, 3, 4, 5) - Simplifying for IMF ratios
        $fixedAssets = $this->getClassBalance(['2'], $currency, $end);
        $liquidAssets = $this->getClassBalance(['5'], $currency, $end);
        
        // Total Actif = Simplified as Debit balances of classes 2, 3, 4, 5
        $actif = $this->getSectionTotal('Actif', $currency, $end);

        // Equity (Classe 1 - accounts starting with 10, 11, 12, 13)
        $equity = $this->getClassBalance(['10', '11', '12'], $currency, $end);
        
        // Provisions (Class 19 or specific 29/39/49 accounts, if implemented)
        $provisions = $this->getClassBalance(['19'], $currency, $end);

        // Liabilities
        $shortTermLiabilities = $this->getClassBalance(['4'], $currency, $end); // Simplified: Class 4 as short term debt
        $stableResources = $this->getClassBalance(['1'], $currency, $end); // Class 1 as Long term resources

        // Income Statement (Class 7 and 6)
        $financialIncome = $this->getClassBalance(['70', '71'], $currency, $end); // Credit interests and fees
        $operatingIncome = $this->getClassBalance(['7'], $currency, $end);
        $operatingExpenses = $this->getClassBalance(['6'], $currency, $end);
        $netIncome = $operatingIncome - $operatingExpenses;

        return [
            'fixed_assets' => abs($fixedAssets),
            'liquid_assets' => abs($liquidAssets),
            'actif' => abs($actif),
            'equity' => abs($equity),
            'stable_resources' => abs($stableResources),
            'short_term_liabilities' => abs($shortTermLiabilities),
            'financial_income' => abs($financialIncome),
            'operating_income' => abs($operatingIncome),
            'operating_expenses' => abs($operatingExpenses),
            'net_income' => $netIncome,
            'provisions' => abs($provisions),
        ];
    }

    /**
     * Get balance for a specific set of accounting classes prefixes.
     */
    private function getClassBalance(array $prefixes, string $currency, Carbon $dateReference)
    {
        $sum = 0;
        foreach ($prefixes as $prefix) {
            $comptes = Compte::where('code', 'like', $prefix . '%')->get();
            foreach ($comptes as $compte) {
                $query = Journal::where('compte_id', $compte->id)
                    ->where('devise', $currency)
                    ->where('date_operation', '<=', $dateReference);
                
                $totalDebit = (float) $query->sum('montant_debit');
                $totalCredit = (float) $query->sum('montant_credit');

                if ($compte->type === 'Actif' || $compte->type === 'Charge') {
                    $sum += ($totalDebit - $totalCredit);
                } else {
                    $sum += ($totalCredit - $totalDebit);
                }
            }
        }
        return $sum;
    }

    private function getSectionTotal(string $type, string $currency, Carbon $dateReference)
    {
        // Calculate the total for a whole category (Actif, Passif, etc.)
        $comptes = Compte::where('type', $type)->get();
        $total = 0;
        foreach ($comptes as $compte) {
            $query = Journal::where('compte_id', $compte->id)
                ->where('devise', $currency)
                ->where('date_operation', '<=', $dateReference);
            
            $totalDebit = (float) $query->sum('montant_debit');
            $totalCredit = (float) $query->sum('montant_credit');

            if ($type === 'Actif' || $type === 'Charge') {
                $total += ($totalDebit - $totalCredit);
            } else {
                $total += ($totalCredit - $totalDebit);
            }
        }
        return $total;
    }

    /**
     * Get credit stats using Credit and Repayment models.
     */
    private function getCreditStats(string $currency, string $dateReference)
    {
        $refDate = Carbon::parse($dateReference)->endOfDay();

        // Total Portfolio Encours
        $totalPortfolio = (float) Credit::where('currency', $currency)
            ->where('created_at', '<=', $refDate)
            ->where('is_paid', false)
            ->sum('amount');

        // Overdue Portfolio > 30 days
        $thirtyDaysAgo = $refDate->copy()->subDays(30);
        $overdue30Ids = Repayment::where('due_date', '<', $thirtyDaysAgo)
            ->where('is_paid', false)
            ->whereHas('credit', fn($q) => $q->where('currency', $currency))
            ->distinct()
            ->pluck('credit_id');
        
        $overdue30Portfolio = (float) Credit::whereIn('id', $overdue30Ids)->sum('amount');

        // Total Overdue (any delay)
        $overdueIds = Repayment::where('due_date', '<', $refDate)
            ->where('is_paid', false)
            ->whereHas('credit', fn($q) => $q->where('currency', $currency))
            ->distinct()
            ->pluck('credit_id');
        
        $totalOverdue = (float) Credit::whereIn('id', $overdueIds)->sum('amount');

        // Repayment Stats
        $totalDue = (float) Repayment::where('due_date', '<=', $refDate)
            ->whereHas('credit', fn($q) => $q->where('currency', $currency))
            ->sum('expected_amount');
        
        $totalRepaid = (float) Repayment::where('due_date', '<=', $refDate)
            ->whereHas('credit', fn($q) => $q->where('currency', $currency))
            ->sum('paid_amount');

        // Long Term Portfolio (Simplified: duration > 12 months if field exists, else maybe a flag)
        // For now using total as proxy if no specific duration field or assume all are short-term if missing.
        // Let's check Credit model for duration.
        $longTermPortfolio = 0; // Placeholder

        return [
            'total_portfolio' => $totalPortfolio,
            'overdue_30_portfolio' => $overdue30Portfolio,
            'total_overdue' => $totalOverdue,
            'total_due' => $totalDue,
            'total_repaid' => $totalRepaid,
            'long_term_portfolio' => $longTermPortfolio,
        ];
    }

    /**
     * Get productivity stats (Agent count and Borrower count).
     */
    private function getProductivityStats()
    {
        $agentCount = User::whereIn('role', ['recouvreur', 'agent', 'caissier'])->count();
        // Emprunteurs actifs (les membres ayant un crédit non solder)
        $borrowerCount = User::whereHas('credits', function($q) {
            $q->where('is_paid', false);
        })->count();

        return [
            'agent_count' => $agentCount,
            'borrower_count' => $borrowerCount,
        ];
    }
}
