<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ClientsNonCollectes extends Controller
{
    public function index()
    {
        return view('client-non-collecte');
    }
}
