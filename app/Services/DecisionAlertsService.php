<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cockpit décisionnel, étape 3 : alertes chiffrées, décisions proposées et listes de travail
 * (crédits à surveiller, clients inactifs avec épargne, performance des agents).
 *
 * Même périmètre que la synthèse : une devise à la fois, agent facultatif.
 */
class DecisionAlertsService
{
    /** Rôles des comptes internes, exclus des classements d'agents */
    public const ROLES_INTERNES = ['membre', 'admin', 'comptable'];

    /** Tranches de retard (« balance âgée ») */
    public const TRANCHES_RETARD = [
        '1 à 30 jours' => [1, 30],
        '31 à 90 jours' => [31, 90],
        '91 à 180 jours' => [91, 180],
        'Plus de 180 jours' => [181, null],
    ];

    /** Tranches d'inactivité des clients */
    public const TRANCHES_INACTIVITE = [
        '30 à 59 jours' => [30, 59],
        '60 à 89 jours' => [60, 89],
        '90 à 179 jours' => [90, 179],
        '180 jours et plus' => [180, null],
    ];

    public function __construct(private ObjectifProgressService $mesures)
    {
    }

    // ------------------------------------------------------------------
    // Crédits à surveiller
    // ------------------------------------------------------------------

    /**
     * Crédits non soldés ayant au moins une échéance impayée dépassée, classés par ancienneté
     * du retard (à partir de la plus ancienne échéance impayée).
     *
     * @return array{tranches: array, liste: array, total: float, nombre: int, a_terme: int}
     */
    public function creditsASurveiller(string $devise, ?int $agentId = null): array
    {
        $aujourdhui = now()->toDateString();

        $credits = DB::table('credits')
            ->join('repayments', 'repayments.credit_id', '=', 'credits.id')
            ->where('credits.is_paid', false)
            ->where('credits.currency', $devise)
            ->when($agentId, fn ($q) => $q->where('credits.agent_id', $agentId))
            ->groupBy('credits.id', 'credits.user_id', 'credits.agent_id', 'credits.amount')
            ->selectRaw('credits.id, credits.user_id, credits.agent_id, credits.amount,
                MIN(CASE WHEN repayments.is_paid = 0 AND repayments.due_date < ? THEN repayments.due_date END) AS plus_ancienne,
                MAX(repayments.due_date) AS derniere_echeance,
                SUM(CASE WHEN repayments.is_paid = 0 AND repayments.due_date < ? THEN 1 ELSE 0 END) AS impayees,
                SUM(GREATEST(COALESCE(repayments.principal_amount, repayments.expected_amount, 0) - COALESCE(repayments.paid_principal, 0), 0)) AS restant',
                [$aujourdhui, $aujourdhui])
            ->get()
            ->filter(fn ($c) => $c->plus_ancienne !== null)
            ->map(function ($c) {
                $c->jours = (int) Carbon::parse($c->plus_ancienne)->startOfDay()->diffInDays(now()->startOfDay());
                $c->restant = (float) $c->restant;

                return $c;
            });

        $tranches = [];
        foreach (self::TRANCHES_RETARD as $label => [$min, $max]) {
            $groupe = $credits->filter(fn ($c) => $c->jours >= $min && ($max === null || $c->jours <= $max));
            $tranches[] = ['label' => $label, 'nombre' => $groupe->count(), 'montant' => round($groupe->sum('restant'), 2)];
        }

        // Crédits dont la dernière échéance est passée depuis plus de 90 jours : arrivés à terme
        $limiteTerme = now()->subDays(90)->toDateString();
        $aTerme = $credits->filter(fn ($c) => $c->derniere_echeance < $limiteTerme)->count();

        $liste = $credits->sortByDesc('restant')->take(10)->values();
        $noms = DB::table('users')
            ->whereIn('id', $liste->pluck('user_id')->merge($liste->pluck('agent_id'))->filter()->unique())
            ->get(['id', 'name', 'postnom', 'prenom', 'code'])->keyBy('id');

        return [
            'tranches' => $tranches,
            'liste' => $liste->map(fn ($c) => [
                'credit_id' => $c->id,
                'client_id' => $c->user_id,
                'client' => $this->nom($noms[$c->user_id] ?? null),
                'code' => $noms[$c->user_id]->code ?? null,
                'agent' => $c->agent_id ? $this->nom($noms[$c->agent_id] ?? null) : 'Non attribué',
                'restant' => $c->restant,
                'jours' => $c->jours,
                'impayees' => (int) $c->impayees,
            ])->all(),
            'total' => round($credits->sum('restant'), 2),
            'nombre' => $credits->count(),
            'a_terme' => $aTerme,
        ];
    }

