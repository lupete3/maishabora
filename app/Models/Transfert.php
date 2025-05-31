<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfert extends Model
{
    /** @use HasFactory<\Database\Factories\TransfertFactory> */
    use HasFactory;

    protected $fillable = ['from_agent_account_id', 'to_main_cash_register_id', 'currency', 'amount'];

    public function fromAgentAccount()
    {
        return $this->belongsTo(AgentAccount::class);
    }

    public function toMainCashRegister()
    {
        return $this->belongsTo(MainCashRegister::class);
    }
}
