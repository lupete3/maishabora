<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipCardInventory extends Model
{
    protected $fillable = [
        'inventory_date',
        'agent_id',
        'agent_name',
        'created_by',
        'status',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'inventory_date' => 'date',
            'finalized_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MembershipCardInventoryItem::class, 'inventory_id');
    }
}
