<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanFinancialRatio extends Model
{
    protected $fillable = [
        'loan_application_id',
        'fonds_roulement',
        'independance_financiere',
        'liquidite_generale',
        'rotation_stock',
        'creances_sur_ventes',
        'profitabilite_nette',
        'solvabilite',
        'ventes_mensuelles',
        'benefice_net_mensuel',
        'date_calcul'
    ];

    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class);
    }
}
