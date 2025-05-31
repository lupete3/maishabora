<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner">
        <!-- Email Verification -->
        <div class="card">
            <div class="card-body">

                <div class="app-brand justify-content-center mb-4">
                    <a href="#" class="app-brand-link gap-2">
                        <span class="app-brand-logo demo">
                            <!-- SVG logo ici -->
                        </span>
                        <span class="app-brand-text demo text-body fw-bolder">{{ config('app.name', 'Laravel') }}</span>
                    </a>
                </div>

                <h4 class="mb-2">Vérification de l'email 📧</h4>

                <p class="mb-4">
                    Merci de vous être inscrit ! Avant de commencer, veuillez vérifier votre adresse e-mail
                    en cliquant sur le lien que nous venons de vous envoyer. <br>
                    Si vous n'avez pas reçu l'e-mail, nous vous en enverrons un autre avec plaisir.
                </p>

                @if (session('status') == 'verification-link-sent')
                    <div class="alert alert-success" role="alert">
                        Un nouveau lien de vérification a été envoyé à votre adresse e-mail.
                    </div>
                @endif
                <div class="d-flex justify-content-between mt-4">
                    <button wire:click="sendVerification" type="button" class="btn btn-primary">
                        <span wire:loading wire:target="sendVerification" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Renvoyer l’e-mail de vérification
                    </button>

                    <button wire:click="logout" type="button" class="btn btn-outline-secondary">
                        <span wire:loading wire:target="logout" class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Se déconnecter
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>
