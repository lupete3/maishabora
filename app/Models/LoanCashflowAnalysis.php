<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanCashflowAnalysis extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'calculated_at' => 'date',
    ];
}
