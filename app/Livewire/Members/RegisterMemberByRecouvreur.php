<?php

namespace App\Livewire\Members;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\WithPagination;

class RegisterMemberByRecouvreur extends Component
{
    use WithPagination;

    public $name = '';
    public $postnom = '';
    public $prenom = '';
    public $date_naissance = '';
    public $telephone = '';
    public $adresse_physique = '';
    public $profession = '';
    public $email = '';

    public $perPage = 10;
    public $search = '';

    protected $listeners = ['userUpdated' => 'refreshList'];

    public function mount()
    {
        $this->fill(request()->only('search'));
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    protected $rules = [
        'name' => 'required|string|max:255',
        'postnom' => 'required|string|max:255',
        'prenom' => 'nullable|string|max:255',
        'date_naissance' => 'required|date',
        'telephone' => 'required|string|max:20',
        'adresse_physique' => 'nullable|string',
        'profession' => 'nullable|string',
        'email' => 'required|email|unique:users,email',
    ];

    public function register()
    {
        $this->validate();

        // Génère un mot de passe temporaire
        $password = 'password'; // tu peux aussi générer un mot de passe aléatoire

        User::create([
            'name' => $this->name,
            'postnom' => $this->postnom,
            'prenom' => $this->prenom,
            'date_naissance' => $this->date_naissance,
            'telephone' => $this->telephone,
            'adresse_physique' => $this->adresse_physique,
            'profession' => $this->profession,
            'email' => $this->email,
            'password' => Hash::make($password),
            'role' => 'membre', // Toujours membre
            'status' => false,
        ]);

        notyf()->success("Membre enregistré avec succès !");
        $this->reset(['name', 'postnom', 'prenom', 'date_naissance', 'telephone', 'adresse_physique', 'profession', 'email']);
    }

    public function render()
    {
        $users = User::where(function ($query) {
                $query->where('name', 'like', "%$this->search%")
                    ->orWhere('postnom', 'like', "%$this->search%")
                    ->orWhere('email', 'like', "%$this->search%");
            })
            ->paginate($this->perPage);

        return view('livewire.members.register-member-by-recouvreur', ['users' => $users]);
    }
}
