<?php

namespace App\Support;

use App\Models\User;

/**
 * Lecture et formatage du code membre.
 *
 * Le code complet est généré par RegisterMember::generateUniqueAccountCode() :
 *
 *     34 2025 0000000258
 *     │   │       └─ numéro séquentiel sur 10 chiffres (ne repart jamais à zéro)
 *     │   └─ année d'adhésion
 *     └─ préfixe fixe
 *
 * Le numéro séquentiel suffit donc à identifier un membre. On en tire un
 * « code court » facile à retenir : MB000258.
 *
 * En base, rien ne change : users.code garde le code complet.
 */
class MemberCode
{
    public const SHORT_PREFIX = 'MB';

    /** Nombre minimum de chiffres affichés dans le code court (MB000258). */
    public const SHORT_DIGITS = 6;

    /** Forme du code complet : 34 + année (4 chiffres) + numéro (10 chiffres). */
    private const FULL_PATTERN = '/^34\d{4}(\d{10})$/';

    /** Ce que le client peut taper comme code court : 258, 000258, MB000258. */
    private const SHORT_PATTERN = '/^(?:MB)?(\d{1,10})$/';

    /**
     * Numéro séquentiel contenu dans un code complet, ou null si le code
     * n'a pas la forme standard (ex. code saisi à la main).
     *
     * 3420250000000258 → 258
     */
    public static function sequence(?string $code): ?int
    {
        if ($code === null || ! preg_match(self::FULL_PATTERN, $code, $m)) {
            return null;
        }

        return (int) $m[1];
    }

    /**
     * Code court à afficher : 3420250000000258 → MB000258.
     */
    public static function short(?string $code): ?string
    {
        $sequence = self::sequence($code);

        return $sequence === null ? null : self::formatShort($sequence);
    }

    /**
     * Nettoie la saisie du client : espaces, tirets et minuscules.
     * " mb 000-258 " → "MB000258"
     */
    public static function clean(string $input): string
    {
        return strtoupper(preg_replace('/[\s\-.]+/', '', $input));
    }

    /**
     * Forme unique d'une saisie, pour que 258, 000258, MB000258 et le code
     * complet comptent comme le MÊME identifiant (anti force brute).
     */
    public static function canonical(string $input): string
    {
        $clean = self::clean($input);

        $sequence = self::sequence($clean) ?? self::shortInputSequence($clean);

        return $sequence === null ? $clean : self::formatShort($sequence);
    }

    /**
     * Retrouve un membre à partir de ce qu'il a tapé.
     * Retourne null si aucun membre, ou si plusieurs membres correspondent
     * (on ne devine jamais).
     */
    public static function findMember(string $input): ?User
    {
        $clean = self::clean($input);

        if ($clean === '') {
            return null;
        }

        $members = User::query()->where('role', 'membre');

        // 1. Code complet tapé tel quel
        $exact = (clone $members)->where('code', $clean)->first();
        if ($exact) {
            return $exact;
        }

        // 2. Code court : on cherche 34 + n'importe quelle année + le numéro
        $sequence = self::shortInputSequence($clean);
        if ($sequence === null || $sequence === 0) {
            return null;
        }

        // "_" remplace exactement un caractère dans un LIKE SQL
        $pattern = '34____' . str_pad((string) $sequence, 10, '0', STR_PAD_LEFT);

        $matches = (clone $members)->where('code', 'like', $pattern)->limit(2)->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    /**
     * 258 / 000258 / MB000258 → 258 ; autre chose → null
     */
    private static function shortInputSequence(string $clean): ?int
    {
        return preg_match(self::SHORT_PATTERN, $clean, $m) ? (int) $m[1] : null;
    }

    private static function formatShort(int $sequence): string
    {
        return self::SHORT_PREFIX . str_pad((string) $sequence, self::SHORT_DIGITS, '0', STR_PAD_LEFT);
    }
}
