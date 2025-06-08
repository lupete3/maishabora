<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ManageRepaymentsController extends Controller
{
    public function index()
    {
        return view('remoursement');
    }
}
