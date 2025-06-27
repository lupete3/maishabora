<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyContribution extends Model
{
    protected $fillable = ['membership_card_id', 'contribution_date', 'amount', 'is_paid'];

    public function card()
    {
        return $this->belongsTo(MembershipCard::class);
    }
}
