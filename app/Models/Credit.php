<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    /** @use HasFactory<\Database\Factories\CreditFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'account_id', 'currency', 'amount',
        'interest_rate', 'installments', 'start_date',
        'due_date', 'is_paid'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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
