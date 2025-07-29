<?php

namespace App\Helpers;

use App\Models\UserLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Jenssegers\Agent\Agent;

class UserLogHelper
{
    public static function log_user_activity($action, $description = null)
    {
        $agent = new Agent();
        $user = Auth::user();

        if (!$user) return; // pour éviter une erreur si non connecté

        UserLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'description' => $description,
            'device' => $agent->platform() . ' ' . $agent->version($agent->platform()) . ' | ' . $agent->browser() . ' ' . $agent->version($agent->browser()),
            'ip_address' => Request::ip(),
        ]);
    }
}
