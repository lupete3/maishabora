<?php

use App\Helpers\UserLogHelper;
use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Authentification utilisateur
     */
    public function login(): void
    {
        try {

            /**
             * Validation sécurisée
             */
            $this->form->validate();

            /**
             * Authentification
             */
            $this->form->authenticate();

            /**
             * Régénération session
             */
            Session::regenerate();

            /**
             * Régénération CSRF token
             */
            request()->session()->regenerateToken();

            /**
             * Journal activité
             */
            UserLogHelper::log_user_activity(
                'Connexion',
                'Utilisateur connecté'
            );

            /**
             * Redirection
             */
            $this->redirectIntended(
                default: route('dashboard', absolute: false),
                navigate: false
            );

        } catch (ValidationException $e) {

            throw $e;

        } catch (\Throwable $e) {

            /**
             * Log erreur sécurité
             */
            Log::error('Erreur connexion Livewire', [
                'message' => $e->getMessage(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            throw ValidationException::withMessages([
                'form.email' => 'Une erreur est survenue. Veuillez réessayer.',
            ]);
        }
    }
};
?>

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
                        <button
                            class="btn btn-primary d-grid w-100"
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="login"
                        >

                            <span wire:loading.remove wire:target="login">
                                Connexion
                            </span>

                            <span
                                wire:loading.flex
                                wire:target="login"
                                class="justify-content-center align-items-center"
                            >
                                <i class="spinner-border spinner-border-sm text-white"></i>
                            </span>

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

