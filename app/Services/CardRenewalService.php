<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Carnets retirés et non renouvelés : clients à relancer.
 *
 * Règles (voir docs/carnets-non-renouveles.md) :
 *  - un carnet est « retiré » quand il est inactif (is_active = 0) ; la date du retrait est sa
 *    date de mise à jour, identique à l'opération « Retrait carnet #id » (vérifié sur 913 retraits) ;
 *  - il est « renouvelé » si le client possède un autre carnet, non annulé, plus récent que lui
 *    (même pris juste avant le retrait de l'ancien) ;
 *  - l'agent collecteur est l'agent inscrit sur le carnet (membership_cards.user_id).
 */
class CardRenewalService
{
    public const PERIODES = [
        'day' => 'Jour',
        'week' => 'Semaine',
        'month' => 'Mois',
        'custom' => 'Personnalisée',
    ];

    public const TYPES = ['epargne' => 'Épargne', 'simple' => 'Simple', 'tous' => 'Tous'];

    /** Tranches de retard depuis le retrait (jours), pour les couleurs et le résumé */
    public const TRANCHES = [
        'Moins de 7 jours' => [0, 6],
        '7 à 30 jours' => [7, 30],
        'Plus de 30 jours' => [31, null],
    ];

    /**
     * Retraits de la période avec leur statut de renouvellement.
     *
     * @param array{start: Carbon, end: Carbon} $periode
     * @param array{agent_id?: ?int, devise?: ?string, type?: string} $filtres
     */
    public function retraits(array $periode, array $filtres = []): Collection
    {
        $type = $filtres['type'] ?? 'epargne';

        return DB::table('membership_cards as c')
            ->join('users as m', 'm.id', '=', 'c.member_id')
            ->leftJoin('users as a', 'a.id', '=', 'c.user_id')
            ->where('c.is_active', false)
            ->whereNull('c.cancelled_at')
            ->whereBetween('c.updated_at', [$periode['start'], $periode['end']])
            ->when(! empty($filtres['agent_id']), fn ($q) => $q->where('c.user_id', $filtres['agent_id']))
            ->when(! empty($filtres['devise']), fn ($q) => $q->where('c.currency', $filtres['devise']))
            ->when($type !== 'tous', fn ($q) => $q->where('c.card_type', $type))
            ->select([
                'c.id', 'c.code', 'c.member_id', 'c.user_id as agent_id', 'c.currency', 'c.card_type',
                'c.subscription_amount', 'c.created_at as pris_le', 'c.updated_at as retire_le',
                'm.code as client_code', 'm.name', 'm.postnom', 'm.prenom', 'm.telephone',
                'a.name as agent_name', 'a.postnom as agent_postnom',
            ])
            // Renouvelé : un autre carnet non annulé, plus récent que celui retiré
            ->selectRaw('EXISTS (SELECT 1 FROM membership_cards n WHERE n.member_id = c.member_id AND n.id <> c.id
                AND n.cancelled_at IS NULL AND n.created_at > c.created_at) AS renouvele')
            // Autre carnet encore actif (plus ancien, tenu en parallèle)
            ->selectRaw('EXISTS (SELECT 1 FROM membership_cards o WHERE o.member_id = c.member_id AND o.id <> c.id
                AND o.is_active = 1 AND o.cancelled_at IS NULL) AS autre_actif')
            ->selectRaw('(SELECT COALESCE(SUM(d.amount), 0) FROM daily_contributions d
                WHERE d.membership_card_id = c.id AND d.is_paid = 1) AS mises')
            ->selectRaw('(SELECT COUNT(*) FROM membership_cards t WHERE t.member_id = c.member_id
                AND t.is_active = 0 AND t.cancelled_at IS NULL) AS carnets_termines')
            ->orderBy('c.updated_at')
            ->get()
            ->map(function ($r) {
                $r->renouvele = (bool) $r->renouvele;
                $r->autre_actif = (bool) $r->autre_actif;
                $r->retire_le = Carbon::parse($r->retire_le);
                $r->jours = (int) $r->retire_le->copy()->startOfDay()->diffInDays(now()->startOfDay());
                $r->client = trim("{$r->name} {$r->postnom} {$r->prenom}");
                $r->agent = $r->agent_id ? trim("{$r->agent_name} {$r->agent_postnom}") : 'Non attribué';
                $r->mises = (float) $r->mises;

                return $r;
            });
    }

    /**
     * Clients à relancer : un client par ligne (son retrait non renouvelé le plus récent de la période),
     * du retard le plus long au plus court.
     */
    public function aRelancer(Collection $retraits, int $retardMinimum = 0, bool $masquerAutreActif = false): Collection
    {
        return $retraits
            ->reject(fn ($r) => $r->renouvele)
            ->groupBy('member_id')
            ->map(fn ($groupe) => $groupe->sortByDesc('retire_le')->first())
            ->filter(fn ($r) => $r->jours >= $retardMinimum)
            ->reject(fn ($r) => $masquerAutreActif && $r->autre_actif)
            ->sortByDesc('jours')
            ->values();
    }

    /** Compteurs de la période. */
    public function resume(Collection $retraits, Collection $aRelancer): array
    {
        $total = $retraits->count();
        $renouveles = $retraits->where('renouvele', true)->count();

        $tranches = [];
        foreach (self::TRANCHES as $label => [$min, $max]) {
            $tranches[$label] = $aRelancer->filter(fn ($r) => $r->jours >= $min && ($max === null || $r->jours <= $max))->count();
        }

        return [
            'retraits' => $total,
            'renouveles' => $renouveles,
            'non_renouveles' => $total - $renouveles,
            'taux' => $total > 0 ? round($renouveles / $total * 100, 1) : null,
            'clients' => $aRelancer->count(),
            'tranches' => $tranches,
        ];
    }

    /** Taux de renouvellement par agent collecteur, du plus faible au plus élevé. */
    public function parAgent(Collection $retraits, Collection $aRelancer): array
    {
        return $retraits
            ->groupBy(fn ($r) => $r->agent_id ?? 0)
            ->map(function ($groupe, $agentId) use ($aRelancer) {
                $renouveles = $groupe->where('renouvele', true)->count();

                return [
                    'agent_id' => $agentId ?: null,
                    'agent' => $groupe->first()->agent,
                    'retraits' => $groupe->count(),
                    'renouveles' => $renouveles,
                    'a_relancer' => $aRelancer->where('agent_id', $agentId ?: null)->count(),
                    'taux' => round($renouveles / $groupe->count() * 100, 1),
                ];
            })
            ->sortBy('taux')
            ->values()
            ->all();
    }

    /** Tranche de retard d'un nombre de jours (pour la couleur). */
    public static function niveau(int $jours): string
    {
        return $jours < 7 ? 'success' : ($jours <= 30 ? 'warning' : 'danger');
    }
}
