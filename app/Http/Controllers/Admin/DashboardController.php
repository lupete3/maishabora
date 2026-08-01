<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        if (Auth::user()->isActive()) {
            return view('dashboard');
        } else {
           return view('not-found');
        }
    }

    public function rapportLogs()
    {
        return view('rapport-logs');
    }

    public function userSessions()
    {
        return view('user-sessions');
    }

    public function notifications()
    {
        return view('notifications');
    }

}
