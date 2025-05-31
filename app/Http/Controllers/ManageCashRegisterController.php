<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ManageCashRegisterController extends Controller
{
    public function index()
    {
        return view("admin.manage-cash-register");
    }
}
