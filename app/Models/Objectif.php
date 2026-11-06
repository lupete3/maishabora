<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Objectif à atteindre sur une période.
 * user_id NULL = objectif global de l'entreprise, sinon objectif propre à un agent.
 */
class Objectif extends Model
{
    protected $fillable = [
        'indicator',
        'user_id',
        'period_type',
        'period_start',
        'period_end',
        'currency',
        'target_value',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'target_value' => 'float',
    ];

    public function agent()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    public function scopeForAgents(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    public function scopeForPeriod(Builder $query, string $type, string $start): Builder
    {
        return $query->where('period_type', $type)->whereDate('period_start', $start);
    }

    /** Définition de l'indicateur dans config/objectifs.php */
    public function definition(): ?array
    {
        return config('objectifs.indicateurs.' . $this->indicator);
    }
}
