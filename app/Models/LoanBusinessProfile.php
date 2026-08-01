<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanBusinessProfile extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'started_at' => 'date',
    ];
}
