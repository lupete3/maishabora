<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
    public function placeholder()
    {
        return view('livewire.placeholder');
    }
}; ?>


<div class="card">
    {{-- <h2 class="card-header text-lg font-medium text-gray-900">Suppression du compte</h2>

    <div class="card-body">
        <div class="mb-3 col-12 mb-0">
            <div class="alert alert-warning">
                <h6 class="alert-heading fw-bold mb-1">Êtes-vous sûr de vouloir supprimer votre compte ?</h6>
                <p class="mb-0">
                    Une fois votre compte supprimé, toutes ses données seront définitivement perdues. Veuillez saisir votre mot de passe pour confirmer.
                </p>
            </div>
        </div>

        <!-- Formulaire Livewire -->
        <form wire:submit.prevent="deleteUser">
            <!-- Mot de passe -->
            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe</label>
                <input
                    wire:model.defer="password"
                    type="password"
                    id="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="Mot de passe"
                    required
                />
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- Confirmation -->
            <div class="form-check mb-3">
                <input
                    wire:model="confirmation"
                    class="form-check-input"
                    type="checkbox"
                    id="confirmationSuppression"
                />
                <label class="form-check-label" for="confirmationSuppression">
                    Je confirme la suppression de mon compte
                </label>
                @error('confirmation')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <!-- Boutons -->
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-danger" wire:loading.attr="disabled">
                    <span wire:loading wire:target="deleteUser" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Supprimer mon compte
                </button>
            </div>
        </form>
    </div> --}}
</div>

