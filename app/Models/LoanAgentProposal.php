<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanAgentProposal extends Model
{
    protected $guarded = ['id'];

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
