<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DisbursementController extends Controller
{
    public function index()
    {
        return view('disbursement');
    }

    public function approval()
    {
        return view('disbursement_approval');
    }
}
