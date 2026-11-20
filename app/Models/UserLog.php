<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLog extends Model
{
    protected $fillable = ['user_id', 'action', 'device', 'ip_address', 'description'];

    /** Utilisateur à l'origine de l'action journalisée. */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
