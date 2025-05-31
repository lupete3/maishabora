<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DepositForMemberController extends Controller
{
    public function index()
    {
        return view("deposit-member");
    }
}
