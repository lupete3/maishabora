<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = ['from_currency', 'to_currency', 'rate', 'applied_at'];

    // ✅ Fonction utilitaire pour récupérer le dernier taux
    public static function getLatestRate($from, $to)
    {
        return static::where('from_currency', $from)
            ->where('to_currency', $to)
            ->orderByDesc('created_at')
            ->first();
    }
}
