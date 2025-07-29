<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use App\Helpers\UserLogHelper;

new class extends Component
{
    public string $name = '';
    public string $postnom = '';
    public ?string $prenom = null;
    public ?string $date_naissance = null;
    public string $telephone = '';
    public ?string $adresse_physique = null;
    public ?string $profession = null;
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->postnom = Auth::user()->postnom;
        $this->prenom = Auth::user()->prenom;
        $this->date_naissance = Auth::user()->date_naissance;
        $this->telephone = Auth::user()->telephone;
        $this->adresse_physique = Auth::user()->adresse_physique;
        $this->profession = Auth::user()->profession;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'postnom' => ['required', 'string', 'max:255'],
            'prenom' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        UserLogHelper::log_user_activity('Modification Information', 'Modification des Informations du profile');

        $this->dispatch('profile-updated', name: $user->name);
        notyf()->success('Informations mises à jour');
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        // Session::flash('status', 'verification-link-sent');
        notyf()->success('Lien de véfification envoyé');

    }

    public function placeholder()
    {
        return view('livewire.placeholder');
    }
}; ?>

<div class="card mb-4">
    <h5 class="card-header text-lg font-medium text-gray-900">Informations du profil</h5>

    <div class="card-body">
        <p class="text-muted mb-4">
            Mettez à jour les informations de votre compte et votre adresse e-mail.
        </p>

        <form wire:submit.prevent="updateProfileInformation">
            <div class="row g-3">

                <!-- Nom -->
                <div class="col-md-3">
                    <label for="name" class="form-label">Nom</label>
                    <input wire:model.defer="name" type="text" class="form-control @error('name') is-invalid @enderror" {{ auth()->user()->role == 'membre' ? 'disabled' : '' }} id="name" required autofocus>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Postnom -->
                <div class="col-md-3">
                    <label for="postnom" class="form-label">Postnom</label>
                    <input wire:model.defer="postnom" type="text" class="form-control @error('postnom') is-invalid @enderror" {{ auth()->user()->role == 'membre' ? 'disabled' : '' }} id="postnom" required>
                    @error('postnom') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Prenom -->
                <div class="col-md-3">
                    <label for="prenom" class="form-label">Prénom</label>
                    <input wire:model.defer="prenom" type="text" class="form-control @error('prenom') is-invalid @enderror" {{ auth()->user()->role == 'membre' ? 'disabled' : '' }} id="prenom">
                    @error('prenom') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Date de naissance -->
                <div class="col-md-3">
                    <label for="date_naissance" class="form-label">Date de naissance</label>
                    <input wire:model.defer="date_naissance" type="date" class="form-control @error('date_naissance') is-invalid @enderror" {{ auth()->user()->role == 'membre' ? 'disabled' : '' }} id="date_naissance" required>
                    @error('date_naissance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Téléphone -->
                <div class="col-md-3">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <input wire:model.defer="telephone" type="text" class="form-control @error('telephone') is-invalid @enderror" {{ auth()->user()->role == 'membre' ? 'disabled' : '' }} id="telephone" required>
                    @error('telephone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Profession -->
                <div class="col-md-3">
                    <label for="profession" class="form-label">Profession</label>
                    <input wire:model.defer="profession" type="text" class="form-control @error('profession') is-invalid @enderror" {{ auth()->user()->role == 'membre' ? 'disabled' : '' }} id="profession">
                    @error('profession') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Email -->
                <div class="col-md-6">
                    <label for="email" class="form-label">Adresse e-mail</label>
                    <input wire:model.defer="email" type="email" class="form-control @error('email') is-invalid @enderror" {{ auth()->user()->role == 'membre' ? 'disabled' : '' }} id="email" required autocomplete="username">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Adresse physique -->
                <div class="col-md-12">
                    <label for="adresse_physique" class="form-label">Adresse physique</label>
                    <textarea wire:model.defer="adresse_physique" class="form-control @error('adresse_physique') is-invalid @enderror" {{ auth()->user()->role == 'membre' ? 'disabled' : '' }} id="adresse_physique" rows="2"></textarea>
                    @error('adresse_physique') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <!-- Email verification alert -->
                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                    <div class="col-md-12">
                        <div class="alert alert-warning mt-3 mb-0">
                            Votre adresse e-mail n’est pas vérifiée.
                            <button type="button" wire:click.prevent="sendVerification" class="btn btn-link p-0 align-baseline">
                                Cliquez ici pour renvoyer l’e-mail de vérification.
                            </button>

                            @if (session('status') === 'verification-link-sent')
                                <div class="text-success mt-2">
                                    Un nouveau lien de vérification a été envoyé à votre adresse e-mail.
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Bouton Enregistrer -->
                <div class="col-md-12 mt-3">
                    @if (auth()->user()->role != 'membre')
                    <button type="submit" class="btn btn-primary me-3" wire:loading.attr="disabled">
                        <span wire:loading wire:target="updateProfileInformation" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Enregistrer
                    </button>

                    <span class="text-success" wire:loading.remove wire:target="updateProfileInformation">
                        @if (session('status') === 'profile-updated')
                            Informations mises à jour avec succès.
                        @endif
                    </span>
                    @endif
                </div>

            </div>
        </form>
    </div>
</div>
