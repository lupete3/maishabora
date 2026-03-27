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
}
