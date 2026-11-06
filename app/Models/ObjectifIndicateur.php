<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Paramétrage d'un indicateur du catalogue (activation, ordre d'affichage).
 * Un indicateur sans ligne ici est considéré comme actif.
 */
class ObjectifIndicateur extends Model
{
    protected $fillable = ['key', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];
}
