<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanCashflow extends Model
{
    protected $fillable = [
        'loan_application_id',
        'type_activite',
        'chiffre_affaires_mensuel_estime',
        'camv_ou_achats_mensuels',
        'charges_activite_mensuelles',
        'autres_revenus_mensuels',
        'charges_menage_mensuelles',
        'revenu_disponible_mensuel',
        'capacite_remboursement_mensuelle',
        'date_calcul'
    ];

    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class);
    }
}
