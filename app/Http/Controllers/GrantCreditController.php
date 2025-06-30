<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GrantCreditController extends Controller
{
    public function index()
    {

        Gate::authorize('ajouter-credit', User::class);

        return view('credit');
    }
}
