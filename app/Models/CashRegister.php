<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    /** @use HasFactory<\Database\Factories\CashRegisterFactory> */
    use HasFactory;

    protected $fillable = [
        'type_operation',
        'montant',
        'reference_type',
        'reference_id'
    ];

    public function reference()
    {
        return $this->morphTo();
    }

}
