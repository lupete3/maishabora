<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardStockMovement extends Model
{
    // Journal append-only : aucune action métier ne modifie ou supprime un mouvement.
    protected $guarded = ['id'];

    public function actor()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fromCollector()
    {
        return $this->belongsTo(User::class, 'from_collector_id');
    }

    public function toCollector()
    {
        return $this->belongsTo(User::class, 'to_collector_id');
    }
}
