<?php

namespace App\Livewire\Members;

use App\Helpers\UserLogHelper;
use Livewire\Component;
use App\Models\User;
use App\Models\Account;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Throwable;

class RegisterMember extends Component
{
    use WithFileUploads;
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    // Infos de base
    public string $name = '';
    public string $postnom = '';
    public ?string $prenom = null;
    public ?string $sexe;
    public ?string $date_naissance = null;
    public ?string $lieu_naissance = null;
    public string $telephone = '';
    public ?string $adresse_physique = null;
    public ?string $province = null;
    public ?string $ville = null;
    public ?string $commune = null;
    public ?string $quartier = null;

    // Profession & économique
    public ?string $profession = null;
    public ?float $revenu_mensuel = null;
    public ?string $source_revenu = null;
    public ?string $nom_employeur = null;

    // Pièce d’identité
    public ?string $type_piece = null;
    public ?string $numero_piece = null;
    public ?string $date_expiration_piece = null;

    // État civil
    public ?string $etat_civil = null;
    public ?int $nombre_dependants = null;

    // Conjoint
    public ?string $nom_conjoint = null;
    public ?string $telephone_conjoint = null;

    // Référence
    public ?string $nom_reference = null;
    public ?string $telephone_reference = null;
    public ?string $lien_reference = null;

    // Infos institutionnelles
    public ?string $date_adhesion = null;
    // Divers
    public ?string $nationalite = null;
    public ?string $niveau_etude = null;
    public ?string $remarque = null;

    // Compte
    public string $email = '';
    public string $role = 'membre';
    public bool $status = true;

    // Fichiers (si tu les utilises)
    public $photo_profil = null;
    public $scan_piece = null;
    public ?string $photo_profil_url = null;
    public ?string $scan_piece_url = null;

    // Utilitaires Livewire
    public $search = '';
    public $perPage = 10;
    public $editModal = false;
    public $userId;
    public $selectedMemberId = null;

    public $currentStep = 1;
    public $totalSteps = 5;


