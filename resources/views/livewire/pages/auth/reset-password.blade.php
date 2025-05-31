<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(string $token): void
    {
        $this->token = $token;

        $this->email = request()->string('email');
    }

    /**
     * Reset the password for the given user.
     */
    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status != Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        Session::flash('status', __($status));

        $this->redirectRoute('login', navigate: true);
    }
}; ?>

<div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner">
        <!-- Reset Password -->
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

                <h4 class="mb-2">Réinitialiser le mot de passe 🔒</h4>
                <p class="mb-4">Saisissez votre nouvelle adresse email et votre nouveau mot de passe.</p>

                <form wire:submit.prevent="resetPassword" id="formResetPassword" class="mb-3">
                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse email</label>
                        <input
                            type="email"
                            wire:model.defer="email"
                            class="form-control"
                            id="email"
                            placeholder="exemple@domaine.com"
                            required
                            autofocus
                            autocomplete="username"
                        />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <!-- Nouveau mot de passe -->
                    <div class="mb-3 form-password-toggle">
                        <label for="password" class="form-label">Nouveau mot de passe</label>
                        <input
                            type="password"
                            wire:model.defer="password"
                            class="form-control"
                            id="password"
                            placeholder="••••••••"
                            required
                            autocomplete="new-password"
                        />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <!-- Confirmation mot de passe -->
                    <div class="mb-3 form-password-toggle">
                        <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                        <input
                            type="password"
                            wire:model.defer="password_confirmation"
                            class="form-control"
                            id="password_confirmation"
                            placeholder="••••••••"
                            required
                            autocomplete="new-password"
                        />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <div class="mb-3">
                        <button class="btn btn-primary d-grid w-100" type="submit" >
                            <span wire:loading.remove>Réinitialiser le mot de passe</span>
                            <i wire:loading class="spinner-border text-white text-center" role="status"
                            style="margin-left:40%"></i>
                        </button>
                    </div>
                </form>

                <p class="text-center">
                    <a href="{{ route('login') }}" wire:navigate>
                        <span>Retour à la connexion</span>
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

