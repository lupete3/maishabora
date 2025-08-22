<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FundTransferController extends Controller
{
    public function index()
    {
        Gate::authorize('effectuer-virement', User::class);
        return view('transfert-compte');
    }
}
