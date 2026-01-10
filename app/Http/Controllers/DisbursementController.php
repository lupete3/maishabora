<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DisbursementController extends Controller
{
    public function index()
    {
        return view('disbursement');
    }
}
