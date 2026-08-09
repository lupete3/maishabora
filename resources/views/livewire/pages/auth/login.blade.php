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

                    <div class="mb-3">
                        <a href="{{ route('auth.google') }}"
                        class="btn btn-light border d-flex align-items-center justify-content-center w-100 py-2"
                        style="height: 44px; font-weight: 500;">

                            <svg width="20" height="20" viewBox="0 0 24 24" class="me-2">
                                <path fill="#4285F4" d="M21.35 12.27c0-.78-.07-1.54-.22-2.27H12v4.3h5.24a4.48 4.48 0 0 1-1.94 2.94v2.45h3.14c1.84-1.69 2.91-4.18 2.91-7.42z"/>
                                <path fill="#34A853" d="M12 21.75c2.63 0 4.84-.87 6.45-2.36l-3.14-2.45c-.87.58-1.98.92-3.31.92-2.54 0-4.69-1.72-5.46-4.03H3.3v2.53A9.75 9.75 0 0 0 12 21.75z"/>
                                <path fill="#FBBC05" d="M6.54 13.83A5.86 5.86 0 0 1 6.23 12c0-.64.11-1.26.31-1.83V7.64H3.3A9.75 9.75 0 0 0 2.25 12c0 1.57.38 3.05 1.05 4.36l3.24-2.53z"/>
                                <path fill="#EA4335" d="M12 6.14c1.43 0 2.71.49 3.72 1.45l2.79-2.79C16.83 3.25 14.63 2.25 12 2.25A9.75 9.75 0 0 0 3.3 7.64l3.24 2.53C7.31 7.86 9.46 6.14 12 6.14z"/>
                            </svg>

                            <span>Continuer avec Google</span>
                        </a>
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

