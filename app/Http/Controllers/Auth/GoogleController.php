<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();
        } catch (\Throwable $e) {
            notyf()->error('La connexion avec Google a échoué. Veuillez réessayer.');
            return redirect()
                ->route('login');
        }

        $email = $googleUser->getEmail();

        if (!$email) {
            notyf()->error('Impossible de récupérer l’adresse e-mail depuis Google. Veuillez réessayer.');
            throw ValidationException::withMessages([
                'email' => 'Impossible de récupérer l’adresse e-mail depuis Google.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Vérification de l'adresse email Google
        |--------------------------------------------------------------------------
        */

        if (!data_get($googleUser->user, 'email_verified')) {
            notyf()->error('Votre adresse e-mail Google n’est pas vérifiée.');
            return redirect()
                ->route('login');
        }

        /*
        |--------------------------------------------------------------------------
        | Recherche du membre Maisha Bora
        |
        | Conditions :
        | - Email déjà enregistré
        | - Compte actif
        | - Au moins un rôle Spatie assigné
        |--------------------------------------------------------------------------
        */

        $user = User::where('email', $email)
            ->where('status', true)
            ->where('is_suspended', false)
            ->whereHas('roles')
            ->first();

        if (!$user) {
            notyf()->error('Cette adresse e-mail ne correspond pas à un compte Maisha Bora actif et autorisé.');
            return redirect()
                ->route('login');
        }

        /*
        |--------------------------------------------------------------------------
        | Vérification du Google ID
        |--------------------------------------------------------------------------
        */

        $googleId = $googleUser->getId();

        if ($user->google_id && $user->google_id !== $googleId) {
            notyf()->error('Ce compte Maisha Bora est déjà associé à un autre compte Google.');
            return redirect()
                ->route('login');
        }

        /*
        |--------------------------------------------------------------------------
        | Première connexion Google
        |--------------------------------------------------------------------------
        */

        if (!$user->google_id) {
            $user->update([
                'google_id' => $googleId,
                'provider' => 'google',
            ]);
        }

        Auth::login($user, true);

        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}