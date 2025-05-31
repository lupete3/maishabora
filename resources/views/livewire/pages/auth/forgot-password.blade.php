<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

<div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner">
        <!-- Forgot Password -->
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

                <h4 class="mb-2">Mot de passe oublié ? 🔐</h4>
                <p class="mb-4">Entrez votre adresse email et nous vous enverrons un lien de réinitialisation.</p>

                <!-- Message de confirmation -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form wire:submit.prevent="sendPasswordResetLink" id="formForgotPassword" class="mb-3">
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
                        />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div class="mb-3">
                        <button class="btn btn-primary d-grid w-100" type="submit">
                             <span wire:loading.remove>Envoyer le lien de réinitialisation</span>
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