    // ------------------------------------------------------------------
    // Clients inactifs avec épargne
    // ------------------------------------------------------------------

    /**
     * Clients sans opération depuis au moins 30 jours qui ont encore de l'épargne dans la devise :
     * risque de départ, et argent qui « dort ».
     */
    public function clientsInactifs(string $devise, ?int $agentId = null): array
    {
        $base = fn () => DB::table('users')
            ->join('accounts', 'accounts.user_id', '=', 'users.id')
            ->where('users.role', 'membre')
            ->where('users.client_category', 'client')
            ->where('users.status', true)
            ->where('accounts.currency', $devise)
            ->where('accounts.balance', '>', 0)
            ->when($agentId, fn ($q) => $q->where('users.agent_id', $agentId));

        $derniere = 'COALESCE(users.last_transaction_at, users.created_at)';
        $tranches = [];
        foreach (self::TRANCHES_INACTIVITE as $label => [$min, $max]) {
            $ligne = $base()
                ->whereRaw("{$derniere} <= ?", [now()->subDays($min)])
                ->when($max, fn ($q) => $q->whereRaw("{$derniere} > ?", [now()->subDays($max + 1)]))
                ->selectRaw('COUNT(DISTINCT users.id) AS nombre, COALESCE(SUM(accounts.balance), 0) AS epargne')
                ->first();
            $tranches[] = ['label' => $label, 'nombre' => (int) $ligne->nombre, 'epargne' => round((float) $ligne->epargne, 2)];
        }

        $liste = $base()
            ->leftJoin('users as agents', 'agents.id', '=', 'users.agent_id')
            ->whereRaw("{$derniere} <= ?", [now()->subDays(30)])
            ->orderByDesc('accounts.balance')
            ->limit(10)
            ->get([
                'users.id', 'users.name', 'users.postnom', 'users.prenom', 'users.code',
                'users.last_transaction_at', 'users.created_at', 'accounts.balance',
                'agents.name as agent_name', 'agents.postnom as agent_postnom',
            ])
            ->map(fn ($u) => [
                'id' => $u->id,
                'client' => $this->nom($u),
                'code' => $u->code,
                'agent' => $u->agent_name ? trim($u->agent_name . ' ' . $u->agent_postnom) : 'Non attribué',
                'epargne' => (float) $u->balance,
                'derniere' => Carbon::parse($u->last_transaction_at ?? $u->created_at),
                'jours' => (int) Carbon::parse($u->last_transaction_at ?? $u->created_at)->startOfDay()->diffInDays(now()->startOfDay()),
            ])
            ->all();

        return [
            'tranches' => $tranches,
            'liste' => $liste,
            'nombre' => array_sum(array_column($tranches, 'nombre')),
            'epargne' => round(array_sum(array_column($tranches, 'epargne')), 2),
        ];
    }

    // ------------------------------------------------------------------
    // Performance des agents
    // ------------------------------------------------------------------

    /**
     * Agents de terrain sur la période et la devise : clientèle, clients actifs, collecte,
     * encours, PAR 30 et crédits en retard. Comptes internes exclus.
     */
    public function performanceAgents(array $p, string $devise, ?int $agentId = null): array
    {
        $collecteTypes = array_merge(ObjectifProgressService::MISE_TYPES, ObjectifProgressService::DEPOT_TYPES);

        $ids = $agentId ? collect([$agentId]) : DB::table('users')->where('role', 'membre')->whereNotNull('agent_id')->distinct()->pluck('agent_id')
            ->merge(DB::table('credits')->where('currency', $devise)->where('is_paid', false)->whereNotNull('agent_id')->distinct()->pluck('agent_id'))
            ->merge(DB::table('transactions')->whereIn('type', $collecteTypes)->where('currency', $devise)->whereNull('account_id')
                ->whereBetween('created_at', [$p['start'], $p['end']])->distinct()->pluck('user_id'));

        $agents = DB::table('users')
            ->whereIn('id', $ids->filter()->unique()->values())
            ->whereNotIn('role', self::ROLES_INTERNES)
            ->get(['id', 'name', 'postnom', 'prenom', 'role']);
        if ($agents->isEmpty()) {
            return [];
        }
        $ids = $agents->pluck('id')->all();

        $m = fn (string $indicateur, ?string $cur = null) => $this->mesures->mesurer($indicateur, $p['start'], $p['end'], $cur, $ids);
        $clients = $m('portefeuille_clients');
        $actifs = $m('clients_actifs');
        $collecte = $m('collecte_totale', $devise);
        $encours = $m('encours_credit', $devise);
        $par30 = $m('par_30', $devise);
        $retards = DB::table('credits')
            ->where('is_paid', false)->where('currency', $devise)->whereIn('agent_id', $ids)
            ->whereExists(fn ($s) => $s->selectRaw('1')->from('repayments')->whereColumn('repayments.credit_id', 'credits.id')
                ->where('repayments.is_paid', false)->where('repayments.due_date', '<', now()->toDateString()))
            ->groupBy('agent_id')->selectRaw('agent_id, COUNT(*) AS n')->pluck('n', 'agent_id');

        return $agents
            ->map(fn ($a) => [
                'id' => $a->id,
                'nom' => $this->nom($a),
                'role' => $a->role,
                'clients' => (int) ($clients[$a->id] ?? 0),
                'actifs' => (int) ($actifs[$a->id] ?? 0),
                'taux_activite' => ($clients[$a->id] ?? 0) > 0 ? round(($actifs[$a->id] ?? 0) / $clients[$a->id] * 100, 1) : null,
                'collecte' => (float) ($collecte[$a->id] ?? 0),
                'encours' => (float) ($encours[$a->id] ?? 0),
                'par30' => (float) ($par30[$a->id] ?? 0),
                'retards' => (int) ($retards[$a->id] ?? 0),
            ])
            ->filter(fn ($a) => $a['clients'] > 0 || $a['collecte'] > 0 || $a['encours'] > 0)
            ->sortByDesc('collecte')
            ->values()
            ->all();
    }

