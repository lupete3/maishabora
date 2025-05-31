<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipCard extends Model
{
    /** @use HasFactory<\Database\Factories\MembershipCardFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'prix',
        'vendu_a'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
