<?php

namespace App\Services;

use App\Support\ClientBalanceRanking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Cockpit décisionnel : indicateurs de microfinance par période, devise et agent.
 *
 * Les chiffres réutilisent les règles déjà validées ailleurs dans l'application :
 *  - ObjectifProgressService::mesurer() pour l'activité de la période (collecte côté compte
 *    membre, sans double comptage ; crédits ; remboursements ; produits ; clients) ;
 *  - ClientBalanceRanking::liabilities() pour ce que nous devons aux clients.
 * Une devise à la fois : CDF et USD ne sont jamais additionnés.
 */
class DecisionDashboardService
{
    public const PERIODES = [
        'year'     => 'Exercice',
        'semester' => 'Semestre',
        'quarter'  => 'Trimestre',
        'month'    => 'Mois',
        'week'     => 'Semaine',
        'day'      => 'Jour',
        'custom'   => 'Intervalle',
    ];

    public const DEVISES = ['CDF', 'USD'];

    public function __construct(private ObjectifProgressService $mesures)
    {
    }

    // ------------------------------------------------------------------
    // Périodes
    // ------------------------------------------------------------------

    /**
     * Bornes de la période demandée et de la période précédente équivalente.
     *
     * @return array{type: string, start: Carbon, end: Carbon, label: string, prev_start: Carbon, prev_end: Carbon, prev_label: string, granularite: string}
     */
    public function periode(string $type, ?string $reference, ?string $du = null, ?string $au = null): array
    {
        $type = array_key_exists($type, self::PERIODES) ? $type : 'month';
        $ref = $this->date($reference) ?? now();

        if ($type === 'custom') {
            $start = ($this->date($du) ?? now()->startOfMonth())->copy()->startOfDay();
            $end = ($this->date($au) ?? now())->copy()->endOfDay();
            if ($end->lt($start)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }
            $jours = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;
            $prevEnd = $start->copy()->subDay()->endOfDay();
            $prevStart = $prevEnd->copy()->subDays($jours - 1)->startOfDay();
            $label = 'Du ' . $start->format('d/m/Y') . ' au ' . $end->format('d/m/Y');
            $prevLabel = 'du ' . $prevStart->format('d/m/Y') . ' au ' . $prevEnd->format('d/m/Y');
        } elseif ($type === 'day') {
            $start = $ref->copy()->startOfDay();
            $end = $ref->copy()->endOfDay();
            $prevStart = $start->copy()->subDay();
            $prevEnd = $end->copy()->subDay();
            $label = 'Le ' . $start->locale('fr')->isoFormat('dddd D MMMM YYYY');
            $prevLabel = 'la veille';
        } else {
            [$start, $end, $label] = ObjectifProgressService::periode($type, $ref);
            $prevStart = ObjectifProgressService::periodePrecedente($type, $start);
            [$prevStart, $prevEnd, $prevLabel] = ObjectifProgressService::periode($type, $prevStart);
        }

        return [
            'type' => $type,
            'start' => $start,
            'end' => $end,
            'label' => $label,
            'prev_start' => $prevStart,
            'prev_end' => $prevEnd,
            'prev_label' => $prevLabel,
            'granularite' => $this->granularite($type, $start, $end),
        ];
    }

    /** Découpage conseillé pour les graphiques de la période. */
    private function granularite(string $type, Carbon $start, Carbon $end): string
    {
        return match ($type) {
            'day' => 'hour',
            'week', 'month' => 'day',
            'quarter', 'semester' => 'week',
            'year' => 'month',
            default => (function () use ($start, $end) {
                $jours = $start->diffInDays($end) + 1;

                return $jours <= 1 ? 'hour' : ($jours <= 62 ? 'day' : ($jours <= 200 ? 'week' : 'month'));
            })(),
        };
    }

    /** Période suivante / précédente pour les flèches de navigation (date de référence). */
    public function referenceVoisine(array $periode, int $sens): string
    {
        if ($periode['type'] === 'custom') {
            return now()->toDateString();
        }

        return $sens < 0
            ? $periode['prev_start']->toDateString()
            : $periode['end']->copy()->addDay()->toDateString();
    }

