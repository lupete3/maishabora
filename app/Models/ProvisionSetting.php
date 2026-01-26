<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProvisionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'classification',
        'rate',
        'description',
    ];

    protected $casts = [
        'rate' => 'float',
    ];
}
