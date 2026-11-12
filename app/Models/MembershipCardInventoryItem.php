<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipCardInventoryItem extends Model
{
    protected $fillable = [
        'membership_card_id',
        'card_code',
        'member_code',
        'member_name',
        'currency',
        'subscription_amount',
        'paid_contributions_count',
        'theoretical_total',
        'physical_total',
        'difference',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'paid_contributions_count' => 'integer',
            'subscription_amount' => 'decimal:2',
            'theoretical_total' => 'decimal:2',
            'physical_total' => 'decimal:2',
            'difference' => 'decimal:2',
        ];
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(MembershipCardInventory::class, 'inventory_id');
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(MembershipCard::class, 'membership_card_id');
    }
}
