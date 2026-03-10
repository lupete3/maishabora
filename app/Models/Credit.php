<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agent_id',
        'account_id',
        'currency',
        'amount',
        'interest_rate',
        'installments',
        'start_date',
        'due_date',
        'frais_credit',
        'mutuelle',
        'credit_type',
        'repayment_type',
        'is_paid'
    ];

    // Membre bénéficiaire
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Agent qui gère ce crédit
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function repayments()
    {
        return $this->hasMany(Repayment::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
