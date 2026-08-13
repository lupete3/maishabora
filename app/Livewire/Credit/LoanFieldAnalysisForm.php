<?php

namespace App\Livewire\Credit;

use App\Models\LoanAgentProposal;
use App\Models\LoanApplication;
use App\Models\LoanBalance;
use App\Models\LoanBalanceSheetDetail;
use App\Models\LoanBusinessProfile;
use App\Models\LoanCashflow;
use App\Models\LoanCashflowAnalysis;
use App\Models\LoanCoBorrower;
use App\Models\LoanCollateralProperty;
use App\Models\LoanCreditHistory;
use App\Models\LoanExpenseLine;
use App\Models\LoanFamilyMember;
use App\Models\LoanFieldVisit;
use App\Models\LoanHouseholdReference;
use App\Models\LoanInventoryItem;
use App\Models\LoanInvestmentPlanItem;
use App\Models\Security;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class LoanFieldAnalysisForm extends Component
{
    public LoanApplication $loan;

    public array $visit = [];
    public array $businessProfile = [];
    public array $balance = [];
    public array $cashflow = [];
    public array $collateralProperty = [];
    public array $coBorrower = [];
    public array $proposal = [];

    public array $familyMembers = [];
    public array $householdReferences = [];
    public array $investmentPlanItems = [];
    public array $creditHistories = [];
    public array $businessExpenses = [];
    public array $householdExpenses = [];
    public array $inventoryItems = [];
    public array $securities = [];

    public function mount(int $loan_application_id): void
    {
        $this->loan = LoanApplication::with([
            'fieldVisit',
            'businessProfile',
            'balanceSheetDetail',
            'cashflowAnalysis',
            'collateralProperty',
            'coBorrower',
            'agentProposal',
            'familyMembers',
            'householdReferences',
            'investmentPlanItems',
            'creditHistories',
            'expenseLines',
            'inventoryItems',
            'securities',
        ])->findOrFail($loan_application_id);

        $this->loadForm();
    }

    public function save(): void
    {
        $this->validate($this->rules(), $this->messages());

        $balance = $this->calculatedBalance();
        $cashflow = $this->calculatedCashflow();

        DB::transaction(function () use ($balance, $cashflow) {
            LoanFieldVisit::updateOrCreate(
                ['loan_application_id' => $this->loan->id],
                $this->moneyData($this->visit)
            );

            LoanBusinessProfile::updateOrCreate(
                ['loan_application_id' => $this->loan->id],
                $this->moneyData($this->businessProfile)
            );

            LoanBalanceSheetDetail::updateOrCreate(
                ['loan_application_id' => $this->loan->id],
                array_merge($this->moneyData($this->balance), $balance, ['calculated_at' => now()])
            );

            LoanCashflowAnalysis::updateOrCreate(
                ['loan_application_id' => $this->loan->id],
                array_merge($this->moneyData($this->cashflow), $cashflow, ['calculated_at' => now()])
            );

            LoanCollateralProperty::updateOrCreate(
                ['loan_application_id' => $this->loan->id],
                $this->moneyData($this->collateralProperty)
            );

            LoanCoBorrower::updateOrCreate(
                ['loan_application_id' => $this->loan->id],
                $this->moneyData($this->coBorrower)
            );

            LoanAgentProposal::updateOrCreate(
                ['loan_application_id' => $this->loan->id],
                array_merge($this->moneyData($this->proposal), ['agent_id' => auth()->id()])
            );

            $this->replaceRows(LoanFamilyMember::class, $this->familyMembers, ['name']);
            $this->replaceRows(LoanHouseholdReference::class, $this->householdReferences, ['name']);
            $this->replaceRows(LoanInvestmentPlanItem::class, $this->investmentPlanItems, ['destination', 'amount']);
            $this->replaceRows(LoanCreditHistory::class, $this->creditHistories, ['institution']);
            $this->replaceExpenseRows('business', $this->businessExpenses);
            $this->replaceExpenseRows('household', $this->householdExpenses);
            $this->replaceRows(LoanInventoryItem::class, $this->inventoryItems, ['description']);
            $this->replaceRows(Security::class, $this->securities, ['type', 'valeur_estimee']);
            $this->syncLegacyAnalysisTables($balance, $cashflow);
        });

        $this->loan->refresh();
        notyf()->success('Fiche d analyse terrain enregistree.');
        $this->dispatch('refreshAnalysis');
    }

    protected function rules(): array
    {
        $exchangeRateRule = ($this->loan->currency === 'CDF') ? 'required|numeric|min:0.0001' : 'nullable|numeric|min:0.0001';

        return [
            'visit.visit_date' => 'required|date',
            'visit.usd_cdf_rate' => $exchangeRateRule,
            'businessProfile.activity' => 'required|string|max:255',
            'businessProfile.full_address' => 'required|string|max:255',
            'cashflow.retained_sales' => 'required|numeric|min:0.01',
            'cashflow.retained_purchases' => 'required|numeric|min:0',
            'cashflow.business_expenses_total' => 'required|numeric|min:0',
            'cashflow.household_expenses_total' => 'required|numeric|min:0',
            'balance.cash' => 'required|numeric|min:0',
            'balance.stock' => 'required|numeric|min:0',
            'balance.equity' => 'required|numeric|min:0.01',
            'proposal.final_conclusions' => 'required|string|min:10',
            'proposal.proposed_amount' => 'required|numeric|min:1',
            'proposal.proposed_rate' => 'required|numeric|min:0.01',
            'proposal.proposed_maturity_months' => 'required|integer|min:1',
            'proposal.grace_period_months' => 'nullable|integer|min:0',
            'familyMembers.*.name' => 'nullable|string|max:255',
            'householdReferences.*.name' => 'nullable|string|max:255',
            'investmentPlanItems.*.amount' => 'nullable|numeric|min:0',
            'businessExpenses.*.amount' => 'nullable|numeric|min:0',
            'householdExpenses.*.amount' => 'nullable|numeric|min:0',
            'inventoryItems.*.amount' => 'nullable|numeric|min:0',
            'securities.*.valeur_estimee' => 'nullable|numeric|min:0',
        ];
    }

    protected function messages(): array
    {
        return [
            'visit.visit_date.required' => 'La date de visite est obligatoire.',
            'visit.usd_cdf_rate.required' => 'Le taux USD/CDF est obligatoire pour une analyse en CDF.',
            'businessProfile.activity.required' => 'L activite est obligatoire.',
            'businessProfile.full_address.required' => 'L adresse de l entreprise est obligatoire.',
            'cashflow.retained_sales.min' => 'Les ventes retenues doivent etre superieures a zero pour calculer les ratios.',
            'balance.equity.min' => 'Les fonds propres doivent etre superieurs a zero pour eviter des ratios non exploitables.',
            'proposal.final_conclusions.required' => 'La conclusion de l agent est obligatoire.',
            'proposal.proposed_rate.min' => 'Le taux propose doit etre superieur a zero.',
            'proposal.proposed_maturity_months.min' => 'La maturite doit etre superieure a zero.',
        ];
    }

    protected function loadForm(): void
    {
        $this->visit = $this->row($this->loan->fieldVisit, [
            'visit_date' => now()->toDateString(),
            'usd_cdf_rate' => null,
            'credit_number' => (string) $this->loan->id,
            'origin_province' => null,
            'education_level' => null,
            'religion' => null,
            'quick_biography' => null,
            'housing_type' => null,
            'housing_value' => null,
            'residence_duration' => null,
            'monthly_rent' => null,
            'rent_paid_in_advance' => null,
            'home_directions' => null,
            'household_impressions' => null,
        ]);

        $this->businessProfile = $this->row($this->loan->businessProfile, [
            'business_name' => $this->loan->business?->type_activite,
            'activity' => $this->loan->business?->type_activite,
            'full_address' => $this->loan->business?->localisation,
            'started_at' => null,
            'employees_count' => null,
            'business_margin_percent' => null,
            'business_history' => null,
            'qualitative_observations' => null,
            'purchase_sales_competition_comments' => null,
            'business_location_notes' => null,
            'household_location_notes' => null,
        ]);

        $this->balance = $this->row($this->loan->balanceSheetDetail, [
            'cash' => 0,
            'bank' => 0,
            'savings' => 0,
            'receivables' => 0,
            'supplier_advances' => 0,
            'stock' => 0,
            'machines_tools' => 0,
            'transport_assets' => 0,
            'buildings_land' => 0,
            'supplier_debts' => 0,
            'current_customer_credit' => 0,
            'short_term_debt' => 0,
            'long_term_debt' => 0,
            'equity' => 0,
            'comments' => null,
        ]);

        $this->cashflow = $this->row($this->loan->cashflowAnalysis, [
            'cash_sales' => 0,
            'credit_sales' => 0,
            'retained_sales' => 0,
            'retained_purchases' => 0,
            'business_expenses_total' => 0,
            'household_income' => 0,
            'household_expenses_total' => 0,
            'household_income_source' => null,
            'household_income_periodicity' => null,
            'comments' => null,
        ]);

        $this->collateralProperty = $this->row($this->loan->collateralProperty, [
            'owner_name' => null,
            'document_type' => null,
            'market_value' => null,
            'address_references' => null,
        ]);

        $this->coBorrower = $this->row($this->loan->coBorrower, [
            'name' => null,
            'postnom_prenom' => null,
            'phone' => null,
            'identity_document' => null,
            'occupation' => null,
            'income' => null,
            'address' => null,
            'relationship' => null,
        ]);

        $this->proposal = $this->row($this->loan->agentProposal, [
            'final_conclusions' => null,
            'proposed_amount' => $this->loan->montant_demande,
            'proposed_rate' => null,
            'proposed_maturity_months' => $this->loan->duree_mois,
            'grace_period_months' => 0,
            'repayment_modality' => null,
            'irregular_payment_explanation' => null,
        ]);

        $this->familyMembers = $this->rows($this->loan->familyMembers, ['name' => null, 'relationship' => null, 'occupation' => null, 'observations' => null], 3);
        $this->householdReferences = $this->rows($this->loan->householdReferences, ['name' => null, 'address' => null, 'phone' => null, 'reference_type' => 'autre'], 2);
        $this->investmentPlanItems = $this->rows($this->loan->investmentPlanItems, ['destination' => null, 'amount' => null, 'starts_on' => null, 'ends_on' => null, 'client_share' => null, 'institution_share' => null, 'third_party_share' => null], 3);
        $this->creditHistories = $this->rows($this->loan->creditHistories, ['institution' => null, 'amount' => null, 'status' => null, 'observations' => null], 2);
        $this->businessExpenses = $this->expenseRows('business');
        $this->householdExpenses = $this->expenseRows('household');
        $this->inventoryItems = $this->rows($this->loan->inventoryItems, ['section' => 'stock', 'description' => null, 'purchase_price' => null, 'sale_price' => null, 'quantity' => null, 'amount' => null, 'observations' => null], 3);
        $this->securities = $this->rows($this->loan->securities, ['type' => null, 'nature_bien' => null, 'description' => null, 'valeur_estimee' => null, 'proprietaire' => null], 3);
    }

    protected function calculatedBalance(): array
    {
        $totalAssets = $this->number('balance.cash') + $this->number('balance.bank') + $this->number('balance.savings')
            + $this->number('balance.receivables') + $this->number('balance.supplier_advances') + $this->number('balance.stock')
            + $this->number('balance.machines_tools') + $this->number('balance.transport_assets') + $this->number('balance.buildings_land');
        $totalDebts = $this->number('balance.supplier_debts') + $this->number('balance.current_customer_credit')
            + $this->number('balance.short_term_debt') + $this->number('balance.long_term_debt');

        return [
            'total_assets' => $totalAssets,
            'total_debts' => $totalDebts,
            'total_liabilities_equity' => $totalDebts + $this->number('balance.equity'),
        ];
    }

    protected function calculatedCashflow(): array
    {
        $grossMargin = $this->number('cashflow.retained_sales') - $this->number('cashflow.retained_purchases');
        //$safetyMargin = $this->number('cashflow.household_expenses_total') * 0.1;
        $availableIncome = $grossMargin - $this->number('cashflow.business_expenses_total')
            + $this->number('cashflow.household_income') - $this->number('cashflow.household_expenses_total'); // - $safetyMargin;

        return [
            'gross_margin' => $grossMargin,
            //'household_safety_margin' => $safetyMargin,
            'available_income' => $availableIncome,
            'repayment_capacity' => max(0, $availableIncome * 0.65),
        ];
    }

    protected function syncLegacyAnalysisTables(array $balance, array $cashflow): void
    {
        LoanCashflow::updateOrCreate(
            ['loan_application_id' => $this->loan->id],
            [
                'type_activite' => $this->businessProfile['activity'],
                'chiffre_affaires_mensuel_estime' => $this->cashflow['retained_sales'],
                'camv_ou_achats_mensuels' => $this->cashflow['retained_purchases'],
                'charges_activite_mensuelles' => $this->cashflow['business_expenses_total'],
                'autres_revenus_mensuels' => $this->cashflow['household_income'] ?? 0,
                'charges_menage_mensuelles' => ($this->cashflow['household_expenses_total'] ?? 0) + ($cashflow['household_safety_margin'] ?? 0),
                'revenu_disponible_mensuel' => $cashflow['available_income'],
                'capacite_remboursement_mensuelle' => $cashflow['repayment_capacity'],
                'date_calcul' => now(),
            ]
        );

        LoanBalance::updateOrCreate(
            ['loan_application_id' => $this->loan->id],
            [
                'cash' => $this->number('balance.cash') + $this->number('balance.bank') + $this->number('balance.savings'),
                'creances' => $this->number('balance.receivables'),
                'stock' => $this->number('balance.stock'),
                'actifs_immobilises' => $this->number('balance.machines_tools') + $this->number('balance.transport_assets') + $this->number('balance.buildings_land'),
                'dettes_formelles_ct' => $this->number('balance.supplier_debts') + $this->number('balance.current_customer_credit') + $this->number('balance.short_term_debt'),
                'dettes_formelles_mt' => 0,
                'dettes_formelles_lt' => $this->number('balance.long_term_debt'),
                'dettes_informelles_ct' => 0,
                'dettes_informelles_mt' => 0,
                'dettes_informelles_lt' => 0,
                'fonds_propres' => $this->number('balance.equity'),
                'total_actif' => $balance['total_assets'],
                'total_dettes' => $balance['total_debts'],
                'total_passif' => $balance['total_liabilities_equity'],
                'date_calcul' => now(),
            ]
        );
    }

    protected function replaceRows(string $model, array $rows, array $requiredKeys): void
    {
        $model::where('loan_application_id', $this->loan->id)->delete();

        foreach ($rows as $row) {
            if (!$this->hasRequiredData($row, $requiredKeys)) {
                continue;
            }

            $data = Arr::except($row, ['id', 'created_at', 'updated_at', 'loan_application_id']);

            // Clean specific numeric fields if they exist
            foreach (['amount', 'purchase_price', 'sale_price', 'quantity', 'client_share', 'institution_share', 'third_party_share', 'valeur_estimee', 'income'] as $numKey) {
                if (array_key_exists($numKey, $data)) {
                    $data[$numKey] = $this->cleanNumber($data[$numKey]);
                }
            }

            $model::create(array_merge(
                ['loan_application_id' => $this->loan->id],
                $this->moneyData($data)
            ));
        }
    }

    protected function replaceExpenseRows(string $section, array $rows): void
    {
        LoanExpenseLine::where('loan_application_id', $this->loan->id)->where('section', $section)->delete();

        foreach ($rows as $row) {
            if (!filled($row['label'] ?? null)) {
                continue;
            }

            LoanExpenseLine::create([
                'loan_application_id' => $this->loan->id,
                'section' => $section,
                'label' => $row['label'],
                'amount' => $this->cleanNumber($row['amount'] ?? 0),
            ]);
        }
    }

    protected function rows($collection, array $defaults, int $minimum): array
    {
        $rows = $collection->map(fn ($row) => array_merge($defaults, $this->normalizeModelDates(Arr::except($row->attributesToArray(), ['created_at', 'updated_at']))))->values()->toArray();

        while (count($rows) < $minimum) {
            $rows[] = $defaults;
        }

        return $rows;
    }

    protected function row($model, array $defaults): array
    {
        if (!$model) {
            return $defaults;
        }

        return array_merge($defaults, $this->normalizeModelDates(Arr::only($model->attributesToArray(), array_keys($defaults))));
    }

    protected function normalizeModelDates(array $data): array
    {
        return collect($data)->map(function ($value) {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d');
            }

            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:?\d{2})?)?$/', $value)) {
                return \Carbon\Carbon::parse($value)->format('Y-m-d');
            }

            return $value;
        })->toArray();
    }

    protected function expenseRows(string $section): array
    {
        $defaults = $section === 'business'
            ? ['Loyer', 'Personnel', 'Transport', 'Communication', 'Autres charges']
            : ['Loyer', 'Nourriture', 'Education', 'Transport'];

        $existing = $this->loan->expenseLines->where('section', $section)->map(fn ($row) => [
            'label' => $row->label,
            'amount' => $row->amount,
        ])->values()->toArray();

        return $existing ?: collect($defaults)->map(fn ($label) => ['label' => $label, 'amount' => 0])->toArray();
    }

    protected function hasRequiredData(array $row, array $keys): bool
    {
        foreach ($keys as $key) {
            if (!filled($row[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    protected function moneyData(array $data): array
    {
        return collect($data)->map(fn ($value) => is_string($value) && is_numeric(str_replace(',', '.', $value))
            ? $this->cleanNumber($value)
            : $value
        )->toArray();
    }

    protected function number(string $key): float
    {
        return $this->cleanNumber(data_get($this, $key, 0));
    }

    protected function cleanNumber($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', (string) $value);
    }

    // Dynamic row addition and removal methods
    public function addFamilyMember(): void
    {
        $this->familyMembers[] = ['name' => null, 'relationship' => null, 'occupation' => null, 'observations' => null];
    }

    public function removeFamilyMember(int $index): void
    {
        unset($this->familyMembers[$index]);
        $this->familyMembers = array_values($this->familyMembers);
    }

    public function addHouseholdReference(): void
    {
        $this->householdReferences[] = ['name' => null, 'address' => null, 'phone' => null, 'reference_type' => 'autre'];
    }

    public function removeHouseholdReference(int $index): void
    {
        unset($this->householdReferences[$index]);
        $this->householdReferences = array_values($this->householdReferences);
    }

    public function addInvestmentPlanItem(): void
    {
        $this->investmentPlanItems[] = ['destination' => null, 'amount' => null, 'starts_on' => null, 'ends_on' => null, 'client_share' => null, 'institution_share' => null, 'third_party_share' => null];
    }

    public function removeInvestmentPlanItem(int $index): void
    {
        unset($this->investmentPlanItems[$index]);
        $this->investmentPlanItems = array_values($this->investmentPlanItems);
    }

    public function addCreditHistory(): void
    {
        $this->creditHistories[] = ['institution' => null, 'amount' => null, 'status' => null, 'observations' => null];
    }

    public function removeCreditHistory(int $index): void
    {
        unset($this->creditHistories[$index]);
        $this->creditHistories = array_values($this->creditHistories);
    }

    public function addInventoryItem(): void
    {
        $this->inventoryItems[] = ['section' => 'stock', 'description' => null, 'purchase_price' => null, 'sale_price' => null, 'quantity' => null, 'amount' => null, 'observations' => null];
    }

    public function removeInventoryItem(int $index): void
    {
        unset($this->inventoryItems[$index]);
        $this->inventoryItems = array_values($this->inventoryItems);
    }

    public function addSecurity(): void
    {
        $this->securities[] = ['type' => null, 'nature_bien' => null, 'description' => null, 'valeur_estimee' => null, 'proprietaire' => null];
    }

    public function removeSecurity(int $index): void
    {
        unset($this->securities[$index]);
        $this->securities = array_values($this->securities);
    }

    public function render()
    {
        return view('livewire.credit.loan-field-analysis-form', [
            'balanceTotals' => $this->calculatedBalance(),
            'cashflowTotals' => $this->calculatedCashflow(),
        ]);
    }
}
