<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SellMembershipCardController extends Controller
{
    public function index()
    {
        return view('sell-membership-card');
    }
}
