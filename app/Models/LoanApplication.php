<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanApplication extends Model
{
    protected $fillable = [
        'user_id',
        'business_id',
        'currency',
        'montant_demande',
        'duree_mois',
        'produit_credit_id',
        'date_demande',
        'statut',
        'agent_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function business()
    {
        return $this->belongsTo(Business::class);
    }
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function cashflow()
    {
        return $this->hasOne(LoanCashflow::class);
    }
    public function balance()
    {
        return $this->hasOne(LoanBalance::class);
    }
    public function ratios()
    {
        return $this->hasOne(LoanFinancialRatio::class);
    }
    public function securities()
    {
        return $this->hasMany(Security::class);
    }
    public function decision()
    {
        return $this->hasOne(LoanDecision::class);
    }

    public function fieldVisit()
    {
        return $this->hasOne(LoanFieldVisit::class);
    }

    public function familyMembers()
    {
        return $this->hasMany(LoanFamilyMember::class);
    }

    public function householdReferences()
    {
        return $this->hasMany(LoanHouseholdReference::class);
    }

    public function businessProfile()
    {
        return $this->hasOne(LoanBusinessProfile::class);
    }

    public function investmentPlanItems()
    {
        return $this->hasMany(LoanInvestmentPlanItem::class);
    }

    public function creditHistories()
    {
        return $this->hasMany(LoanCreditHistory::class);
    }

    public function balanceSheetDetail()
    {
        return $this->hasOne(LoanBalanceSheetDetail::class);
    }

    public function cashflowAnalysis()
    {
        return $this->hasOne(LoanCashflowAnalysis::class);
    }

    public function expenseLines()
    {
        return $this->hasMany(LoanExpenseLine::class);
    }

    public function inventoryItems()
    {
        return $this->hasMany(LoanInventoryItem::class);
    }

    public function collateralProperty()
    {
        return $this->hasOne(LoanCollateralProperty::class);
    }

    public function coBorrower()
    {
        return $this->hasOne(LoanCoBorrower::class);
    }

    public function agentProposal()
    {
        return $this->hasOne(LoanAgentProposal::class);
    }
}
