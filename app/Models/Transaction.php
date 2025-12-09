<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'account_id', 'agent_account_id', 'user_id', 'credit_id',
        'type', 'currency', 'amount', 'balance_after', 'description'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function agentAccount()
    {
        return $this->belongsTo(AgentAccount::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    public function paired()
    {
        return $this->hasOne(Transaction::class, 'created_at', 'created_at')
                    ->where('type', 'conversion_entree')
                    ->where('user_id', $this->user_id);
    }
}