    private function date(?string $valeur): ?Carbon
    {
        try {
            return $valeur ? Carbon::parse($valeur) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    // ------------------------------------------------------------------
    // Synthèse
    // ------------------------------------------------------------------

    /**
     * Indicateurs de synthèse (mis en cache 5 minutes par combinaison de filtres).
     */
    public function synthese(array $periode, string $devise, ?int $agentId = null): array
    {
        $devise = in_array($devise, self::DEVISES, true) ? $devise : 'CDF';
        $cle = 'cockpit:' . md5(json_encode([
            $periode['start']->toDateTimeString(), $periode['end']->toDateTimeString(), $devise, $agentId,
            now()->format('Y-m-d H') . floor(now()->minute / 5),
        ]));

        return Cache::remember($cle, 300, fn () => $this->calculerSynthese($periode, $devise, $agentId));
    }

    private function calculerSynthese(array $p, string $devise, ?int $agentId): array
    {
        // Activité de la période et de la période précédente
        $flux = fn (string $indicateur, ?string $cur = null) => [
            'valeur' => $this->mesurer($indicateur, $p['start'], $p['end'], $cur, $agentId),
            'precedent' => $this->mesurer($indicateur, $p['prev_start'], $p['prev_end'], $cur, $agentId),
        ];

        $collecte = $flux('collecte_totale', $devise);
        $retraits = $flux('retraits_montant', $devise);
        $interets = $flux('interets_encaisses', $devise);
        $penalites = $flux('penalites_encaissees', $devise);
        $frais = $flux('frais_credit', $devise);
        $carnets = $flux('carnets_montant', $devise);

        $produits = [
            'valeur' => $interets['valeur'] + $penalites['valeur'] + $frais['valeur'] + $carnets['valeur'],
            'precedent' => $interets['precedent'] + $penalites['precedent'] + $frais['precedent'] + $carnets['precedent'],
        ];

        $credits = [
            'nombre' => [
                'valeur' => $this->nombreCredits($p['start'], $p['end'], $devise, $agentId),
                'precedent' => $this->nombreCredits($p['prev_start'], $p['prev_end'], $devise, $agentId),
            ],
            'montant' => $flux('credits_montant', $devise),
        ];

        // Situation à date (portefeuille, dépôts, liquidité)
        $encours = $this->mesurer('encours_credit', $p['start'], $p['end'], $devise, $agentId);
        $emprunteurs = $this->emprunteursActifs($devise, $agentId);
        $liabilities = ClientBalanceRanking::liabilities()[$devise];
        $depots = $agentId
            ? $this->mesurer('encours_epargne', $p['start'], $p['end'], $devise, $agentId)
            : $liabilities['du'];
        $liquidite = $this->liquidite($devise, $agentId);

        return [
            'devise' => $devise,
            'activite' => [
                'collecte' => $collecte,
                'retraits' => $retraits,
                'epargne_nette' => [
                    'valeur' => $collecte['valeur'] - $retraits['valeur'],
                    'precedent' => $collecte['precedent'] - $retraits['precedent'],
                ],
                'credits_nombre' => $credits['nombre'],
                'credits_montant' => $credits['montant'],
                'remboursements' => $flux('remboursements_capital', $devise),
                'produits' => $produits,
                'produits_detail' => [
                    'Intérêts' => $interets['valeur'],
                    'Pénalités' => $penalites['valeur'],
                    'Frais de crédit' => $frais['valeur'],
                    'Ventes de carnets' => $carnets['valeur'],
                ],
                'taux_remboursement' => $flux('taux_remboursement', $devise),
                'nouveaux_membres' => $flux('clients_sensibilises'),
                'membres_actifs' => $flux('clients_actifs'),
            ],
            'portefeuille' => [
                'encours' => $encours,
                'emprunteurs' => $emprunteurs,
                'credit_moyen' => $emprunteurs > 0 ? $encours / $emprunteurs : 0,
                'par1' => $this->par(1, $devise, $agentId),
                'par30' => $this->par(30, $devise, $agentId),
                'par90' => $this->par(90, $devise, $agentId),
                'en_retard' => $this->creditsEnRetard($devise, $agentId),
            ],
            'epargne' => [
                'depots' => $depots,
                'epargnants' => $agentId ? null : $liabilities['nb_du'],
                'soldes_negatifs' => $agentId ? null : $liabilities['negatifs'],
            ],
            'liquidite' => [
                'caisse_centrale' => $liquidite['centrale'],
                'caisses_agents' => $liquidite['agents'],
                'total' => $liquidite['centrale'] + $liquidite['agents'],
                // Couverture des dépôts de la clientèle par la liquidité disponible
                'couverture' => $depots > 0 ? ($liquidite['centrale'] + $liquidite['agents']) / $depots * 100 : null,
            ],
            'portee' => $this->portee($agentId),
        ];
    }

    /** Valeur globale ou d'un agent, via les mesures du module Objectifs. */
    private function mesurer(string $indicateur, Carbon $start, Carbon $end, ?string $devise, ?int $agentId): float
    {
        $valeur = $this->mesures->mesurer($indicateur, $start, $end, $devise, $agentId ? [$agentId] : null);

        return (float) (is_array($valeur) ? ($valeur[$agentId] ?? 0) : $valeur);
    }

    private function nombreCredits(Carbon $start, Carbon $end, string $devise, ?int $agentId): int
    {
        return DB::table('credits')
            ->where('currency', $devise)
            ->whereBetween('created_at', [$start, $end])
            ->when($agentId, fn ($q) => $q->where('agent_id', $agentId))
            ->count();
    }

    private function emprunteursActifs(string $devise, ?int $agentId): int
    {
        return (int) DB::table('credits')
            ->where('currency', $devise)
            ->where('is_paid', false)
            ->when($agentId, fn ($q) => $q->where('agent_id', $agentId))
            ->distinct()
            ->count('user_id');
    }

    /** Crédits non soldés ayant au moins une échéance impayée dépassée (dans la devise choisie). */
    private function creditsEnRetard(string $devise, ?int $agentId): int
    {
        return DB::table('credits')
            ->where('is_paid', false)
            ->where('currency', $devise)
            ->when($agentId, fn ($q) => $q->where('agent_id', $agentId))
            ->whereExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('repayments')
                    ->whereColumn('repayments.credit_id', 'credits.id')
                    ->where('repayments.is_paid', false)
                    ->where('repayments.due_date', '<', now()->toDateString());
            })
            ->count();
    }