    public function nextStep()
    {
        $this->validateStep($this->currentStep);

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function validateStep($step)
    {
        $rules = [];
        switch ($step) {
            case 1:
                $rules = [
                    'name' => ['required', 'string', 'max:255'],
                    'postnom' => ['required', 'string', 'max:255'],
                    'prenom' => ['nullable', 'string', 'max:255'],
                    'sexe' => ['nullable', 'string', 'max:255'],
                    'date_naissance' => ['required', 'date'],
                    'telephone' => [
                        'required',
                        'string',
                        'max:20',
                        'regex:/^\+243\d{9}$/',
                        Rule::unique('users')->where(fn ($query) => $query
                            ->where('name', $this->name)
                            ->where('postnom', $this->postnom)
                            ->where('telephone', $this->telephone)),
                    ],
                    'profession' => ['nullable', 'string', 'max:255'],
                    'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($this->userId)],
                    'adresse_physique' => ['nullable', 'string'],
                ];
                break;
            case 2:
                $rules = [
                    'type_piece' => ['nullable', 'string', 'max:100'],
                    'numero_piece' => ['nullable', 'string', 'max:100'],
                    'date_expiration_piece' => ['nullable', 'date'],
                    'etat_civil' => ['nullable', 'in:célibataire,marié,divorcé,veuf'],
                    'nombre_dependants' => ['nullable', 'integer', 'min:0'],
                    'lieu_naissance' => ['nullable', 'string', 'max:255'],
                ];
                break;
            case 3:
                $rules = [
                    'revenu_mensuel' => ['nullable', 'numeric', 'min:0'],
                    'source_revenu' => ['nullable', 'string', 'max:255'],
                    'nom_employeur' => ['nullable', 'string', 'max:255'],
                    'nom_conjoint' => ['nullable', 'string', 'max:255'],
                    'telephone_conjoint' => ['nullable', 'string', 'max:20'],
                ];
                break;
            case 4:
                $rules = [
                    'nom_reference' => ['nullable', 'string', 'max:255'],
                    'telephone_reference' => ['nullable', 'string', 'max:20'],
                    'lien_reference' => ['nullable', 'string', 'max:255'],
                    'province' => ['nullable', 'string', 'max:255'],
                    'ville' => ['nullable', 'string', 'max:255'],
                    'commune' => ['nullable', 'string', 'max:255'],
                    'quartier' => ['nullable', 'string', 'max:255'],
                ];
                break;
            case 5:
                $rules = [
                    'photo_profil' => ['nullable', 'image', 'max:4048'],
                    'scan_piece' => ['nullable', 'mimes:jpeg,png,pdf', 'max:4096'],
                    'date_adhesion' => ['nullable', 'date'],
                    'nationalite' => ['nullable', 'string', 'max:255'],
                    'niveau_etude' => ['nullable', 'string', 'max:255'],
                    'remarque' => ['nullable', 'string'],
                ];
                break;
        }

        $this->validate($rules);
    }


    public function submitForm()
    {
        $validator = Validator::make([
            'name' => $this->name,
            'postnom' => $this->postnom,
            'prenom' => $this->prenom,
            'sexe' => $this->sexe,
            'date_naissance' => $this->date_naissance,
            'lieu_naissance' => $this->lieu_naissance,
            'telephone' => $this->telephone,
            'adresse_physique' => $this->adresse_physique,
            'province' => $this->province,
            'ville' => $this->ville,
            'commune' => $this->commune,
            'quartier' => $this->quartier,
            'profession' => $this->profession,
            'type_piece' => $this->type_piece,
            'numero_piece' => $this->numero_piece,
            'date_expiration_piece' => $this->date_expiration_piece,
            'etat_civil' => $this->etat_civil,
            'nombre_dependants' => $this->nombre_dependants,
            'revenu_mensuel' => $this->revenu_mensuel,
            'source_revenu' => $this->source_revenu,
            'nom_employeur' => $this->nom_employeur,
            'nom_conjoint' => $this->nom_conjoint,
            'telephone_conjoint' => $this->telephone_conjoint,
            'nom_reference' => $this->nom_reference,
            'telephone_reference' => $this->telephone_reference,
            'lien_reference' => $this->lien_reference,
            'date_adhesion' => $this->date_adhesion,
            'nationalite' => $this->nationalite,
            'niveau_etude' => $this->niveau_etude,
            'remarque' => $this->remarque,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'postnom' => ['required', 'string', 'max:255'],
            'prenom' => ['nullable', 'string', 'max:255'],
            'sexe' => ['nullable', 'string', 'max:255'],
            'date_naissance' => ['required', 'date'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'telephone' => [
                'required',
                'string',
                'max:20',
                'regex:/^\+243\d{9}$/',
                Rule::unique('users')->where(fn ($query) => $query
                    ->where('name', $this->name)
                    ->where('postnom', $this->postnom)
                    ->where('telephone', $this->telephone)),
            ],
            'adresse_physique' => ['nullable', 'string'],
            'province' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:255'],
            'commune' => ['nullable', 'string', 'max:255'],
            'quartier' => ['nullable', 'string', 'max:255'],
            'profession' => ['nullable', 'string', 'max:255'],
            'type_piece' => ['nullable', 'string', 'max:100'],
            'numero_piece' => ['nullable', 'string', 'max:100'],
            'date_expiration_piece' => ['nullable', 'date'],
            'etat_civil' => ['nullable', 'in:célibataire,marié,divorcé,veuf'],
            'nombre_dependants' => ['nullable', 'integer', 'min:0'],
            'revenu_mensuel' => ['nullable', 'numeric', 'min:0'],
            'source_revenu' => ['nullable', 'string', 'max:255'],
            'nom_employeur' => ['nullable', 'string', 'max:255'],
            'nom_conjoint' => ['nullable', 'string', 'max:255'],
            'telephone_conjoint' => ['nullable', 'string', 'max:20'],
            'nom_reference' => ['nullable', 'string', 'max:255'],
            'telephone_reference' => ['nullable', 'string', 'max:20'],
            'lien_reference' => ['nullable', 'string', 'max:255'],
            'date_adhesion' => ['nullable', 'date'],
            'nationalite' => ['nullable', 'string', 'max:255'],
            'niveau_etude' => ['nullable', 'string', 'max:255'],
            'remarque' => ['nullable', 'string'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'role' => ['nullable', 'in:admin,caissier,recouvreur,comptable,receptionniste,membre'],
            'status' => ['required', 'in:0,1'],
            'photo_profil' => ['nullable', 'image', 'max:4048'], // max 2MB
            'scan_piece' => ['nullable', 'mimes:jpeg,png,pdf', 'max:4096'],
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'postnom.required' => 'Le post-nom est obligatoire.',
            'prenom.string' => 'Le prénom doit être une chaîne.',
            'date_naissance.required' => 'La date de naissance est obligatoire.',
            'date_naissance.date' => 'La date doit être valide.',
            'lieu_naissance.string' => 'Le lieu de naissance doit être une chaîne.',
            'telephone.regex' => 'Le numéro doit commencer par +243 et contenir 9 chiffres.',
            'telephone.unique' => 'Ce membre existe déjà (nom, post-nom, téléphone).',
            'adresse_physique.string' => 'L’adresse doit être une chaîne.',
            'email.required' => 'L’email est obligatoire.',
            'email.email' => 'L’email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'role.in' => 'Le rôle sélectionné est invalide.',
            'status.required' => 'Choisir le statut.',
            'status.in' => 'Le statut est invalide.',
        ]);

        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());
            notyf()->error($validator->errors().'Veuillez corriger les erreurs dans le formulaire.');
            return;
        }

        $validated = $validator->validated();
        $validated['password'] = Hash::make('1234');
        $validated['status'] = (int) $this->status;
        $validated['code'] = $this->generateUniqueAccountCode();

        if ($this->photo_profil) {
            $photoPath = $this->photo_profil->store('photos_profil', 'public');
            $validated['photo_profil'] = $photoPath;
        }

        if ($this->scan_piece) {
            $scanPath = $this->scan_piece->store('scans_pieces', 'public');
            $validated['scan_piece'] = $scanPath;
        }

        $user = User::create($validated);

        foreach (['USD', 'CDF'] as $currency) {
            Account::create([
                'user_id' => $user->id,
                'currency' => $currency,
                'balance' => 0,
            ]);
        }

        UserLogHelper::log_user_activity(
            action: 'enregistrement_membre',
            description: "Enregistrement du membre {$user->name} {$user->postnom} ({$user->code})"
        );

        $this->reset([
            'name', 'postnom', 'prenom','sexe', 'date_naissance', 'lieu_naissance',
            'telephone', 'adresse_physique', 'province', 'ville', 'commune', 'quartier',
            'profession', 'revenu_mensuel', 'source_revenu', 'nom_employeur',
            'type_piece', 'numero_piece', 'date_expiration_piece',
            'etat_civil', 'nombre_dependants',
            'nom_conjoint', 'telephone_conjoint',
            'nom_reference', 'telephone_reference', 'lien_reference',
            'date_adhesion', 'nationalite', 'niveau_etude', 'remarque',
            'email', 'role', 'status',
            'photo_profil', 'scan_piece'
        ]);

        $this->dispatch('closeModal', name: 'modalMembre');
        $this->dispatch('$refresh');

        notyf()->success('Membre enregistré avec succès !');
    }

    public function edit($idUser)
    {
        try {
            $user = User::findOrFail($idUser);

            $this->userId = $user->id;
            $this->name = $user->name;
            $this->postnom = $user->postnom;
            $this->prenom = $user->prenom;
            $this->sexe = $user->sexe;
            $this->date_naissance = $user->date_naissance;
            $this->lieu_naissance = $user->lieu_naissance;
            $this->telephone = $user->telephone;
            $this->adresse_physique = $user->adresse_physique;
            $this->province = $user->province;
            $this->ville = $user->ville;
            $this->commune = $user->commune;
            $this->quartier = $user->quartier;

            $this->profession = $user->profession;
            $this->revenu_mensuel = $user->revenu_mensuel;
            $this->source_revenu = $user->source_revenu;
            $this->nom_employeur = $user->nom_employeur;

            $this->type_piece = $user->type_piece;
            $this->numero_piece = $user->numero_piece;
            $this->date_expiration_piece = $user->date_expiration_piece;

            $this->etat_civil = $user->etat_civil;
            $this->nombre_dependants = $user->nombre_dependants;

            $this->nom_conjoint = $user->nom_conjoint;
            $this->telephone_conjoint = $user->telephone_conjoint;

            $this->nom_reference = $user->nom_reference;
            $this->telephone_reference = $user->telephone_reference;
            $this->lien_reference = $user->lien_reference;

            $this->date_adhesion = $user->date_adhesion;
            $this->nationalite = $user->nationalite;
            $this->niveau_etude = $user->niveau_etude;
            $this->remarque = $user->remarque;

            $this->email = $user->email;
            $this->role = $user->role ?? 'membre';
            $this->status = (bool) $user->status;

            $this->photo_profil_url = $user->photo_profil;
            $this->scan_piece_url = $user->scan_piece;


            $this->editModal = true;
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

            $validated = $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'postnom' => ['required', 'string', 'max:255'],
                'prenom' => ['nullable', 'string', 'max:255'],
                'sexe' => ['nullable', 'string', 'max:255'],
                'date_naissance' => ['required', 'date'],
                'lieu_naissance' => ['nullable', 'string', 'max:255'],
                'telephone' => [
                    'required',
                    'string',
                    'max:20',
                    'regex:/^\+243\d{9}$/',
                    Rule::unique('users')
                        ->ignore($this->userId)
                        ->where(fn($query) => $query
                            ->where('name', $this->name)
                            ->where('postnom', $this->postnom)
                            ->where('telephone', $this->telephone)),
                ],
                'adresse_physique' => ['nullable', 'string'],
                'province' => ['nullable', 'string', 'max:255'],
                'ville' => ['nullable', 'string', 'max:255'],
                'commune' => ['nullable', 'string', 'max:255'],
                'quartier' => ['nullable', 'string', 'max:255'],

                'profession' => ['nullable', 'string', 'max:255'],
                'revenu_mensuel' => ['nullable', 'numeric', 'min:0'],
                'source_revenu' => ['nullable', 'string', 'max:255'],
                'nom_employeur' => ['nullable', 'string', 'max:255'],

                'type_piece' => ['nullable', 'string', 'max:100'],
                'numero_piece' => ['nullable', 'string', 'max:100'],
                'date_expiration_piece' => ['nullable', 'date'],

                'etat_civil' => ['nullable', 'in:célibataire,marié,divorcé,veuf'],
                'nombre_dependants' => ['nullable', 'integer', 'min:0'],

                'nom_conjoint' => ['nullable', 'string', 'max:255'],
                'telephone_conjoint' => ['nullable', 'string', 'max:20'],

                'nom_reference' => ['nullable', 'string', 'max:255'],
                'telephone_reference' => ['nullable', 'string', 'max:20'],
                'lien_reference' => ['nullable', 'string', 'max:255'],

                'date_adhesion' => ['nullable', 'date'],

                'nationalite' => ['nullable', 'string', 'max:255'],
                'niveau_etude' => ['nullable', 'string', 'max:255'],
                'remarque' => ['nullable', 'string'],

                'email' => [
                    'required', 'string', 'lowercase', 'email', 'max:255',
                    Rule::unique('users')->ignore($this->userId),
                ],
                'role' => ['nullable', 'in:admin,caissier,recouvreur,comptable,receptionniste,membre'],
                'photo_profil' => ['nullable', 'image', 'max:4048'], // max 2MB
                'scan_piece' => ['nullable', 'mimes:jpeg,png,pdf', 'max:4096'],
            ]);

            $validated['status'] = $status;

            if ($this->photo_profil) {
                $photoPath = $this->photo_profil->store('photos_profil', 'public');
                $validated['photo_profil'] = $photoPath;
            } else {
                $validated['photo_profil'] = $this->photo_profil_url; // conserver l'ancien
            }

            if ($this->scan_piece) {
                $scanPath = $this->scan_piece->store('scans_pieces', 'public');
                $validated['scan_piece'] = $scanPath;
            } else {
                $validated['scan_piece'] = $this->scan_piece_url; // conserver l'ancien
            }

            User::findOrFail($this->userId)->update($validated);

            UserLogHelper::log_user_activity(
                action: 'mise_a_jour_membre',
                description: "Mise à jour du membre {$this->name} {$this->postnom} ({$this->userId})"
            );

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
                // Récupère le dernier code utilisateur
                $lastAccount = User::whereNotNull('code')->orderByDesc('id')->first();
                
                // Extrait le numéro incrémental après "34" + année (à partir du 6ème caractère)
                $number = $lastAccount 
                    ? intval(substr($lastAccount->code, 6)) + 1 
                    : 1;
                
                // Génère le code avec : "34" + année + numéro incrémental (10 chiffres)
                $code = '34' . now()->format('Y') . str_pad($number, 10, '0', STR_PAD_LEFT);
            } while (User::where('code', $code)->exists()); // Vérifie l'unicité
            
