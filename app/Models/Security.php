<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Security extends Model
{
    protected $fillable = [
        'loan_application_id',
        'type',
        'description',
        'valeur_estimee',
        'nature_bien',
        'proprietaire'
    ];

    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class);
    }
}
