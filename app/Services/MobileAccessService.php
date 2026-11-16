<?php

namespace App\Services;

use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Gestion de l'accès d'un membre à l'application mobile, côté agence.
 *
 * - activate()   : active l'accès (ou le réactive si le membre a oublié son
 *                  mot de passe) et génère un code provisoire à 6 chiffres.
 * - deactivate() : coupe l'accès et déconnecte tous les appareils.
 *
 * Le code provisoire n'est jamais stocké en clair : seule son empreinte
 * (hash) est enregistrée. Il est montré une seule fois à l'agent.
 */
class MobileAccessService
{
    /** Durée de validité du code provisoire, en heures. */
    public const TEMP_CODE_HOURS = 72;

    /**
     * Retourne le code provisoire en clair, à remettre au membre.
     *
     * @throws DomainException si le membre ne peut pas avoir d'accès mobile
     */
    public function activate(User $member): string
    {
        $this->ensureEligible($member);

        // random_int est un générateur cryptographiquement sûr
        $code = (string) random_int(100000, 999999);

        DB::transaction(function () use ($member, $code) {
            $member->forceFill([
                'password' => Hash::make($code),
                'must_change_password' => true,
                'temporary_password_expires_at' => now()->addHours(self::TEMP_CODE_HOURS),
                // On garde la date de première activation si elle existe
                'mobile_activated_at' => $member->mobile_activated_at ?? now(),
            ])->saveQuietly(); // saveQuietly : évite le contrôle de doublon du modèle User

            // Tout appareil déjà connecté est déconnecté
            $member->tokens()->delete();
        });

        return $code;
    }

    public function deactivate(User $member): void
    {
        DB::transaction(function () use ($member) {
            $member->forceFill([
                'mobile_activated_at' => null,
                'temporary_password_expires_at' => null,
            ])->saveQuietly();

            $member->tokens()->delete();
        });
    }

    private function ensureEligible(User $member): void
    {
        if ($member->role !== 'membre') {
            throw new DomainException('Seuls les membres peuvent avoir un accès mobile.');
        }

        if (! $member->status || $member->is_suspended) {
            throw new DomainException('Ce membre est inactif ou suspendu : réactivez d\'abord son compte.');
        }
    }
}
