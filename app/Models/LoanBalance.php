<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanBalance extends Model
{
    protected $fillable = [
        'loan_application_id',
        'cash',
        'creances',
        'stock',
        'actifs_immobilises',
        'dettes_formelles_ct',
        'dettes_formelles_mt',
        'dettes_formelles_lt',
        'dettes_informelles_ct',
        'dettes_informelles_mt',
        'dettes_informelles_lt',
        'fonds_propres',
        'total_actif',
        'total_dettes',
        'total_passif',
        'date_calcul'
    ];

    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class);
    }
}
