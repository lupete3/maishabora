<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanDecision extends Model
{
    protected $fillable = [
        'loan_application_id',
        'note_caractere',
        'note_capacite',
        'note_capital',
        'note_caution',
        'note_caracteristiques_financieres',
        'commentaire_global',
        'decision_finale',
        'user_id'
    ];

    public function loanApplication()
    {
        return $this->belongsTo(LoanApplication::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