            return $code;
        } catch (\Throwable $th) {
            throw $th; // Relève l'erreur
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
                'name', 'postnom', 'prenom','sexe', 'date_naissance', 'lieu_naissance',
                'telephone', 'adresse_physique', 'province', 'ville', 'commune', 'quartier',
                'profession', 'revenu_mensuel', 'source_revenu', 'nom_employeur',
                'type_piece', 'numero_piece', 'date_expiration_piece',
                'etat_civil', 'nombre_dependants',
                'nom_conjoint', 'telephone_conjoint',
                'nom_reference', 'telephone_reference', 'lien_reference',
                'date_adhesion', 'nationalite', 'niveau_etude', 'remarque',
                'email', 'role', 'status',
                'photo_profil', 'scan_piece'
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
                $members = User::where('role', 'membre')
                    ->where(function ($query) {
                        $query->where('code', 'like', "%{$this->search}%")
                            ->orWhere('name', 'like', "%{$this->search}%")
                            ->orWhere('postnom', 'like', "%{$this->search}%")
                            ->orWhere('prenom', 'like', "%{$this->search}%")
                            ->orWhere('sexe', 'like', "%{$this->search}%")
                            ->orWhere('date_naissance', 'like', "%{$this->search}%")
                            ->orWhere('telephone', 'like', "%{$this->search}%")
                            ->orWhere('adresse_physique', 'like', "%{$this->search}%")
                            ->orWhere('profession', 'like', "%{$this->search}%");
                    })
                    ->paginate($this->perPage);
            } else {
                $members = User::where('role', 'membre')->paginate($this->perPage);
            }

            return view('livewire.members.register-member', ['members' => $members]);

        } catch (Throwable $th) {
            notyf()->error('Erreur lors du chargement des membres.');
            return view('livewire.members.register-member', ['members' => []]);
        }
    }
}
