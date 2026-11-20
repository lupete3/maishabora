<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MemberDetailsController extends Controller
{
    public function index(Request $request)
    {
        $id = $request->id;
        return view("member-details", compact("id"));
    }

    public function comptes()
    {
        return view("rapports.comptes-clients");
    }

    /** Classement des titulaires de comptes (client, interne, test). */
    public function classementTitulaires()
    {
        return view('rapports.classement-titulaires');
    }
}
