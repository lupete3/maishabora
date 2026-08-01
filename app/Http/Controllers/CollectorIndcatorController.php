<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CollectorIndcatorController extends Controller
{
    public function index()
    {
        return view('comptabilite.collectorindicator');
    }
}
