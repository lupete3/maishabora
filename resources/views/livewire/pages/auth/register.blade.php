<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $postnom = '';
    public ?string $prenom = null;
    public ?string $date_naissance = null;
    public string $telephone = '';
    public ?string $adresse_physique = null;
    public ?string $profession = null;
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = 'membre'; // Valeur par défaut


    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'postnom' => ['required', 'string', 'max:255'],
            'prenom' => ['nullable', 'string', 'max:255'],
            'date_naissance' => ['required', 'date'],
            'telephone' => ['required', 'string', 'max:20'],
            'adresse_physique' => ['nullable', 'string'],
            'profession' => ['nullable', 'string'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'role' => ['nullable', 'in:admin,caissier,recouvreur,membre'],
        ],[
            'name.required' => 'Le nom est obligatoire.',
            'postnom.required' => 'Le post-nom est obligatoire.',
            'prenom.string' => 'Le prénom doit être une chaîne de caractères.',
            'date_naissance.required' => 'La date de naissance est obligatoire.',
            'date_naissance.date' => 'La date de naissance doit être une date valide.',
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'telephone.max' => 'Le numéro de téléphone ne peut pas dépasser :max caractères.',
            'adresse_physique.string' => 'L’adresse physique doit être une chaîne de caractères.',
            'profession.string' => 'La profession doit être une chaîne de caractères.',
            'email.required' => 'L’adresse e-mail est obligatoire.',
            'email.email' => 'L’adresse e-mail doit être valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'role.in' => 'Le rôle sélectionné est invalide.',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="authentication-wrapper authentication-basic container-p-y" >
    <div class="authentication-inner" >
        <!-- Register -->
        <div class="card">
            <div class="card-body">
                <!-- Logo -->
                <div class="app-brand justify-content-center mb-4">
                    <a href="#" class="app-brand-link gap-2">
                        <span class="app-brand-logo demo">
                            <!-- SVG Logo ici -->
                        </span>
                        <span class="app-brand-text demo text-body fw-bolder">{{ config('app.name', 'Laravel') }}</span>
                    </a>
                </div>
                <!-- /Logo -->

                <h4 class="mb-2">Créer un compte ✨</h4>
                <p class="mb-4">Remplissez les informations ci-dessous pour vous inscrire</p>

                <form wire:submit.prevent="register" id="formRegister" class="mb-3">
                    <!-- Nom -->
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom</label>
                        <input type="text" wire:model.defer="name" id="name" class="form-control" placeholder="Nom" required autofocus />
                        @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Postnom -->
                    <div class="mb-3">
                        <label for="postnom" class="form-label">Postnom</label>
                        <input type="text" wire:model.defer="postnom" id="postnom" class="form-control" placeholder="Postnom" required />
                        @error('postnom') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Prenom -->
                    <div class="mb-3">
                        <label for="prenom" class="form-label">Prénom (optionnel)</label>
                        <input type="text" wire:model.defer="prenom" id="prenom" class="form-control" placeholder="Prénom" />
                        @error('prenom') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Date de naissance -->
                    <div class="mb-3">
                        <label for="date_naissance" class="form-label">Date de naissance</label>
                        <input type="date" wire:model.defer="date_naissance" id="date_naissance" class="form-control" required />
                        @error('date_naissance') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Téléphone -->
                    <div class="mb-3">
                        <label for="telephone" class="form-label">Téléphone</label>
                        <input type="text" wire:model.defer="telephone" id="telephone" class="form-control" placeholder="+243..." required />
                        @error('telephone') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Adresse physique -->
                    <div class="mb-3">
                        <label for="adresse_physique" class="form-label">Adresse physique</label>
                        <textarea wire:model.defer="adresse_physique" id="adresse_physique" class="form-control" rows="3"></textarea>
                        @error('adresse_physique') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Profession -->
                    <div class="mb-3">
                        <label for="profession" class="form-label">Profession</label>
                        <input type="text" wire:model.defer="profession" id="profession" class="form-control" placeholder="Ex: Agriculteur" />
                        @error('profession') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" wire:model.defer="email" id="email" class="form-control" placeholder="exemple@domaine.com" required />
                        @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Mot de passe -->
                    <div class="mb-3 form-password-toggle">
                        <label class="form-label" for="password">Mot de passe</label>
                        <div class="input-group input-group-merge">
                            <input type="password" wire:model.defer="password" id="password" class="form-control" placeholder="••••••••" required />
                        </div>
                        @error('password') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Confirmer mot de passe -->
                    <div class="mb-3 form-password-toggle">
                        <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                        <div class="input-group input-group-merge">
                            <input type="password" wire:model.defer="password_confirmation" id="password_confirmation" class="form-control" placeholder="••••••••" required />
                        </div>
                        @error('password_confirmation') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-3">
                        <button class="btn btn-primary d-grid w-100" type="submit" >
                            <span wire:loading.remove>S'inscrire</span>
                            <i wire:loading class="spinner-border text-white text-center" role="status"
                            style="margin-left:40%"></i>
                        </button>
                    </div>
                </form>

                <p class="text-center">
                    <span>Vous avez déjà un compte ?</span>
                    <a href="{{ route('login') }}" wire:navigate>
                        <span class="text-primary">Se connecter</span>
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
