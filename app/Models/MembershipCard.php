<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipCard extends Model
{
    /** @use HasFactory<\Database\Factories\MembershipCardFactory> */
    use HasFactory;

    protected $fillable = ['code', 'member_id', 'currency', 'price', 'subscription_amount', 'start_date', 'end_date', 'is_active'];

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function contributions()
    {
        return $this->hasMany(DailyContribution::class);
    }

    public function getRemainingDaysAttribute()
    {
        return max(0, now()->diffInDays($this->end_date));
    }

    public function getTotalSavedAttribute()
    {
        return $this->contributions->where('is_paid', true)->sum('amount');
    }

    public function getUnpaidContributionsAttribute()
    {
        return $this->contributions->where('is_paid', false);
    }
}
