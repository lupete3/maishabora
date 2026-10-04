<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TermDepositProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'term_days',
        'term_months',
        'annual_interest_rate',
        'early_withdrawal_penalty_rate',
        'early_withdrawal_interest_policy',
        'minimum_amount',
        'maximum_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'annual_interest_rate' => 'decimal:4',
            'term_months' => 'integer',
            'early_withdrawal_penalty_rate' => 'decimal:4',
            'minimum_amount' => 'decimal:2',
            'maximum_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function termDeposits(): HasMany
    {
        return $this->hasMany(TermDeposit::class);
    }

}