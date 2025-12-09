<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $fillable = [
        'user_id',
        'type_activite',
        'secteur',
        'date_debut',
        'localisation',
        'description'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function loanApplications()
    {
        return $this->hasMany(LoanApplication::class);
    }
}
