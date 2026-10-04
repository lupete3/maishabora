<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TermDeposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'term_deposit_product_id',
        'user_id',
        'source_account_id',
        'settlement_account_id',
        'renewed_from_id',
        'currency',
        'principal_amount',
        'term_days',
        'term_months',
        'annual_interest_rate',
        'accrued_interest_amount',
        'last_interest_calculated_at',
        'early_withdrawal_penalty_rate',
        'early_withdrawal_interest_policy',
        'opened_at',
        'matures_at',
        'closed_at',
        'status',
        'interest_amount',
        'penalty_amount',
        'payout_amount',
        'settlement_reference',
        'opened_by',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'term_months' => 'integer',
            'annual_interest_rate' => 'decimal:4',
            'accrued_interest_amount' => 'decimal:4',
            'early_withdrawal_penalty_rate' => 'decimal:4',
            'interest_amount' => 'decimal:2',
            'penalty_amount' => 'decimal:2',
            'payout_amount' => 'decimal:2',
            'opened_at' => 'date',
            'matures_at' => 'date',
            'last_interest_calculated_at' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(TermDepositProduct::class, 'term_deposit_product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'source_account_id');
    }

    public function settlementAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'settlement_account_id');
    }

    public function renewedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewed_from_id');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(self::class, 'renewed_from_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(TermDepositTransaction::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }
}