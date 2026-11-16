<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vérifie, à CHAQUE requête de l'application mobile, que le compte
 * est toujours autorisé. Si l'agence désactive ou suspend un membre,
 * son jeton est supprimé immédiatement.
 */
class EnsureActiveClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! self::canUseMobile($user)) {
            // Révoque le jeton utilisé pour cette requête
            $user?->currentAccessToken()?->delete();

            return response()->json([
                'code' => 'access_denied',
                'message' => 'Accès mobile non autorisé. Adressez-vous à votre agence.',
            ], 403);
        }

        return $next($request);
    }

    /**
     * Règle unique, réutilisée à la connexion et à chaque requête.
     */
    public static function canUseMobile(User $user): bool
    {
        return $user->role === 'membre'
            && (bool) $user->status
            && ! $user->is_suspended
            && $user->mobile_activated_at !== null;
    }
}
