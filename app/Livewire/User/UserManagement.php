<?php

namespace App\Livewire\User;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Account;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class UserManagement extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    // 🧩 Propriétés
    public $userId = null;
    public string $name = '';
    public string $postnom = '';
    public ?string $prenom = null;
    public ?string $date_naissance = null;
    public string $telephone = '';
    public ?string $adresse_physique = null;
    public ?string $profession = null;
    public string $email = '';
    public ?string $password = null;
    public array $roles = [];
    public string $role = 'membre';
    public bool $status = false;
    public string $search = '';
    public int $perPage = 10;
    public bool $editModal = false;
    public bool $is_suspended = false;

    public $roleAgent;
    public $rolesAgents = ['admin', 'caissier', 'recouvreur', 'receptionniste', 'membre', 'comptable'];

    // ✅ Validation centralisée
    protected function rules()
    {
        $uniquePhone = Rule::unique('users', 'telephone')
            ->ignore($this->userId);

        $uniqueEmail = Rule::unique('users', 'email')
            ->ignore($this->userId);

        return [
            'name' => ['required', 'string', 'max:255'],
            'postnom' => ['required', 'string', 'max:255'],
            'prenom' => ['nullable', 'string', 'max:255'],
            'date_naissance' => ['required', 'date'],
            'telephone' => ['required', 'regex:/^\+243\d{9}$/', $uniquePhone],
            'adresse_physique' => ['nullable', 'string'],
            'profession' => ['nullable', 'string'],
            'email' => ['required', 'email', 'max:255', $uniqueEmail],
            'role' => ['nullable', 'in:admin,caissier,recouvreur,membre'],
            'status' => ['required', 'boolean'],
            'is_suspended' => ['required', 'boolean'],
        ];
    }

    protected $messages = [
        'name.required' => 'Le nom est obligatoire.',
        'postnom.required' => 'Le post-nom est obligatoire.',
        'date_naissance.required' => 'La date de naissance est obligatoire.',
        'telephone.required' => 'Le numéro de téléphone est obligatoire.',
        'telephone.regex' => 'Le numéro doit commencer par +243 et contenir 9 chiffres après.',
        'telephone.unique' => 'Ce numéro est déjà utilisé.',
        'email.required' => 'L’adresse e-mail est obligatoire.',
        'email.email' => 'L’adresse e-mail doit être valide.',
        'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
        'status.required' => 'Choisir le statut du membre.',
        'is_suspended.required' => 'Choisir le statut de suspension du membre.',
    ];

    // ✅ Réinitialise le formulaire
    private function resetForm()
    {
        $this->reset([
            'userId', 'name', 'postnom', 'prenom', 'date_naissance',
            'telephone', 'adresse_physique', 'profession',
            'email', 'password', 'role', 'status', 'roles', 'is_suspended'
        ]);
    }

    // ✅ Créer un membre
    public function submit()
    {
        try {
            $validated = $this->validate();
            $validated['password'] = Hash::make('1234');
            $validated['status'] = (int) $this->status;
            $validated['is_suspended'] = (bool) $this->is_suspended;
            $validated['role'] = $this->roleAgent ?? 'membre';
            $validated['code'] = $this->generateUniqueAccountCode();

            $user = User::create($validated);

            // Attribution des rôles
            if (!empty($this->roles)) {
                $user->syncRoles($this->roles);
            } else {
                $user->assignRole($this->role ?? 'membre');
            }

            // Création automatique des comptes
            foreach (['USD', 'CDF'] as $currency) {
                Account::create([
                    'user_id' => $user->id,
                    'currency' => $currency,
                    'balance' => 0,
                ]);
            }

            $this->resetForm();
            $this->dispatch('closeModal', name: 'modalMembre');
            $this->dispatch('$refresh');
            notyf()->success('Membre enregistré avec succès !');
        } catch (Throwable $th) {
            report($th);
            dd($th);
            notyf()->error('Erreur lors de l’enregistrement du membre.');
        }
    }

    // ✅ Charger les données d’un membre pour modification
    public function edit($idUser)
    {
        try {
            $user = User::findOrFail($idUser);
            $this->userId = $user->id;
            $this->fill($user->only([
                'name', 'postnom', 'prenom', 'date_naissance',
                'telephone', 'adresse_physique', 'profession',
                'email', 'status', 'is_suspended'
            ]));
            $this->roleAgent = $user->role;
            $this->roles = $user->roles()->pluck('name')->toArray();
            $this->editModal = true;
            $this->dispatch('openModal', name: 'modalMembre');
        } catch (ModelNotFoundException) {
            notyf()->error('Membre non trouvé.');
        } catch (Throwable $th) {
            report($th);
            notyf()->error('Erreur lors du chargement du membre.');
        }
    }

    // ✅ Mettre à jour un membre
    public function update()
    {
        try {
            $validated = $this->validate();

            if (!empty($this->password)) {
                $validated['password'] = Hash::make($this->password);
            }
            $validated['role'] = $this->roleAgent;
            $validated['is_suspended'] = (bool) $this->is_suspended;

            $user = User::findOrFail($this->userId);
            $user->update($validated);

            $user->syncRoles($this->roles);

            $this->resetForm();
            $this->dispatch('closeModal', name: 'modalMembre');
            $this->dispatch('$refresh');
            $this->resetPage();

            notyf()->success('Mise à jour effectuée avec succès.');
        } catch (ModelNotFoundException) {
            notyf()->error('Membre non trouvé.');
        } catch (Throwable $th) {
            report($th);
            notyf()->error('Erreur lors de la mise à jour du membre.');
        }
    }

    // ✅ Génération code unique
    private function generateUniqueAccountCode()
    {
        do {
            $last = User::whereNotNull('code')->orderByDesc('id')->first();
            $num = $last ? intval(substr($last->code, 6)) + 1 : 1;
            $code = '34' . now()->format('Y') . str_pad($num, 10, '0', STR_PAD_LEFT);
        } while (User::where('code', $code)->exists());

        return $code;
    }

    // ✅ Ouverture du modal pour ajout
    public function openModal()
    {
        $this->resetForm();
        $this->dispatch('openModal', name: 'modalMembre');
    }

    public function closeModal()
    {
        $this->resetForm();
        $this->dispatch('closeModal', name: 'modalMembre');
    }

    // ✅ Affichage
    public function render()
    {
        try {
            $query = User::query();

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('code', 'like', "%{$this->search}%")
                      ->orWhere('name', 'like', "%{$this->search}%")
                      ->orWhere('postnom', 'like', "%{$this->search}%")
                      ->orWhere('prenom', 'like', "%{$this->search}%")
                      ->orWhere('telephone', 'like', "%{$this->search}%");
                });
            } else {
                $query->whereHas('roles', fn($r) => $r->where('name', 'membre'));
            }

            $members = $query->paginate($this->perPage);
            $roles_user = Role::all();

            return view('livewire.user.user-management', compact('members', 'roles_user'));
        } catch (Throwable $th) {
            report($th);
            notyf()->error('Erreur lors du chargement des membres.');
            return view('livewire.user.user-management', ['members' => collect()]);
        }
    }
}
