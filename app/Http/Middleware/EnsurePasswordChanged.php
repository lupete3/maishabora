<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloque l'accès aux données tant que le membre n'a pas remplacé
 * son mot de passe temporaire ou par défaut.
 *
 * À la connexion, un membre dans ce cas reçoit un jeton "limité"
 * (capacité password:change uniquement). Seul un jeton "complet"
 * (capacité client) passe ce contrôle.
 */
class EnsurePasswordChanged
{
    public const ABILITY_FULL = 'client';

    public const ABILITY_PASSWORD_ONLY = 'password:change';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->tokenCan(self::ABILITY_FULL)) {
            return response()->json([
                'code' => 'password_change_required',
                'message' => 'Vous devez d\'abord changer votre mot de passe.',
            ], 403);
        }

        return $next($request);
    }
}
