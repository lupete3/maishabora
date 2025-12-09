<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;
use App\Helpers\UserLogHelper;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Déconnexion des autres appareils
        Auth::logoutOtherDevices($validated['password']);

        $this->reset('current_password', 'password', 'password_confirmation');

        UserLogHelper::log_user_activity('Modification Mot de passe', 'Modification du Mot de passe');

        $this->dispatch('password-updated');
        notyf()->success('Informations mises à jour');

    }
    public function placeholder()
    {
        return view('livewire.placeholder');
    }
}; ?>

<div class="card mb-4">
    <h5 class="card-header text-lg font-medium text-gray-900">Modifier le mot de passe</h5>

    <div class="card-body">
        <p class="text-muted mb-3">
            Assurez-vous d’utiliser un mot de passe long et complexe pour sécuriser votre compte.
        </p>

        <form wire:submit.prevent="updatePassword" class="row">
            <!-- Mot de passe actuel -->
            <div class="mb-3 col-md-4">
                <label for="current_password" class="form-label">Mot de passe actuel</label>
                <input
                    wire:model.defer="current_password"
                    type="password"
                    class="form-control @error('current_password') is-invalid @enderror"
                    id="current_password"
                    autocomplete="current-password"
                >
                @error('current_password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Nouveau mot de passe -->
            <div class="mb-3 col-md-4">
                <label for="password" class="form-label">Nouveau mot de passe</label>
                <input
                    wire:model.defer="password"
                    type="password"
                    class="form-control @error('password') is-invalid @enderror"
                    id="password"
                    autocomplete="new-password"
                >
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Confirmation -->
            <div class="mb-3 col-md-4">
                <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                <input
                    wire:model.defer="password_confirmation"
                    type="password"
                    class="form-control @error('password_confirmation') is-invalid @enderror"
                    id="password_confirmation"
                    autocomplete="new-password"
                >
                @error('password_confirmation')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Bouton -->
            <div class="d-flex align-items-center">
                <button type="submit" class="btn btn-primary me-3" wire:loading.attr="disabled">
                    <span wire:loading wire:target="updatePassword" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Enregistrer
                </button>

                <span class="text-success" wire:loading.remove wire:target="updatePassword" wire:transition>
                    @if (session()->has('status') && session('status') === 'password-updated')
                        Mot de passe mis à jour avec succès.
                    @endif
                </span>
            </div>
        </form>
    </div>
</div>

