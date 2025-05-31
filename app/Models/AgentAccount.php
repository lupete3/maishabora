<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentAccount extends Model
{
    /** @use HasFactory<\Database\Factories\AgentAccountFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'currency', 'balance', 'missing_or_surplus'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function transfers()
    {
        return $this->hasMany(Transfert::class);
    }
}
