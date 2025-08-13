<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AgentTransactionsReportController extends Controller
{
    public function rapportTransactions()
    {
        return view('rapports.transactions');
    }

    public function rapportDepotRetrait()
    {
        return view('rapports.depot_retrait');
    }

    
}
