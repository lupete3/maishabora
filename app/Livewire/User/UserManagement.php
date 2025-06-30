<?php

namespace App\Livewire\User;

use Livewire\Component;
use App\Models\User;
use App\Models\Account;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\Permission\Models\Role;
use Throwable;

class UserManagement extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public string $name = '';
    public string $postnom = '';
    public ?string $prenom = null;
    public ?string $date_naissance = null;
    public string $telephone = '';
    public ?string $adresse_physique = null;
    public ?string $profession = null;
    public string $email = '';
    public string|null $password = null;
    public $roles = [];

    public string $role = 'membre'; // Valeur par défaut
    public bool $status = false;
    public $search = '';
    public $perPage = 10; // Corrigé: généralement 10 par page
    public $editModal = false;
    public $userId;
    public $selectedMemberId = null;

    public function submit()
    {
        try {
            $validated = $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'postnom' => ['required', 'string', 'max:255'],
                'prenom' => ['nullable', 'string', 'max:255'],
                'date_naissance' => ['required', 'date'],
                'telephone' => [
                    'required',
                    'string',
                    'max:20',
                    'regex:/^\+243\d{9}$/',
                    Rule::unique('users')->where(function ($query) {
                        return $query->where('name', $this->name)
                                    ->where('postnom', $this->postnom)
                                    ->where('telephone', $this->telephone);
                    }),
                ],
                'adresse_physique' => ['nullable', 'string'],
                'profession' => ['nullable', 'string'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
                'role' => ['nullable', 'in:admin,caissier,recouvreur,membre'],
                'status' => ['required','in:0,1'],
            ],[
                'name.required' => 'Le nom est obligatoire.',
                'postnom.required' => 'Le post-nom est obligatoire.',
                'prenom.string' => 'Le prénom doit être une chaîne de caractères.',
                'date_naissance.required' => 'La date de naissance est obligatoire.',
                'date_naissance.date' => 'La date de naissance doit être une date valide.',
                'telephone.regex' => 'Le numéro de téléphone doit commencer par +243 et contenir 9 chiffres après.',
                'telephone.unique' => 'Un membre avec le même nom, post-nom et numéro existe déjà.',
                'adresse_physique.string' => 'L’adresse physique doit être une chaîne de caractères.',
                'profession.string' => 'La profession doit être une chaîne de caractères.',
                'email.required' => 'L’adresse e-mail est obligatoire.',
                'email.email' => 'L’adresse e-mail doit être valide.',
                'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
                'role.in' => 'Le rôle sélectionné est invalide.',
                'status.required' => 'Choisir le status du membre.',
                'status.in' => 'Le status sélectionné est invalide.',
            ]);

            $validated['password'] = Hash::make('1234');
            $validated['status'] = (int) $this->status;
            $validated['code'] = $this->generateUniqueAccountCode();

            $user = User::create($validated);

            //  ➜ Ici on attache les rôles sélectionnés :
            if (!empty($this->roles)) {
                $user->syncRoles($this->roles);
            } else {
                $user->assignRole($this->role ?? 'Membre');
            }

            // Créer les deux comptes (USD et CDF)
            foreach (['USD', 'CDF'] as $currency) {
                Account::create([
                    'user_id' => $user->id,
                    'currency' => $currency,
                    'balance' => 0,
                ]);
            }

            $this->reset([
                'name',
                'postnom',
                'prenom',
                'date_naissance',
                'telephone',
                'adresse_physique',
                'profession',
                'email',
                'role',
                'status'
            ]);
            $this->dispatch('closeModal', name: 'modalMembre');
            $this->dispatch('$refresh');
            notyf()->success('Membre enregistré avec succès !');

        } catch (Throwable $th) {
            notyf()->error('Erreur lors de l\'enregistrement du membre.');
        }
    }

    public function edit($idUser)
    {
        try {
            $user = User::findOrFail($idUser);

            $this->userId = $user->id;
            $this->name = $user->name;
            $this->postnom = $user->postnom;
            $this->prenom = $user->prenom;
            $this->date_naissance = $user->date_naissance;
            $this->telephone = $user->telephone;
            $this->adresse_physique = $user->adresse_physique;
            $this->profession = $user->profession;
            $this->email = $user->email;
            $this->status = $user->status;
            $this->password = null;
            $this->editModal = true;

            // ➜ Charger les rôles actuels :
            $this->roles = $user->roles()->pluck('name')->toArray();

            $this->dispatch('openModal', name: 'modalMembre');

        } catch (ModelNotFoundException $e) {
            notyf()->error('Membre non trouvé.');
        } catch (Throwable $th) {
            notyf()->error('Une erreur est survenue lors du chargement du membre.');
        }
    }

    public function update()
    {
        try {
            $status = (int) $this->status;

            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'postnom' => ['required', 'string', 'max:255'],
                'prenom' => ['nullable', 'string', 'max:255'],
                'date_naissance' => ['required', 'date'],
                'telephone' => [
                    'required',
                    'string',
                    'max:20',
                    'regex:/^\\+243\\d{9}$/',
                    Rule::unique('users')
                        ->ignore($this->userId)
                        ->where(function ($query) {
                            return $query->where('name', $this->name)
                                ->where('postnom', $this->postnom)
                                ->where('telephone', $this->telephone);
                        }),
                ],
                'adresse_physique' => ['nullable', 'string'],
                'profession' => ['nullable', 'string'],
                'email' => [
                    'required', 'string', 'lowercase', 'email', 'max:255',
                    Rule::unique('users')->ignore($this->userId),
                ],
                'role' => ['nullable', 'in:admin,caissier,recouvreur,membre'],
            ];

            // Si mot de passe est fourni on l'ajoute aux règles
            if ($this->password !== null && trim($this->password) !== '') {
                $rules['password'] = ['min:4'];
            }

            $validated = $this->validate($rules);

            $validated['status'] = $status;

            if ($this->password !== null && trim($this->password) !== '') {
                $validated['password'] = Hash::make($this->password);
            } else {
                unset($validated['password']);
            }

            // $user = User::findOrFail($this->userId)->update($validated);
            $user = User::findOrFail($this->userId);
            $user->update($validated);

            // ➜ Synchroniser les rôles :
            $user->syncRoles($this->roles);


            $this->dispatch('closeModal', name: 'modalMembre');
            $this->dispatch('$refresh');
            $this->resetPage();
            notyf()->success('Mise à jour effectuée avec succès.');

        } catch (ModelNotFoundException $e) {
            notyf()->error('Membre non trouvé.');
        } catch (Throwable $th) {
            notyf()->error('Une erreur est survenue lors de la mise à jour.');
        }
    }

    private function generateUniqueAccountCode()
    {
        try {
            do {
                $lastAccount = User::whereNotNull('code')->orderByDesc('id')->first();
                $number = $lastAccount ? intval(substr($lastAccount->code, 3)) + 1 : 1;
                $code = 'IMF' . str_pad($number, 3, '0', STR_PAD_LEFT);
            } while (User::where('code', $code)->exists());

            return $code;

        } catch (Throwable $th) {
            throw $th; // On relève l’erreur plutôt que de la traiter ici
        }
    }

    public function placeholder()
    {
        return view('livewire.placeholder');
    }

    public function closeModal()
    {
        $this->dispatch(event: 'closeModal', name: 'modalMembre');
    }

    public function openModal()
    {
        try {
            $this->reset([
                'name',
                'postnom',
                'prenom',
                'date_naissance',
                'telephone',
                'adresse_physique',
                'profession',
                'email',
                'role',
                'status'
            ]);
            $this->dispatch('openModal', name: 'modalMembre');

        } catch (Throwable $th) {
            notyf()->error('Impossible d’ouvrir la fenêtre.');
        }
    }

    public function render()
    {
        try {
            if ($this->search) {
                $members = User::where('code', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%")
                    ->orWhere('postnom', 'like', "%{$this->search}%")
                    ->orWhere('prenom', 'like', "%{$this->search}%")
                    ->orWhere('date_naissance', 'like', "%{$this->search}%")
                    ->orWhere('telephone', 'like', "%{$this->search}%")
                    ->orWhere('adresse_physique', 'like', "%{$this->search}%")
                    ->orWhere('profession', 'like', "%{$this->search}%")
                    ->paginate($this->perPage);
            } else {
                $members = User::where('role', 'membre')->paginate($this->perPage);
            }

            $roles_user = Role::all();

            return view('livewire.user.user-management', ['members' => $members, 'roles_user' => $roles_user]);

        } catch (Throwable $th) {
            notyf()->error('Erreur lors du chargement des membres.');
            return view('livewire.user.user-management', ['members' => []]);
        }
    }
}

