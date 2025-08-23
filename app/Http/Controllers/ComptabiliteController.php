<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ComptabiliteController extends Controller
{
    public function index()
    {
        return view('comptabilite.comptes');
    }

    public function typeJournal()
    {
        return view('comptabilite.type_journal');
    }

    public function journals()
    {
        return view('comptabilite.journals');
    }

    public function balanceGenerale()
    {
        return view('comptabilite.balance_generale');
    }

    public function resultats()
    {
        return view('comptabilite.resultats');
    }

    
}
