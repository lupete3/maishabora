<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Billetage extends Model
{
    protected $fillable = [
        'cloture_id',
        'currency',
        'denomination',
        'quantity',
        'total',
    ];

    protected $casts = [
        'denomination' => 'decimal:2',
        'total'        => 'decimal:2',
    ];

    /**
     * La clôture associée à ce billetage.
     */
    public function cloture()
    {
        return $this->belongsTo(Cloture::class);
    }
}
