<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'montant_souscrit',
        'statut',
        'cree_a',
        'termine_a'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function contributionBooks()
    {
        return $this->hasOne(ContributionBook::class);
    }
}
