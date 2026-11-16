<?php

namespace App\Services;

use App\Http\Middleware\EnsurePasswordChanged;
use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

/**
 * Création des jetons de l'application mobile, au même endroit pour la
 * connexion et pour le changement de mot de passe.
 */
class ClientTokenIssuer
{
    /** Durée de vie d'un jeton, en jours. */
    public const TOKEN_DAYS = 30;

    /**
     * @param  bool  $fullAccess  false = jeton limité au changement de mot de passe
     */
    public function issue(User $user, string $deviceName, bool $fullAccess): NewAccessToken
    {
        // Un seul jeton par appareil : on remplace l'ancien s'il existe
        $user->tokens()->where('name', $deviceName)->delete();

        return $user->createToken(
            $deviceName,
            [$fullAccess
                ? EnsurePasswordChanged::ABILITY_FULL
                : EnsurePasswordChanged::ABILITY_PASSWORD_ONLY],
            now()->addDays(self::TOKEN_DAYS),
        );
    }

    /**
     * Réponse JSON commune : jeton + date d'expiration.
     */
    public static function payload(NewAccessToken $token): array
    {
        return [
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
        ];
    }
}
