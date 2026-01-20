<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisbursementRequest extends Model
{
    protected $fillable = [
        'user_id',
        'disbursement_type_id',
        'amount',
        'currency',
        'description',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'transaction_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function disbursementType()
    {
        return $this->belongsTo(DisbursementType::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
