<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cloture extends Model
{
    protected $fillable = [
        'user_id',
        'closing_date',
        'logical_usd',
        'logical_cdf',
        'physical_usd',
        'physical_cdf',
        'gap_usd',
        'gap_cdf',
        'validated_by',
        'validated_at',
        'note',
        'status', // pending, validated, rejected
        'rejection_reason',
    ];

    protected $casts = [
        'closing_date'   => 'date',
        'validated_at'   => 'datetime',
        'logical_usd'    => 'decimal:2',
        'logical_cdf'    => 'decimal:2',
        'physical_usd'   => 'decimal:2',
        'physical_cdf'   => 'decimal:2',
        'gap_usd'        => 'decimal:2',
        'gap_cdf'        => 'decimal:2',
        'billetage_usd' => 'array',
        'billetage_cdf' => 'array',
    ];

    /**
     * L'agent propriétaire de la clôture.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * L'utilisateur qui a validé la clôture.
     */
    public function validatedBy() {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Les détails de billetage liés à cette clôture.
     */
    public function billetages()
    {
        return $this->hasMany(Billetage::class);
    }
}
