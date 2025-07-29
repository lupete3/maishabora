<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repayment extends Model
{
    /** @use HasFactory<\Database\Factories\RepaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'credit_id', 'due_date', 'paid_date',
        'expected_amount', 'penalty', 'total_due',
        'paid_amount', 'is_paid'
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }
}
