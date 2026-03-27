<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EcartCaisse extends Model
{
    protected $table = 'ecarts_caisse';

    protected $fillable = [
        'cloture_id',
        'user_id',
        'type',
        'currency',
        'amount',
        'description',
        'status',
        'resolution_note',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];

    /**
     * La clôture liée à cet écart.
     */
    public function cloture()
    {
        return $this->belongsTo(Cloture::class);
    }

    /**
     * L'agent concerné par l'écart.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * L'utilisateur qui a résolu l'écart.
     */
    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
