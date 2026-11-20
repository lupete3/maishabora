<?php

namespace App\Support;

use App\Models\ExchangeRate;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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

    /**
     * Catégories de comptes (filtre « Catégorie »).
     * « Dormant » est calculé : aucune opération sur le compte depuis N mois (config comptes.dormant_after_months).
     */
    public const CATEGORIES = [
        'tous'     => 'Tous les comptes',
        'actifs'   => 'Comptes actifs',
        'inactifs' => 'Comptes inactifs (statut « Inactif »)',
        'dormants' => 'Comptes dormants (sans opération récente)',
        'negatifs' => 'Soldes négatifs à régulariser',
    ];

    public static function normalizeCategory(?string $category): string
    {
        return array_key_exists((string) $category, self::CATEGORIES) ? $category : 'tous';
    }

    /** Nature du titulaire : client (par défaut), interne, test, ou tous. */
    public static function natures(): array
    {
        return User::CLIENT_CATEGORIES + ['tous' => 'Toutes natures'];
    }

    public static function normalizeNature(?string $nature): string
    {
        return array_key_exists((string) $nature, self::natures()) ? $nature : 'client';
    }

    /** Nombre de mois sans opération à partir duquel un compte est dormant. */
    public static function dormantMonths(): int
    {
        return max(1, (int) config('comptes.dormant_after_months', 12));
    }

    /** Filtre commun des comptes pris en compte (type, devise, solde minimum, catégorie). */
    public static function accountFilter(string $accountType, string $currencyFilter, $minBalance, string $category = 'tous'): Closure
    {
        $dormantSince = now()->subMonths(self::dormantMonths());

        return function ($q) use ($accountType, $currencyFilter, $minBalance, $category, $dormantSince) {
            if ($accountType !== 'all') {
                $q->where('accounts.type', $accountType);
            }
            if ($currencyFilter !== 'all') {
                $q->where('accounts.currency', $currencyFilter);
            }
            if ((float) $minBalance > 0) {
                $q->where('accounts.balance', '>=', $minBalance);
            }

            match ($category) {
                'actifs'   => $q->where('accounts.status', 'Actif'),
                'inactifs' => $q->where('accounts.status', 'Inactif'),
                // Aucune opération sur le compte depuis la date limite (index transactions account_id + created_at)
                'dormants' => $q->whereNotExists(function ($sub) use ($dormantSince) {
                    $sub->selectRaw('1')
                        ->from('transactions')
                        ->whereColumn('transactions.account_id', 'accounts.id')
                        ->where('transactions.created_at', '>=', $dormantSince);
                }),
                'negatifs' => $q->where('accounts.balance', '<', 0),
                default    => null,
            };
        };
    }

    /**
     * « Ce que nous devons aux clients », par devise, sur TOUTE l'institution (indépendant des filtres) :
     *  - du       : somme des soldes POSITIFS des comptes de vrais clients (actifs, inactifs, dormants confondus)
     *  - negatifs : soldes négatifs des clients, affichés à part (ce n'est pas une dette envers eux)
     *  - exclus   : soldes positifs des comptes internes et de test, non comptés
     *
     * @return array<string, array{du: float, nb_du: int, negatifs: float, nb_negatifs: int, exclus: float}>
     */
    public static function liabilities(): array
    {
        $rows = DB::table('accounts')
            ->join('users', 'users.id', '=', 'accounts.user_id')
            ->where('users.role', 'membre')
            ->groupBy('accounts.currency')
            ->selectRaw("accounts.currency AS currency,
                SUM(CASE WHEN users.client_category = 'client' AND accounts.balance > 0 THEN accounts.balance ELSE 0 END) AS du,
                SUM(CASE WHEN users.client_category = 'client' AND accounts.balance > 0 THEN 1 ELSE 0 END) AS nb_du,
                SUM(CASE WHEN users.client_category = 'client' AND accounts.balance < 0 THEN accounts.balance ELSE 0 END) AS negatifs,
                SUM(CASE WHEN users.client_category = 'client' AND accounts.balance < 0 THEN 1 ELSE 0 END) AS nb_negatifs,
                SUM(CASE WHEN users.client_category <> 'client' AND accounts.balance > 0 THEN accounts.balance ELSE 0 END) AS exclus")
            ->get();

        $result = [];
        foreach (['USD', 'CDF'] as $currency) {
            $row = $rows->firstWhere('currency', $currency);
            $result[$currency] = [
                'du'          => (float) ($row->du ?? 0),
                'nb_du'       => (int) ($row->nb_du ?? 0),
                'negatifs'    => (float) ($row->negatifs ?? 0),
                'nb_negatifs' => (int) ($row->nb_negatifs ?? 0),
                'exclus'      => (float) ($row->exclus ?? 0),
            ];
        }

        return $result;
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

    /** Tailles de lot proposées pour le PDF (le maximum protège le serveur). */
    public const LOT_SIZES = [50, 100, 200];

    public const DEFAULT_LOT_SIZE = 100;

    public static function normalizeLotSize($size): int
    {
        return in_array((int) $size, self::LOT_SIZES, true) ? (int) $size : self::DEFAULT_LOT_SIZE;
    }

    /**
     * Totaux des comptes des membres filtrés, calculés en une passe SQL
     * (au lieu de relire tous les membres en PHP).
     *
     * @param Builder $members requête des membres filtrés (sans tri)
     * @return array{current_usd: float, current_cdf: float, savings_usd: float, savings_cdf: float, positive: array}
     */
    public static function totals(Builder $members, Closure $accountFilter, ?float $rate): array
    {
        $memberIds = (clone $members)->toBase()->select('users.id');

        $accounts = DB::table('accounts')->whereIn('user_id', $memberIds);
        $accountFilter($accounts);

        // Totaux par type et devise (cartes en haut de page)
        $rows = (clone $accounts)
            ->selectRaw('type, currency, COALESCE(SUM(balance), 0) AS total')
            ->groupBy('type', 'currency')
            ->get();

        $totals = ['current_usd' => 0.0, 'current_cdf' => 0.0, 'savings_usd' => 0.0, 'savings_cdf' => 0.0];
        foreach ($rows as $row) {
            $key = "{$row->type}_" . strtolower($row->currency);
            if (array_key_exists($key, $totals)) {
                $totals[$key] = (float) $row->total;
            }
        }

        // Encours créditeur : somme des soldes positifs par membre (USD, CDF, total converti)
        $parMembre = (clone $accounts)
            ->selectRaw("user_id,
                SUM(CASE WHEN currency = 'USD' THEN balance ELSE 0 END) AS usd,
                SUM(CASE WHEN currency = 'CDF' THEN balance ELSE 0 END) AS cdf")
            ->groupBy('user_id');

        $positive = DB::query()->fromSub($parMembre, 'm')
            ->selectRaw('COALESCE(SUM(GREATEST(usd, 0)), 0) AS usd, COALESCE(SUM(GREATEST(cdf, 0)), 0) AS cdf, COALESCE(SUM(GREATEST(usd + cdf / ?, 0)), 0) AS total', [$rate ?: 1])
            ->first();

        $totals['positive'] = [
            'usd'   => (float) $positive->usd,
            'cdf'   => (float) $positive->cdf,
            // Sans taux, le "total" se replie sur l'USD (même règle que le tri)
            'total' => $rate ? (float) $positive->total : (float) $positive->usd,
        ];

        return $totals;
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

    /**
     * Part d'un membre dans l'encours créditeur (somme des soldes positifs, voir totals()), en %.
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