    // ------------------------------------------------------------------
    // Alertes et décisions
    // ------------------------------------------------------------------

    /**
     * Alertes classées par gravité, chacune avec le chiffre qui la justifie et la décision proposée.
     *
     * @return array<int, array{niveau: string, titre: string, constat: string, decision: string, lien: ?string, lien_libelle: ?string}>
     */
    public function alertes(array $synthese, array $periode, array $credits, array $inactifs, array $agents, ?int $agentId = null): array
    {
        $devise = $synthese['devise'];
        $f = fn ($v) => number_format((float) $v, 0, ',', ' ') . ' ' . $devise;
        $pct = fn ($v) => number_format((float) $v, 1, ',', ' ') . ' %';
        $act = $synthese['activite'];
        $pf = $synthese['portefeuille'];
        $liq = $synthese['liquidite'];
        $alertes = [];

        $ajouter = function (string $niveau, string $titre, string $constat, string $decision, ?string $route = null, ?string $libelle = null) use (&$alertes) {
            $alertes[] = [
                'niveau' => $niveau,
                'titre' => $titre,
                'constat' => $constat,
                'decision' => $decision,
                'lien' => $route ? route($route) : null,
                'lien_libelle' => $libelle,
            ];
        };

        // 1. Qualité du portefeuille
        if ($pf['encours'] > 0 && $pf['par30'] >= 5) {
            $critique = $pf['par30'] >= 10;
            $ajouter(
                $critique ? 'danger' : 'warning',
                $critique ? 'Portefeuille à risque critique' : 'Portefeuille à risque à surveiller',
                "PAR 30 = {$pct($pf['par30'])} de l'encours ({$f($pf['encours'] * $pf['par30'] / 100)} sur {$f($pf['encours'])}). Repère : moins de 5 %.",
                $critique
                    ? 'Freiner les nouveaux crédits chez les agents les plus exposés et lancer une campagne de recouvrement sur les retards les plus lourds (liste ci-dessous).'
                    : 'Renforcer le suivi des échéances : appeler les clients dès le premier jour de retard.',
                'report.credit.overview', 'Rapport des crédits'
            );
        }

        if ($credits['a_terme'] > 0) {
            $ajouter(
                'warning',
                'Crédits arrivés à terme mais non soldés',
                "{$credits['a_terme']} crédit(s) ont leur dernière échéance dépassée depuis plus de 90 jours.",
                'Vérifier chaque dossier : s\'il est remboursé, le marquer soldé (il fausse l\'encours et le PAR) ; sinon, décider du recouvrement contentieux ou du passage en perte.',
                'report.credit.overview', 'Voir les crédits'
            );
        }

        // Concentration : un seul agent porte l'essentiel de l'encours
        $totalEncoursAgents = array_sum(array_column($agents, 'encours'));
        if (! $agentId && $pf['encours'] > 0 && $totalEncoursAgents > 0) {
            $principal = collect($agents)->sortByDesc('encours')->first();
            $part = $principal['encours'] / $pf['encours'] * 100;
            if ($part >= 50) {
                $ajouter(
                    'warning',
                    'Encours concentré sur un seul agent',
                    "{$principal['nom']} suit {$f($principal['encours'])}, soit {$pct($part)} de l'encours.",
                    'Répartir le suivi des crédits entre plusieurs agents pour limiter le risque (absence, départ, erreur).',
                    null, null
                );
            }
        }

        if ($act['taux_remboursement']['valeur'] > 0 && $act['taux_remboursement']['valeur'] < 95) {
            $taux = $act['taux_remboursement']['valeur'];
            $ajouter(
                $taux < 80 ? 'danger' : 'warning',
                'Échéances de la période mal remboursées',
                "Seulement {$pct($taux)} du montant attendu sur les échéances de la période a été payé.",
                'Identifier les agents dont les clients paient mal et organiser des visites ciblées.',
                'report.credit.overview', 'Rapport des crédits'
            );
        }

        // 2. Liquidité
        if ($liq['caisse_centrale'] < 0 || $liq['caisses_agents'] < 0) {
            $ajouter(
                'danger',
                'Caisse négative',
                "Caisse centrale : {$f($liq['caisse_centrale'])} ; caisses des agents : {$f($liq['caisses_agents'])}.",
                'Contrôler les dernières opérations de caisse et régulariser immédiatement (une caisse ne peut pas être négative).',
                'cash.register', 'Caisse centrale'
            );
        }

        if ($liq['couverture'] !== null && $liq['couverture'] < 20) {
            $tension = $liq['couverture'] < 10;
            $ajouter(
                $tension ? 'danger' : 'warning',
                $tension ? 'Liquidité en tension' : 'Liquidité à surveiller',
                "La liquidité ({$f($liq['total'])}) couvre {$pct($liq['couverture'])} des dépôts de la clientèle.",
                'Limiter les décaissements de crédit et les grosses sorties tant que la couverture reste sous 20 % ; accélérer les remboursements.',
                'cash.register', 'Caisse centrale'
            );
        }

        // 3. Épargne et activité
        if ($act['epargne_nette']['valeur'] < 0) {
            $ajouter(
                'warning',
                'Les retraits dépassent la collecte',
                "Épargne nette de la période : {$f($act['epargne_nette']['valeur'])} (collecte {$f($act['collecte']['valeur'])}, retraits {$f($act['retraits']['valeur'])}).",
                'Comprendre les gros retraits (besoin ponctuel ou perte de confiance) et relancer la collecte.',
                'rapports.transactions', 'Rapport journalier'
            );
        }

        $varCollecte = DecisionDashboardService::variation($act['collecte']['valeur'], $act['collecte']['precedent']);
        if ($varCollecte !== null && $varCollecte <= -20) {
            $ajouter(
                'warning',
                'Collecte en forte baisse',
                "Collecte {$f($act['collecte']['valeur'])}, soit {$pct($varCollecte)} par rapport à {$periode['prev_label']}.",
                'Vérifier l\'activité de chaque agent (tableau ci-dessous) et fixer des objectifs de collecte.',
                'objectifs.suivi', 'Objectifs'
            );
        }

        if ($inactifs['nombre'] > 0) {
            $ajouter(
                'info',
                'Clients inactifs qui ont de l\'épargne',
                "{$inactifs['nombre']} client(s) sans opération depuis au moins 30 jours détiennent {$f($inactifs['epargne'])}.",
                'Faire visiter ces clients par leur agent pour relancer leurs mises (liste ci-dessous).',
                'agent.clients-non-collectes', 'Clients à visiter'
            );
        }

        if (! $agentId && ($synthese['epargne']['soldes_negatifs'] ?? 0) < 0) {
            $ajouter(
                'info',
                'Comptes clients à solde négatif',
                "Total des soldes négatifs : {$f($synthese['epargne']['soldes_negatifs'])}.",
                'Analyser l\'origine de chaque solde négatif et le régulariser.',
                'member.accounts', 'Comptes membres'
            );
        }

        if (! $agentId) {
            $sansAgent = DB::table('users')->where('role', 'membre')->where('client_category', 'client')
                ->where('status', true)->whereNull('agent_id')->count();
            if ($sansAgent > 0) {
                $ajouter(
                    'info',
                    'Clients sans agent attribué',
                    "{$sansAgent} client(s) actif(s) ne sont rattachés à aucun agent : personne n'est chargé de leur suivi.",
                    'Attribuer un agent à chaque client pour que les visites, la collecte et les relances soient suivies.',
                    'agent.clients-non-collectes', 'Clients à visiter'
                );
            }
        }

        $ordre = ['danger' => 0, 'warning' => 1, 'info' => 2];
        usort($alertes, fn ($a, $b) => $ordre[$a['niveau']] <=> $ordre[$b['niveau']]);

        return $alertes;
    }

    private function nom(?object $u): string
    {
        return $u ? (trim(($u->name ?? '') . ' ' . ($u->postnom ?? '') . ' ' . ($u->prenom ?? '')) ?: 'Non renseigné') : 'Non renseigné';
    }
}
