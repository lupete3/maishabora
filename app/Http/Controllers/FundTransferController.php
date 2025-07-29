<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FundTransferController extends Controller
{
    public function index()
    {
        // Gate::authorize('ajouter-credit', User::class);
        return view('transfert-compte');
    }
}