    /**
     * Portefeuille à risque : encours des crédits ayant une échéance impayée depuis plus de N jours,
     * rapporté à l'encours total (en %). Situation à date.
     */
    public function par(int $jours, string $devise, ?int $agentId = null): float
    {
        $restant = 'COALESCE(SUM(GREATEST(COALESCE(repayments.principal_amount, repayments.expected_amount, 0) - COALESCE(repayments.paid_principal, 0), 0)), 0)';
        $base = fn () => DB::table('repayments')
            ->join('credits', 'credits.id', '=', 'repayments.credit_id')
            ->where('credits.is_paid', false)
            ->where('credits.currency', $devise)
            ->when($agentId, fn ($q) => $q->where('credits.agent_id', $agentId));

        $total = (float) $base()->selectRaw("{$restant} AS v")->value('v');
        if ($total <= 0) {
            return 0.0;
        }

        $limite = now()->subDays($jours)->toDateString();
        $risque = (float) $base()->whereExists(function ($sub) use ($limite) {
            $sub->selectRaw('1')
                ->from('repayments as echeance')
                ->whereColumn('echeance.credit_id', 'credits.id')
                ->where('echeance.is_paid', false)
                ->where('echeance.due_date', '<', $limite);
        })->selectRaw("{$restant} AS v")->value('v');

        return round($risque / $total * 100, 2);
    }

    /** Liquidité disponible : caisse centrale et caisses des agents (celle de l'agent si filtré). */
    private function liquidite(string $devise, ?int $agentId): array
    {
        $agents = (float) DB::table('agent_accounts')
            ->where('currency', $devise)
            ->when($agentId, fn ($q) => $q->where('user_id', $agentId))
            ->sum('balance');

        $centrale = $agentId ? 0.0 : (float) DB::table('main_cash_registers')->where('currency', $devise)->sum('balance');

        return ['centrale' => $centrale, 'agents' => $agents];
    }

    /** Portée : membres (vrais clients), part des femmes. */
    private function portee(?int $agentId): array
    {
        $membres = DB::table('users')
            ->where('role', 'membre')
            ->where('client_category', 'client')
            ->when($agentId, fn ($q) => $q->where('agent_id', $agentId));

        $total = (clone $membres)->count();
        $femmes = (clone $membres)->where('sexe', 'Féminin')->count();

        return [
            'membres' => $total,
            'femmes' => $femmes,
            'part_femmes' => $total > 0 ? round($femmes / $total * 100, 1) : 0,
        ];
    }

    /** Variation en % par rapport à la période précédente (null si la base est nulle). */
    public static function variation(float $valeur, float $precedent): ?float
    {
        if (abs($precedent) < 0.005) {
            return null;
        }

        return round(($valeur - $precedent) / abs($precedent) * 100, 1);
    }
}
