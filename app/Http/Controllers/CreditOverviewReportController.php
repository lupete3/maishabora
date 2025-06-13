<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CreditOverviewReportController extends Controller
{
    public function index()
    {
        return view('credit-overview-report');
    }
}
