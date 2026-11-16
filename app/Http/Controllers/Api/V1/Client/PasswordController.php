<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\ChangePasswordRequest;
use App\Http\Resources\Api\V1\Client\ClientProfileResource;
use App\Services\ClientTokenIssuer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PasswordController extends Controller
{
    /**
     * PUT /api/v1/client/password
     *
     * Accessible avec un jeton limité (premier changement obligatoire)
     * comme avec un jeton complet (changement volontaire).
     */
    public function update(ChangePasswordRequest $request, ClientTokenIssuer $tokens): JsonResponse
    {
        $user = $request->user();

        // 1. Code provisoire expiré : le membre doit repasser en agence
        if ($user->temporaryPasswordExpired()) {
            $user->currentAccessToken()->delete();

            return response()->json([
                'code' => 'temporary_password_expired',
                'message' => 'Votre code provisoire a expiré. Demandez-en un nouveau à votre agence.',
            ], 403);
        }

        // 2. Vérifie le mot de passe actuel (ou le code provisoire)
        if (! Hash::check($request->string('current_password')->toString(), $user->password)) {
            return response()->json([
                'code' => 'invalid_current_password',
                'message' => 'Mot de passe actuel incorrect.',
                'errors' => ['current_password' => ['Mot de passe actuel incorrect.']],
            ], 422);
        }

        $deviceName = $user->currentAccessToken()->name;

        // 3. Enregistre le nouveau mot de passe et déconnecte TOUS les appareils
        DB::transaction(function () use ($user, $request) {
            $user->forceFill([
                'password' => Hash::make($request->string('password')->toString()),
                'must_change_password' => false,
                'temporary_password_expires_at' => null,
            ])->saveQuietly();

            $user->tokens()->delete();
        });

        // 4. L'appareil actuel reçoit un nouveau jeton complet : le membre
        //    reste connecté et accède enfin à ses données.
        $token = $tokens->issue($user, $deviceName, fullAccess: true);

        Log::channel('daily')->warning('API mobile : mot de passe changé', [
            'user_id' => $user->id,
            'device' => $deviceName,
            'ip' => $request->ip(),
        ]);

        return response()->json(ClientTokenIssuer::payload($token) + [
            'message' => 'Mot de passe modifié avec succès.',
            'must_change_password' => false,
            'client' => new ClientProfileResource($user->fresh()->load('agent')),
        ]);
    }
}
