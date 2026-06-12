<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanFieldVisit extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'visit_date' => 'date',
        'usd_cdf_rate' => 'decimal:4',
    ];
}
