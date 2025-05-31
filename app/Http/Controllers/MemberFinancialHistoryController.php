<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MemberFinancialHistoryController extends Controller
{
    public function index()
    {
        return view('member-financial-history');
    }

}
