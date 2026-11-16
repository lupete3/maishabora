<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureActiveClient;
use App\Http\Requests\Api\V1\Client\LoginRequest;
use App\Http\Resources\Api\V1\Client\ClientProfileResource;
use App\Services\ClientTokenIssuer;
use App\Support\MemberCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /** Mot de passe attribué par défaut à la création d'un membre. */
    private const DEFAULT_PASSWORD = '1234';

    /** Nombre d'essais autorisés avant blocage. */
    private const MAX_ATTEMPTS = 5;

    /** Durée du blocage en secondes (identique à la connexion web). */
    private const LOCK_SECONDS = 300;

    /**
     * POST /api/v1/client/auth/login
     */
    public function login(LoginRequest $request, ClientTokenIssuer $tokens): JsonResponse
    {
        $code = $request->string('code')->toString();
        $password = $request->string('password')->toString();

        // Clé de blocage sur la forme canonique : 258, 000258, MB000258 et le
        // code complet partagent le même compteur d'essais.
        $throttleKey = Str::transliterate(Str::lower(MemberCode::canonical($code)) . '|' . $request->ip());

        // 1. Protection contre la force brute
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->logSecurityEvent($request, 'API mobile : blocage brute force', ['code' => $code]);

            return response()->json([
                'code' => 'too_many_attempts',
                'message' => 'Trop de tentatives. Réessayez dans ' . ceil($seconds / 60) . ' minute(s).',
                'retry_after' => $seconds,
            ], 429);
        }

        // 2. Recherche du membre (code complet ou code court, rôle membre uniquement)
        $user = MemberCode::findMember($code);

        // 3. Code inconnu ou mauvais mot de passe : même message dans les deux
        //    cas, pour ne pas révéler quels codes existent.
        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, self::LOCK_SECONDS);
            $this->logSecurityEvent($request, 'API mobile : échec connexion', ['code' => $code]);

            return response()->json([
                'code' => 'invalid_credentials',
                'message' => 'Code membre ou mot de passe incorrect.',
            ], 422);
        }

        // 4. Compte inactif, suspendu ou accès mobile jamais activé en agence
        if (! EnsureActiveClient::canUseMobile($user)) {
            $this->logSecurityEvent($request, 'API mobile : compte refusé', ['user_id' => $user->id]);

            return response()->json([
                'code' => 'access_denied',
                'message' => 'Accès mobile non autorisé. Adressez-vous à votre agence.',
            ], 403);
        }

        RateLimiter::clear($throttleKey);

        // 4 bis. Code provisoire remis en agence mais plus valable
        if ($user->temporaryPasswordExpired()) {
            return response()->json([
                'code' => 'temporary_password_expired',
                'message' => 'Votre code provisoire a expiré. Demandez-en un nouveau à votre agence.',
            ], 403);
        }

        // 5. Le membre doit-il changer son mot de passe ?
        //    Oui si l'agence lui a remis un code temporaire, ou s'il utilise
        //    encore le mot de passe par défaut.
        $mustChange = $user->must_change_password || $password === self::DEFAULT_PASSWORD;

        if ($mustChange && ! $user->must_change_password) {
            // saveQuietly : n'exécute pas le contrôle de doublon du modèle User,
            // prévu pour les formulaires web
            $user->forceFill(['must_change_password' => true])->saveQuietly();
        }

        // 6. Jeton : complet, ou limité au changement de mot de passe
        $deviceName = $request->string('device_name')->toString();
        $token = $tokens->issue($user, $deviceName, fullAccess: ! $mustChange);

        $this->logSecurityEvent($request, 'API mobile : connexion réussie', [
            'user_id' => $user->id,
            'device' => $deviceName,
        ]);

        return response()->json(ClientTokenIssuer::payload($token) + [
            'must_change_password' => $mustChange,
            'client' => new ClientProfileResource($user->load('agent')),
        ]);
    }

    /**
     * POST /api/v1/client/auth/logout
     * Supprime uniquement le jeton de l'appareil qui fait la demande.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    /**
     * GET /api/v1/client/me
     */
    public function me(Request $request): ClientProfileResource
    {
        return new ClientProfileResource($request->user()->load('agent'));
    }

    /**
     * Journal de sécurité, dans le même fichier que la connexion web.
     */
    private function logSecurityEvent(Request $request, string $message, array $context = []): void
    {
        Log::channel('daily')->warning($message, array_merge([
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ], $context));
    }
}
