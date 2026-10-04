<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermDepositTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'term_deposit_id',
        'account_id',
        'performed_by',
        'type',
        'reference',
        'currency',
        'amount',
        'term_balance_after',
        'description',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'term_balance_after' => 'decimal:2',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function termDeposit(): BelongsTo
    {
        return $this->belongsTo(TermDeposit::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}