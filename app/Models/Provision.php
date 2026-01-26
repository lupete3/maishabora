<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provision extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_id',
        'classification',
        'provision_rate',
        'outstanding_amount',
        'provision_amount',
        'currency',
        'calculated_at',
        'notes',
    ];

    protected $casts = [
        'provision_rate' => 'float',
        'outstanding_amount' => 'float',
        'provision_amount' => 'float',
        'calculated_at' => 'date',
    ];

    /**
     * Crédit associé
     */
    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }
}
