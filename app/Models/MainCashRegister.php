<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainCashRegister extends Model
{
    /** @use HasFactory<\Database\Factories\MainCashRegisterFactory> */
    use HasFactory;

    protected $fillable = ['currency', 'balance'];

    public function transfers()
    {
        return $this->hasMany(Transfert::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public static function getByCurrency($currency)
    {
        return static::where('currency', $currency)->firstOrFail();
    }
}
