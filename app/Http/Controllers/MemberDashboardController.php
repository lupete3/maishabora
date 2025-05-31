<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MemberDashboardController extends Controller
{
    public function index(Request $id)
    {
        return view('member-dashboard', compact('id'));
    }
}
