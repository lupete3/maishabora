<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardStockItem extends Model
{
    protected $guarded = ['id'];

    public function batch()
    {
        return $this->belongsTo(CardBatch::class, 'card_batch_id');
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function sale()
    {
        return $this->hasOne(MembershipCard::class)->withoutGlobalScope('not_cancelled');
    }

    public function cancelledSales()
    {
        // Les ventes annulees restent accessibles apres reaffectation ou nouvelle vente.
        return $this->hasMany(MembershipCard::class, 'archived_card_stock_item_id')
            ->withoutGlobalScope('not_cancelled')->whereNotNull('cancelled_at');
    }

    public function movements()
    {
        return $this->hasMany(CardStockMovement::class);
    }

    public function getReferenceAttribute(): string
    {
        return $this->printed_number ?? (string) $this->id;
    }
}
