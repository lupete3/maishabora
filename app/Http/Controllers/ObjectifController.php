<?php

namespace App\Http\Controllers;

/**
 * Module Objectifs : interface dédiée, séparée du tableau de bord des caisses agents.
 * La logique est portée par les composants Livewire App\Livewire\Objectifs\*.
 */
class ObjectifController extends Controller
{
    /** Suivi des objectifs et progressions (global entreprise + par agent). */
    public function suivi()
    {
        return view('objectifs.suivi');
    }

    /** Fixation des objectifs et paramétrage des indicateurs. */
    public function configuration()
    {
        return view('objectifs.configuration');
    }
}
