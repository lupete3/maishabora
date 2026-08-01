<?php

namespace App\Livewire\Forms;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate([
        'required',
        'string',
        'email:rfc',
        'max:255',
    ])]
    public string $email = '';

    #[Validate([
        'required',
        'string',
        'min:4',
        'max:255',
    ])]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Authentifier l'utilisateur
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        /**
         * Protection supplémentaire contre
         * les payloads malveillants Livewire
         */
        if (
            !is_string($this->email) ||
            !is_string($this->password)
        ) {

            $this->logSecurityEvent(
                'Payload invalide détecté',
                [
                    'email_type' => gettype($this->email),
                    'password_type' => gettype($this->password),
                ]
            );

            throw ValidationException::withMessages([
                'form.email' => 'Coordonnées invalides.',
            ]);
        }

        /**
         * Nettoyage des données
         */
        $this->email = trim(Str::lower($this->email));
        $this->password = trim($this->password);

        /**
         * Validation Livewire
         */
        $this->validate();

        /**
         * Vérification brute force
         */
        $this->ensureIsNotRateLimited();

        /**
         * Tentative de connexion
         */
        if (!Auth::attempt([
            'email' => $this->email,
            'password' => $this->password,
        ], $this->remember)) {

            RateLimiter::hit($this->throttleKey(), 300);

            $this->logSecurityEvent(
                'Échec connexion',
                [
                    'email' => $this->email,
                ]
            );

            throw ValidationException::withMessages([
                'form.email' => 'Coordonnées invalides.',
            ]);
        }

        /**
         * Utilisateur connecté
         */
        $user = Auth::user();

        /**
         * Vérification sécurité compte
         */
        if (
            !$user ||
            $user->roles->isEmpty() ||
            !$user->status
        ) {

            Auth::logout();

            $this->logSecurityEvent(
                'Compte refusé après authentification',
                [
                    'user_id' => $user?->id,
                    'email' => $user?->email,
                    'status' => $user?->status,
                    'roles_count' => $user?->roles?->count(),
                ]
            );

            /**
             * Message générique
             * pour éviter l'énumération
             */
            throw ValidationException::withMessages([
                'form.email' => 'Accès refusé.',
            ]);
        }

        /**
         * Succès connexion
         */
        RateLimiter::clear($this->throttleKey());

        $this->logSecurityEvent(
            'Connexion réussie',
            [
                'user_id' => $user->id,
                'email' => $user->email,
            ]
        );
    }

    /**
     * Protection brute force
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        $this->logSecurityEvent(
            'Blocage brute force',
            [
                'seconds_remaining' => $seconds,
            ]
        );

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Clé de limitation sécurisée
     */
    protected function throttleKey(): string
    {
        $email = is_string($this->email)
            ? $this->email
            : 'invalid-email';

        return Str::transliterate(
            Str::lower($email) . '|' . request()->ip()
        );
    }

    /**
     * Logs sécurité
     */
    protected function logSecurityEvent(
        string $message,
        array $context = []
    ): void {

        Log::channel('daily')->warning($message, array_merge([
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ], $context));
    }

    /**
     * Messages validation
     */
    public function messages(): array
    {
        return [
            'email.required' => 'L’adresse e-mail est obligatoire.',
            'email.string' => 'Format e-mail invalide.',
            'email.email' => 'Adresse e-mail invalide.',
            'email.max' => 'Adresse e-mail trop longue.',

            'password.required' => 'Le mot de passe est obligatoire.',
            'password.string' => 'Format mot de passe invalide.',
            'password.min' => 'Mot de passe invalide.',
            'password.max' => 'Mot de passe invalide.',

            'remember.boolean' => 'Valeur invalide.',
        ];
    }
}

