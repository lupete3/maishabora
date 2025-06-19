<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CreditFollowUpReportController extends Controller
{
    public function index()
    {
        return view('repport-credits');
    }
}
