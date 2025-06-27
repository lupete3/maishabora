<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MembershipCardController extends Controller
{
    public function index()
    {
        return view('membershipcard');
    }
    public function depot()
    {
        return view('depositmembershipcard');
    }

    public function withdrawfromcard()
    {
        return view('withdrawfromcard');
    }
}
