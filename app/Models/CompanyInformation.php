<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyInformation extends Model
{
    protected $table = 'company_informations';

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'rccm',
        'ifu',
        'logo_path',
        'currency',
        'currency_symbol',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function getActive()
    {
        return static::where('is_active', true)->first();
    }

    public static function getActiveOrDefault()
    {
        return static::where('is_active', true)->first() ?? new static([
            'name' => config('app.name'),
            'address' => config('app.name'),
            'phone' => config('app.name'),
            'email' => config('app.name'),
            'rccm' => config('app.name'),
            'ifu' => config('app.name'),
        ]);
    }
}