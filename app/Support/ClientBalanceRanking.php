<?php

namespace App\Support;

use App\Models\ExchangeRate;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Classement des membres selon le solde de leurs comptes (page /comptes-membres et ses exports).
 *
 * Le tri est fait en SQL (sous-requêtes de somme) : il porte donc sur tous les membres,
 * pas seulement sur la page affichée.
 */
class ClientBalanceRanking
{
    public const SORTS = [
        'total' => 'Plus gros soldes (total converti en USD)',
        'usd'   => 'Plus gros soldes USD',
        'cdf'   => 'Plus gros soldes CDF',
        'name'  => 'Ordre alphabétique',
    ];

    /** Normalise la valeur reçue (requête, propriété Livewire). */
    public static function normalize(?string $sortBy): string
    {
        return array_key_exists((string) $sortBy, self::SORTS) ? $sortBy : 'name';
    }

    public static function isBalanceSort(string $sortBy): bool
    {
        return $sortBy !== 'name';
    }

    /** Filtre commun des comptes pris en compte (type, devise, solde minimum). */
    public static function accountFilter(string $accountType, string $currencyFilter, $minBalance): Closure
    {
        return function ($q) use ($accountType, $currencyFilter, $minBalance) {
            if ($accountType !== 'all') {
                $q->where('type', $accountType);
            }
            if ($currencyFilter !== 'all') {
                $q->where('currency', $currencyFilter);
            }
            if ((float) $minBalance > 0) {
                $q->where('balance', '>=', $minBalance);
            }
        };
    }

    /** Dernier taux USD → CDF enregistré, ou null s'il n'y en a pas. */
    public static function usdToCdfRate(): ?float
    {
        $rate = ExchangeRate::getLatestRate('USD', 'CDF');

        return $rate && (float) $rate->rate > 0 ? (float) $rate->rate : null;
    }

    /**
     * Ajoute les colonnes solde_usd / solde_cdf (sommes filtrées) et l'ordre demandé.
     */
    public static function apply(Builder $query, string $sortBy, Closure $accountFilter, ?float $rate): Builder
    {
        $query->withSum(['accounts as solde_usd' => function ($q) use ($accountFilter) {
            $accountFilter($q);
            $q->where('currency', 'USD');
        }], 'balance')
            ->withSum(['accounts as solde_cdf' => function ($q) use ($accountFilter) {
                $accountFilter($q);
                $q->where('currency', 'CDF');
            }], 'balance');

        // Sans taux de change, le total converti se replie sur le solde USD
        if ($sortBy === 'total' && !$rate) {
            $sortBy = 'usd';
        }

        match ($sortBy) {
            'usd'   => $query->orderByRaw('COALESCE(solde_usd, 0) DESC'),
            'cdf'   => $query->orderByRaw('COALESCE(solde_cdf, 0) DESC'),
            'total' => $query->orderByRaw('(COALESCE(solde_usd, 0) + COALESCE(solde_cdf, 0) / ?) DESC', [$rate]),
            default => null,
        };

        // Départage stable (et ordre principal pour le tri alphabétique)
        return $query->orderBy('name')->orderBy('postnom')->orderBy('users.id');
    }

    /**
     * Soldes USD / CDF d'un membre, sur ses comptes chargés (déjà filtrés).
     * Même base que le tri SQL (solde_usd / solde_cdf), tous types de comptes confondus.
     */
    public static function amountsOf($member): array
    {
        return [
            'usd' => (float) $member->accounts->where('currency', 'USD')->sum('balance'),
            'cdf' => (float) $member->accounts->where('currency', 'CDF')->sum('balance'),
        ];
    }

    /** Accumulateur des soldes positifs, base de calcul de la part de l'encours. */
    public static function emptyPositiveTotals(): array
    {
        return ['usd' => 0.0, 'cdf' => 0.0, 'total' => 0.0];
    }

    /** Ajoute un membre ($amounts = usd, cdf) aux totaux positifs (les soldes négatifs sont ignorés). */
    public static function addPositive(array &$positiveTotals, array $amounts, ?float $rate): void
    {
        foreach (['usd', 'cdf', 'total'] as $critere) {
            $positiveTotals[$critere] += max(0, (float) self::value($critere, $amounts, $rate));
        }
    }

    /**
     * Part d'un membre dans l'encours créditeur (somme des soldes positifs), en %.
     * Null pour un tri alphabétique ou un solde nul / négatif.
     */
    public static function share(string $sortBy, array $amounts, array $positiveTotals, ?float $rate): ?float
    {
        $valeur = self::value($sortBy, $amounts, $rate);
        $total = $positiveTotals[$sortBy] ?? 0;

        if ($valeur === null || $valeur <= 0 || $total <= 0) {
            return null;
        }

        return round($valeur / $total * 100, 2);
    }

    /** Valeur classée : solde USD, solde CDF ou total converti en USD. */
    public static function value(string $sortBy, array $amounts, ?float $rate): ?float
    {
        return match ($sortBy) {
            'usd'   => (float) $amounts['usd'],
            'cdf'   => (float) $amounts['cdf'],
            'total' => $rate ? (float) $amounts['usd'] + (float) $amounts['cdf'] / $rate : (float) $amounts['usd'],
            default => null,
        };
    }
}
