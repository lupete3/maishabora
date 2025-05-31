<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner">
        <!-- Confirm Password -->
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

                <h4 class="mb-2">Zone sécurisée 🔐</h4>
                <p class="mb-4">Veuillez confirmer votre mot de passe avant de continuer.</p>

                <form wire:submit.prevent="confirmPassword" id="formConfirmPassword" class="mb-3">
                    <!-- Password -->
                    <div class="mb-3 form-password-toggle">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input
                            type="password"
                            wire:model.defer="password"
                            class="form-control"
                            id="password"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="mb-3">
                        <button class="btn btn-primary d-grid w-100" type="submit">
                            <span wire:loading.remove>Confirmer</span>
                            <i wire:loading class="spinner-border text-white text-center" role="status"
                            style="margin-left:40%"></i>
                        </button>
                    </div>

                </form>

                <p class="text-center">
                    <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <span>Se déconnecter</span>
                    </a>
                </p>

                <form id="logout-form" method="POST" action="{{ route('logout') }}" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</div>

