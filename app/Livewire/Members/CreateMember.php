<?php

namespace App\Livewire\Members;

use Livewire\Component;
use App\Models\Member;

class CreateMember extends Component
{
    public $nom;
    public $postnom;
    public $prenom;
    public $date_naissance;
    public $telephone;
    public $email;
    public $adresse_physique;
    public $profession;

    protected $rules = [
        'nom' => 'required|string|max:255',
        'postnom' => 'required|string|max:255',
        'prenom' => 'nullable|string|max:255',
        'date_naissance' => 'required|date',
        'telephone' => 'required|string|max:20',
        'email' => 'required|email',
        'adresse_physique' => 'required|string',
        'profession' => 'nullable|string',
    ];

    public function submit()
    {
        $this->validate();

        Member::create([
            'nom' => $this->nom,
            'postnom' => $this->postnom,
            'prenom' => $this->prenom,
            'date_naissance' => $this->date_naissance,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'adresse_physique' => $this->adresse_physique,
            'profession' => $this->profession,
        ]);

        session()->flash('message', 'Membre enregistré avec succès.');

        // Réinitialise les champs
        $this->reset(['nom', 'postnom', 'prenom', 'date_naissance', 'telephone', 'email', 'adresse_physique', 'profession']);
    }

    public function render()
    {
        return view('livewire.members.create-member');
    }
}
