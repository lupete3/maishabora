<?php

namespace App\Http\Controllers;

class ReportAIController extends Controller
{

    public function index()
    {
        return view('reports.daily-summary');
    }

}
