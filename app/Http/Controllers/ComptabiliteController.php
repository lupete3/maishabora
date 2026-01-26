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

    public function grandLivre()
    {
        return view('comptabilite.grand_livre');
    }

    public function compteResultat()
    {
        return view('comptabilite.compte_resultat');
    }

    public function bilan()
    {
        return view('comptabilite.bilan');
    }

    public function provisions()
    {
        return view('comptabilite.provisions');
    }
}
