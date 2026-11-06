<?php

namespace App\Services;

use App\Models\Objectif;
use App\Models\ObjectifIndicateur;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Calcule le réalisé des indicateurs du module Objectifs et la progression vers chaque objectif.
 *
 * Règles de rattachement :
 *  - Opérations d'un agent : transactions dont user_id = l'agent (écriture côté caisse agent).
 *  - Opérations globales : écritures côté compte membre (account_id renseigné), pour ne pas
 *    compter les comptes techniques (frais d'adhésion, retenues...).
 *  - Clientèle : membres (role = membre) rattachés via users.agent_id.
 *  - Carnets : membership_cards.user_id (agent responsable), hors carnets annulés.
 *  - Crédit : credits.agent_id pour un agent, tous les crédits pour le global.
 */
class ObjectifProgressService
{
    public const MISE_TYPES    = ['mise_quotidienne', 'encaissement_mise'];
    public const DEPOT_TYPES   = ['depot', 'dépôt'];
    public const RETRAIT_TYPES = ['retrait', 'retrait_carte_adhesion'];
    public const CARNET_TYPES  = ['vente_carte_adhesion'];

    /** Cache des calculs pour la durée de la requête */
    private array $memo = [];

    // ------------------------------------------------------------------
    // Catalogue & agents
    // ------------------------------------------------------------------

    /**
     * Indicateurs du catalogue, triés par groupe puis ordre paramétré.
     * @param string|null $scope 'agent' | 'global' | null (tous)
     */
    public function indicateurs(?string $scope = null, bool $activeOnly = true): array
    {
        $settings = ObjectifIndicateur::all()->keyBy('key');
        $groupOrder = array_flip(array_keys(config('objectifs.groupes')));
        $position = 0;
        $list = [];

        foreach (config('objectifs.indicateurs') as $key => $def) {
            $setting = $settings->get($key);
            $def['key'] = $key;
            $def['is_active'] = $setting ? $setting->is_active : true;
            $def['sort_order'] = $setting && $setting->sort_order ? $setting->sort_order : ++$position;

            if ($activeOnly && !$def['is_active']) {
                continue;
            }
            if ($scope && !in_array($scope, $def['scopes'], true)) {
                continue;
            }
            $list[$key] = $def;
        }

        uasort($list, fn ($a, $b) => [$groupOrder[$a['group']] ?? 99, $a['sort_order']]
            <=> [$groupOrder[$b['group']] ?? 99, $b['sort_order']]);

        return $list;
    }

    /** Agents collecteurs : comptes actifs, non membres, avec une caisse visible au tableau de bord. */
    public function agents(): Collection
    {
        return User::where('status', true)
            ->where('role', '!=', 'membre')
            ->whereHas('agentAccounts', fn ($q) => $q->where('is_visible_dashboard', true))
            ->orderBy('name')
            ->get(['id', 'name', 'postnom', 'prenom']);
    }

    // ------------------------------------------------------------------
    // Périodes
    // ------------------------------------------------------------------

    /** @return array{0: Carbon, 1: Carbon, 2: string} début, fin, libellé */
    public static function periode(string $type, Carbon|string|null $reference = null): array
    {
        $ref = $reference ? Carbon::parse($reference) : now();
        $ref = $ref->copy()->locale('fr');

        return match ($type) {
            'week' => [
                $ref->copy()->startOfWeek(), $ref->copy()->endOfWeek(),
                'Semaine du ' . $ref->copy()->startOfWeek()->format('d/m/Y') . ' au ' . $ref->copy()->endOfWeek()->format('d/m/Y'),
            ],
            'quarter' => [
                $ref->copy()->startOfQuarter(), $ref->copy()->endOfQuarter(),
                'T' . $ref->quarter . ' ' . $ref->year,
            ],
            'semester' => (function () use ($ref) {
                $start = $ref->copy()->month($ref->month <= 6 ? 1 : 7)->startOfMonth();
                $end = $start->copy()->addMonths(5)->endOfMonth();
                return [$start, $end, 'S' . ($ref->month <= 6 ? 1 : 2) . ' ' . $ref->year];
            })(),
            'year' => [
                $ref->copy()->startOfYear(), $ref->copy()->endOfYear(), 'Année ' . $ref->year,
            ],
            'month' => [
                $ref->copy()->startOfMonth(), $ref->copy()->endOfMonth(),
                Str::ucfirst($ref->isoFormat('MMMM YYYY')),
            ],
            default => throw new InvalidArgumentException("Type de période inconnu : {$type}"),
        };
    }

    /** Début de la période précédente du même type. */
    public static function periodePrecedente(string $type, Carbon $start): Carbon
    {
        return match ($type) {
            'week'     => $start->copy()->subWeek(),
            'quarter'  => $start->copy()->subQuarter(),
            'semester' => $start->copy()->subMonths(6),
            'year'     => $start->copy()->subYear(),
            default    => $start->copy()->subMonth(),
        };
    }

    /** Part du temps écoulé dans la période (0 à 1). */
    public static function tempsEcoule(Carbon $start, Carbon $end, ?Carbon $today = null): float
    {
        $today = ($today ?? now())->copy()->endOfDay();

        if ($today->lt($start)) {
            return 0.0;
        }
        if ($today->gte($end)) {
            return 1.0;
        }

        $total = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;
        $elapsed = $start->copy()->startOfDay()->diffInDays($today->copy()->startOfDay()) + 1;

        return min(1, max(0, $elapsed / $total));
    }

    // ------------------------------------------------------------------
    // Calcul du réalisé
    // ------------------------------------------------------------------

    /**
     * Réalisé d'un indicateur.
     * @param array|null $agentIds null = valeur globale entreprise (float) ; sinon [agentId => float]
     */
    public function mesurer(string $indicator, Carbon $start, Carbon $end, ?string $currency = null, ?array $agentIds = null): float|array
    {
        $def = config("objectifs.indicateurs.{$indicator}");
        if (!$def) {
            throw new InvalidArgumentException("Indicateur inconnu : {$indicator}");
        }

        $agentIds = $agentIds === null ? null : array_values(array_unique(array_map('intval', $agentIds)));
        $cacheKey = implode('|', [$indicator, $start->toDateString(), $end->toDateString(), $currency, $agentIds === null ? 'G' : implode(',', $agentIds)]);

        if (!array_key_exists($cacheKey, $this->memo)) {
            $method = Str::camel($indicator);
            $start = $start->copy()->startOfDay();
            $end = $end->copy()->endOfDay();
            $result = $this->{$method}($start, $end, $currency, $agentIds);

            if ($agentIds !== null) {
                $filled = array_fill_keys($agentIds, 0.0);
                foreach ((array) $result as $id => $value) {
                    if (array_key_exists((int) $id, $filled)) {
                        $filled[(int) $id] = (float) $value;
                    }
                }
                $result = $filled;
            } else {
                $result = (float) $result;
            }

            $this->memo[$cacheKey] = $result;
        }

        return $this->memo[$cacheKey];
    }

    /**
     * Agrège une requête soit globalement, soit par agent.
     */
    private function agreger(Builder $query, string $expression, ?string $agentColumn, ?array $agentIds): float|array
    {
        if ($agentIds === null) {
            return (float) ($query->selectRaw("{$expression} AS v")->value('v') ?? 0);
        }

        if (empty($agentIds)) {
            return [];
        }

        return $query->whereIn($agentColumn, $agentIds)
            ->selectRaw("{$agentColumn} AS aid, {$expression} AS v")
            ->groupBy($agentColumn)
            ->pluck('v', 'aid')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /** Transactions d'opérations : côté agent (par agent) ou côté compte membre (global). */
    private function transactions(array $types, Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): Builder
    {
        $query = DB::table('transactions')
            ->whereIn('type', $types)
            ->whereBetween('created_at', [$start, $end]);

        if ($currency) {
            $query->where('currency', $currency);
        }

        if ($agentIds === null) {
            $query->whereNotNull('account_id');
        }

        return $query;
    }

    // ---- Activité ----

    protected function operationsNombre(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        $types = array_merge(self::MISE_TYPES, self::DEPOT_TYPES, self::RETRAIT_TYPES);

        if ($agentIds !== null) {
            return $this->agreger(
                $this->transactions(array_merge($types, self::CARNET_TYPES), $start, $end, null, $agentIds),
                'COUNT(*)', 'user_id', $agentIds
            );
        }

        // Global : opérations sur comptes membres + carnets vendus
        return $this->agreger($this->transactions($types, $start, $end, null, null), 'COUNT(*)', null, null)
            + $this->carnetsVendus($start, $end, null, null);
    }

    // ---- Collecte & épargne ----

    protected function misesNombre(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        return $this->agreger($this->transactions(self::MISE_TYPES, $start, $end, $currency, $agentIds), 'COUNT(*)', 'user_id', $agentIds);
    }

    protected function misesMontant(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        return $this->agreger($this->transactions(self::MISE_TYPES, $start, $end, $currency, $agentIds), 'COALESCE(SUM(amount),0)', 'user_id', $agentIds);
    }

    protected function depotsNombre(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        return $this->agreger($this->transactions(self::DEPOT_TYPES, $start, $end, $currency, $agentIds), 'COUNT(*)', 'user_id', $agentIds);
    }

    protected function depotsMontant(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        return $this->agreger($this->transactions(self::DEPOT_TYPES, $start, $end, $currency, $agentIds), 'COALESCE(SUM(amount),0)', 'user_id', $agentIds);
    }

    protected function collecteTotale(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        return $this->agreger(
            $this->transactions(array_merge(self::MISE_TYPES, self::DEPOT_TYPES), $start, $end, $currency, $agentIds),
            'COALESCE(SUM(amount),0)', 'user_id', $agentIds
        );
    }

    protected function retraitsMontant(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        return $this->agreger($this->transactions(self::RETRAIT_TYPES, $start, $end, $currency, $agentIds), 'COALESCE(SUM(amount),0)', 'user_id', $agentIds);
    }

    protected function epargneNette(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        $in = $this->collecteTotale($start, $end, $currency, $agentIds);
        $out = $this->retraitsMontant($start, $end, $currency, $agentIds);

        if ($agentIds === null) {
            return $in - $out;
        }

        $net = [];
        foreach ($agentIds as $id) {
            $net[$id] = ($in[$id] ?? 0) - ($out[$id] ?? 0);
        }

        return $net;
    }

    protected function encoursEpargne(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        $query = DB::table('accounts')
            ->join('users', 'users.id', '=', 'accounts.user_id')
            ->where('users.role', 'membre');

        if ($currency) {
            $query->where('accounts.currency', $currency);
        }

        return $this->agreger($query, 'COALESCE(SUM(accounts.balance),0)', 'users.agent_id', $agentIds);
    }

    // ---- Clientèle ----

    private function membres(): Builder
    {
        return DB::table('users')->where('role', 'membre');
    }

    protected function clientsSensibilises(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        return $this->agreger(
            $this->membres()->whereBetween('created_at', [$start, $end]),
            'COUNT(*)', 'agent_id', $agentIds
        );
    }

    protected function portefeuilleClients(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        return $this->agreger($this->membres()->where('created_at', '<=', $end), 'COUNT(*)', 'agent_id', $agentIds);
    }

    protected function clientsActifs(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        $query = DB::table('transactions')
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->join('users', 'users.id', '=', 'accounts.user_id')
            ->where('users.role', 'membre')
            ->whereIn('transactions.type', array_merge(self::MISE_TYPES, self::DEPOT_TYPES))
            ->whereBetween('transactions.created_at', [$start, $end]);

        return $this->agreger($query, 'COUNT(DISTINCT users.id)', 'users.agent_id', $agentIds);
    }

    protected function tauxActivite(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        $actifs = $this->clientsActifs($start, $end, $currency, $agentIds);
        $total = $this->portefeuilleClients($start, $end, $currency, $agentIds);

        if ($agentIds === null) {
            return $total > 0 ? round($actifs / $total * 100, 2) : 0;
        }

        $taux = [];
        foreach ($agentIds as $id) {
            $taux[$id] = ($total[$id] ?? 0) > 0 ? round(($actifs[$id] ?? 0) / $total[$id] * 100, 2) : 0;
        }

        return $taux;
    }

    // ---- Carnets ----

    private function carnets(Carbon $start, Carbon $end, ?string $currency): Builder
    {
        $query = DB::table('membership_cards')
            ->whereNull('cancelled_at')
            ->whereBetween('created_at', [$start, $end]);

        if ($currency) {
            $query->where('currency', $currency);
        }

        return $query;
    }

    protected function carnetsVendus(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        return $this->agreger($this->carnets($start, $end, null), 'COUNT(*)', 'user_id', $agentIds);
    }

    protected function carnetsMontant(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        return $this->agreger($this->carnets($start, $end, $currency), 'COALESCE(SUM(price),0)', 'user_id', $agentIds);
    }

    // ---- Crédit ----

    private function creditsOctroyes(Carbon $start, Carbon $end, ?string $currency): Builder
    {
        $query = DB::table('credits')->whereBetween('created_at', [$start, $end]);

        if ($currency) {
            $query->where('currency', $currency);
        }

        return $query;
    }

    protected function creditsNombre(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        return $this->agreger($this->creditsOctroyes($start, $end, null), 'COUNT(*)', 'agent_id', $agentIds);
    }

    protected function creditsMontant(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        return $this->agreger($this->creditsOctroyes($start, $end, $currency), 'COALESCE(SUM(amount),0)', 'agent_id', $agentIds);
    }

    protected function beneficiaires(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        return $this->agreger($this->creditsOctroyes($start, $end, null), 'COUNT(DISTINCT user_id)', 'agent_id', $agentIds);
    }

    protected function nouveauxBeneficiaires(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        $query = $this->creditsOctroyes($start, $end, null)
            ->whereNotExists(function ($sub) use ($start) {
                $sub->select(DB::raw(1))
                    ->from('credits as anterieur')
                    ->whereColumn('anterieur.user_id', 'credits.user_id')
                    ->where('anterieur.created_at', '<', $start);
            });

        return $this->agreger($query, 'COUNT(DISTINCT user_id)', 'agent_id', $agentIds);
    }

    protected function fraisCredit(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        // frais_credit est un pourcentage du montant octroyé
        return $this->agreger(
            $this->creditsOctroyes($start, $end, $currency),
            'COALESCE(SUM(amount * COALESCE(frais_credit,0) / 100),0)', 'agent_id', $agentIds
        );
    }

    private function paiements(Carbon $start, Carbon $end, ?string $currency): Builder
    {
        $query = DB::table('repayments')
            ->join('credits', 'credits.id', '=', 'repayments.credit_id')
            ->whereBetween('repayments.paid_date', [$start->toDateString(), $end->toDateString()]);

        if ($currency) {
            $query->where('credits.currency', $currency);
        }

        return $query;
    }

    protected function remboursementsCapital(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        return $this->agreger($this->paiements($start, $end, $currency), 'COALESCE(SUM(repayments.paid_principal),0)', 'credits.agent_id', $agentIds);
    }

    protected function interetsEncaisses(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        return $this->agreger($this->paiements($start, $end, $currency), 'COALESCE(SUM(repayments.paid_interest),0)', 'credits.agent_id', $agentIds);
    }

    protected function penalitesEncaissees(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        return $this->agreger($this->paiements($start, $end, $currency), 'COALESCE(SUM(repayments.paid_penalty),0)', 'credits.agent_id', $agentIds);
    }

    /** Capital restant dû des crédits non soldés (situation actuelle). */
    private function encours(?string $currency, bool $retard30 = false): Builder
    {
        $query = DB::table('repayments')
            ->join('credits', 'credits.id', '=', 'repayments.credit_id')
            ->where('credits.is_paid', false);

        if ($currency) {
            $query->where('credits.currency', $currency);
        }

        if ($retard30) {
            $limite = now()->subDays(30)->toDateString();
            $query->whereExists(function ($sub) use ($limite) {
                $sub->select(DB::raw(1))
                    ->from('repayments as echeance')
                    ->whereColumn('echeance.credit_id', 'credits.id')
                    ->where('echeance.is_paid', false)
                    ->where('echeance.due_date', '<', $limite);
            });
        }

        return $query;
    }

    private const RESTANT_DU = 'COALESCE(SUM(GREATEST(COALESCE(repayments.principal_amount, repayments.expected_amount, 0) - COALESCE(repayments.paid_principal, 0), 0)),0)';

    protected function encoursCredit(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        return $this->agreger($this->encours($currency), self::RESTANT_DU, 'credits.agent_id', $agentIds);
    }

    protected function par30(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        $risque = $this->agreger($this->encours($currency, true), self::RESTANT_DU, 'credits.agent_id', $agentIds);
        $total = $this->encoursCredit($start, $end, $currency, $agentIds);

        if ($agentIds === null) {
            return $total > 0 ? round($risque / $total * 100, 2) : 0;
        }

        $taux = [];
        foreach ($agentIds as $id) {
            $taux[$id] = ($total[$id] ?? 0) > 0 ? round(($risque[$id] ?? 0) / $total[$id] * 100, 2) : 0;
        }

        return $taux;
    }

    protected function creditsEnRetard(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        $query = DB::table('credits')
            ->where('is_paid', false)
            ->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('repayments')
                    ->whereColumn('repayments.credit_id', 'credits.id')
                    ->where('repayments.is_paid', false)
                    ->where('repayments.due_date', '<', now()->toDateString());
            });

        return $this->agreger($query, 'COUNT(*)', 'agent_id', $agentIds);
    }

    protected function tauxRemboursement(Carbon $start, Carbon $end, ?string $currency, ?array $agentIds): float|array
    {
        // Échéances tombées dans la période (jusqu'à aujourd'hui au plus tard)
        $limite = $end->copy()->min(now()->endOfDay());

        $query = DB::table('repayments')
            ->join('credits', 'credits.id', '=', 'repayments.credit_id')
            ->whereBetween('repayments.due_date', [$start->toDateString(), $limite->toDateString()]);

        if ($currency) {
            $query->where('credits.currency', $currency);
        }

        $du = 'COALESCE(repayments.total_due, repayments.expected_amount, 0)';
        $expression = "CASE WHEN SUM({$du}) > 0 THEN ROUND(SUM(LEAST(COALESCE(repayments.paid_amount,0), {$du})) / SUM({$du}) * 100, 2) ELSE 0 END";

        return $this->agreger($query, $expression, 'credits.agent_id', $agentIds);
    }

    // ------------------------------------------------------------------
    // Progression
    // ------------------------------------------------------------------

    /**
     * Calcule la progression d'une liste d'objectifs.
     * Les calculs sont regroupés (une requête par indicateur/devise/période).
     *
     * @return Collection<int, array> clé = id de l'objectif
     */
    public function progression(Collection $objectifs, ?Carbon $today = null): Collection
    {
        $result = collect();

        // Un lot = même indicateur, devise, période et portée : une seule requête par lot
        $batches = $objectifs->groupBy(fn (Objectif $o) => implode('|', [
            $o->indicator, $o->currency, $o->period_start->toDateString(), $o->period_end->toDateString(), $o->user_id ? 'A' : 'G',
        ]));

        foreach ($batches as $batch) {
            $first = $batch->first();
            $def = $first->definition();
            if (!$def) {
                continue;
            }

            $agentIds = $first->user_id ? $batch->pluck('user_id')->all() : null;
            $mesure = $this->mesurer($first->indicator, $first->period_start, $first->period_end, $first->currency, $agentIds);

            foreach ($batch as $o) {
                $realise = is_array($mesure) ? ($mesure[$o->user_id] ?? 0.0) : $mesure;

                $result[$o->id] = $this->evaluer($def, (float) $o->target_value, (float) $realise, $o->period_start, $o->period_end, $today) + [
                    'objectif' => $o,
                    'def'      => $def,
                ];
            }
        }

        return $result;
    }

    /**
     * Compare réalisé / cible / attendu à date et retourne taux + statut.
     */
    public function evaluer(array $def, float $cible, float $realise, Carbon $start, Carbon $end, ?Carbon $today = null): array
    {
        $ratioTemps = $def['nature'] === 'flux' ? self::tempsEcoule($start, $end, $today) : 1.0;
        $attendu = $cible * $ratioTemps;
        $taux = $cible > 0 ? round($realise / $cible * 100, 1) : 0.0;
        $vigilance = config('objectifs.seuils.vigilance', 80) / 100;
        $tolerance = config('objectifs.seuils.tolerance_plafond', 20) / 100;

        if ($def['direction'] === 'down') {
            // Plafond : moins c'est mieux
            if ($ratioTemps <= 0) {
                [$statut, $label] = ['secondary', 'À venir'];
            } elseif ($realise <= $attendu) {
                [$statut, $label] = ['success', 'Sous le plafond'];
            } elseif ($realise <= $attendu * (1 + $tolerance)) {
                [$statut, $label] = ['warning', 'Proche du plafond'];
            } else {
                [$statut, $label] = ['danger', 'Plafond dépassé'];
            }
            $score = $cible > 0 ? max(0, min(100, 100 - max(0, $realise - $cible) / $cible * 100)) : ($realise > 0 ? 0 : 100);
        } else {
            if ($ratioTemps <= 0 || $attendu <= 0) {
                [$statut, $label] = ['secondary', 'À venir'];
            } elseif ($realise >= $cible && $cible > 0) {
                [$statut, $label] = ['success', 'Atteint'];
            } elseif ($realise >= $attendu) {
                [$statut, $label] = ['success', 'Dans les temps'];
            } elseif ($realise >= $attendu * $vigilance) {
                [$statut, $label] = ['warning', 'Vigilance'];
            } else {
                [$statut, $label] = ['danger', 'En retard'];
            }
            $score = min(100, $taux);
        }

        return [
            'cible'         => $cible,
            'realise'       => $realise,
            'attendu'       => round($attendu, 2),
            'reste'         => max(0, $cible - $realise),
            'taux'          => $taux,
            'taux_attendu'  => round($ratioTemps * 100, 1),
            'statut'        => $statut,
            'statut_label'  => $label,
            'score'         => round($score, 1),
        ];
    }

    /** Formatage d'une valeur selon l'unité de l'indicateur. */
    public static function formater(float $value, array $def, ?string $currency = null): string
    {
        return match ($def['unit']) {
            'percent' => number_format($value, 1, ',', ' ') . ' %',
            'amount'  => number_format($value, 2, ',', ' ') . ($currency ? ' ' . $currency : ''),
            default   => number_format($value, 0, ',', ' '),
        };
    }
}
