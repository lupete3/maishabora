<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContributionBook extends Model
{
    /** @use HasFactory<\Database\Factories\ContributionBookFactory> */
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'code',
        'taille',
        'verrouille',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function lines()
    {
        return $this->hasMany(ContributionLine::class);
    }

    public function isComplete()
    {
        return $this->lines()->where('montant', '>' , 0)->count() === $this->taille;
    }

    public function getTotalAmountAttribute()
    {
        return $this->lines()->where('montant', '>' , 0)->sum('montant');
    }
}
