<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RepaymentReportController extends Controller
{
    public function index()
    {
        return view('reports.repayments');
    }
}
