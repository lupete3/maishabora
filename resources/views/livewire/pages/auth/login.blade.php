<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use App\Helpers\UserLogHelper;


new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        UserLogHelper::log_user_activity('Connexion', 'Utilisateur déconnecté');

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: false);
    }
}; ?>

<div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner">
        <!-- Login -->
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

                <h4 class="mb-2">Bienvenue 👋</h4>
                <p class="mb-4">Connectez-vous à votre compte</p>

                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form wire:submit="login" id="formAuthentication" class="mb-3">
                    <!-- Email Address -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input
                            type="email"
                            wire:model.defer="form.email"
                            class="form-control"
                            id="email"
                            placeholder="Entrez votre email"
                            required
                            autofocus
                            autocomplete="off"
                        />
                        <x-input-error :messages="$errors->get('form.email')" class="mt-2 text-danger" />
                    </div>

                    <!-- Password -->
                    <div class="mb-3 form-password-toggle">
                        <div class="d-flex justify-content-between">
                            <label class="form-label" for="password">Mot de passe</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" wire:navigate>
                                    <small class="text-primary">Mot de passe oublié ?</small>
                                </a>
                            @endif
                        </div>
                        <div class="input-group input-group-merge">
                            <input
                                type="password"
                                wire:model.defer="form.password"
                                id="password"
                                class="form-control"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                            />
                        </div>
                        <x-input-error :messages="$errors->get('form.password')" class="mt-2 text-danger" />
                    </div>

                    <!-- Remember Me -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input
                                wire:model="form.remember"
                                class="form-check-input"
                                type="checkbox"
                                id="remember"
                                name="remember"
                            />
                            <label class="form-check-label" for="remember">Se souvenir de moi</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <button class="btn btn-primary d-grid w-100" type="submit" >
                            <span wire:loading.remove>Connexion</span>
                            <i wire:loading class="spinner-border text-white text-center" role="status"
                            style="margin-left:40%"></i>
                        </button>
                    </div>

                </form>

                {{-- <p class="text-center">
                    <span>Nouveau ?</span>
                    <a href="{{ route('register') }}" wire:navigate>
                        <span class="text-primary">Créer un compte</span>
                    </a>
                </p> --}}
            </div>
        </div>
    </div>
</div>

