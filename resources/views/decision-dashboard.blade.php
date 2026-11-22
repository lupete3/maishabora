@extends('layouts.backend')

@section('title', 'Cockpit décisionnel')

@section('content')
@php
    $fullName = fn ($user) => trim(($user?->name ?? '') . ' ' . ($user?->postnom ?? '') . ' ' . ($user?->prenom ?? '')) ?: 'Non renseigné';
@endphp

<style>
    .decision-shell { background: #f6f7fb; }
    .decision-card { border: 1px solid #e6e8ef; border-radius: 8px; background: #fff; box-shadow: 0 1px 2px rgba(35, 38, 47, .04); }
    .decision-kpi { display: flex; gap: .85rem; min-height: 118px; color: inherit; transition: transform .15s ease, box-shadow .15s ease; }
    .decision-kpi:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(35, 38, 47, .08); color: inherit; }
    .decision-icon { width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; background: #eef4ff; color: #315bb8; flex: 0 0 auto; }
    .decision-label { color: #697386; font-size: .78rem; text-transform: uppercase; letter-spacing: 0; font-weight: 700; }
    .decision-value { color: #1f2937; font-size: 1rem; line-height: 1.3; font-weight: 800; word-break: break-word; }
    .decision-band { border-left: 4px solid #315bb8; }
    .decision-alert { border-left: 4px solid #d93025; }
    .decision-good { border-left: 4px solid #188038; }
    .decision-table th { color: #697386; font-size: .76rem; text-transform: uppercase; letter-spacing: 0; }
    .decision-pill { border-radius: 999px; padding: .28rem .58rem; background: #eef4ff; color: #315bb8; font-size: .78rem; font-weight: 700; white-space: nowrap; }
    .decision-progress { height: 8px; border-radius: 999px; background: #edf0f5; overflow: hidden; }
    .decision-progress span { display: block; height: 100%; background: #315bb8; }
    .decision-help { display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 50%; color: #697386; background: #f1f3f8; cursor: help; flex: 0 0 auto; }
    .decision-help:hover { color: #315bb8; background: #e4ebff; }
    .decision-heading { display: flex; align-items: center; gap: .45rem; }
    .decision-section { color: #697386; font-size: .8rem; text-transform: uppercase; font-weight: 800; margin-bottom: .6rem; }
    .decision-big { color: #1f2937; font-size: 1.15rem; font-weight: 800; line-height: 1.3; }
    .decision-var { font-size: .78rem; font-weight: 700; white-space: nowrap; }
    @media (max-width: 767.98px) {
        .decision-value { font-size: 0.8rem; }
        .decision-kpi { min-height: auto; }
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y decision-shell">
    @php
        $devise = $synthese['devise'];
        $act = $synthese['activite'];
        $pf = $synthese['portefeuille'];
        $liq = $synthese['liquidite'];
        $ep = $synthese['epargne'];
        $portee = $synthese['portee'];
        $montant = fn ($v) => number_format((float) $v, 0, ',', ' ') . ' ' . $devise;
        $nombre = fn ($v) => number_format((float) $v, 0, ',', ' ');
        $pct = fn ($v) => $v === null ? '—' : number_format((float) $v, 1, ',', ' ') . ' %';
        // Variation par rapport à la période précédente ; $inverse : une hausse est mauvaise (retraits…)
        $variation = function (array $ligne, bool $inverse = false) use ($periode) {
            $v = \App\Services\DecisionDashboardService::variation((float) $ligne['valeur'], (float) $ligne['precedent']);
            if ($v === null) {
                return '<span class="decision-var text-muted" title="Pas de base de comparaison">— vs ' . e($periode['prev_label']) . '</span>';
            }
            $bon = $inverse ? $v <= 0 : $v >= 0;
            $fleche = $v > 0 ? '▲' : ($v < 0 ? '▼' : '■');

            return '<span class="decision-var ' . ($bon ? 'text-success' : 'text-danger') . '">' . $fleche . ' '
                . number_format(abs($v), 1, ',', ' ') . ' %</span> <span class="text-muted small">vs ' . e($periode['prev_label']) . '</span>';
        };
        // Seuils usuels en microfinance : PAR 30 < 5 % sain, < 10 % à surveiller, au-delà critique
        $niveauPar = fn ($v) => $v < 5 ? 'success' : ($v < 10 ? 'warning' : 'danger');
        $niveauCouverture = fn ($v) => $v === null ? 'secondary' : ($v >= 20 ? 'success' : ($v >= 10 ? 'warning' : 'danger'));
        $lienFiltre = fn (array $changes) => route('decision.dashboard', array_filter(array_merge($filtres, $changes), fn ($v) => $v !== null && $v !== ''));
    @endphp

    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
        <div>
            <h4 class="fw-bold mb-1">Cockpit décisionnel</h4>
            <p class="text-muted mb-0">Pilotage de l'institution : activité, portefeuille de crédit, épargne et liquidité.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-start">
            <a href="{{ route('rapports.transactions') }}" class="btn btn-sm btn-outline-primary">
                <i class="bx bx-file me-1"></i> Rapport journalier
            </a>
            <a href="{{ route('report.credit.overview') }}" class="btn btn-sm btn-outline-primary">
                <i class="bx bx-credit-card me-1"></i> Crédits
            </a>
        </div>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('decision.dashboard') }}" class="decision-card p-3 mb-3" id="cockpit-filtres">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" for="f-periode">Période</label>
                <select id="f-periode" name="periode" class="form-select form-select-sm">
                    @foreach (\App\Services\DecisionDashboardService::PERIODES as $cle => $libelle)
                        <option value="{{ $cle }}" @selected($periode['type'] === $cle)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2 js-ref">
                <label class="form-label mb-1" for="f-ref">Date de référence</label>
                <input type="date" id="f-ref" name="ref" class="form-control form-control-sm"
                    value="{{ $periode['type'] === 'custom' ? now()->toDateString() : $periode['start']->toDateString() }}" max="{{ now()->toDateString() }}">
            </div>
            <div class="col-6 col-md-2 js-intervalle">
                <label class="form-label mb-1" for="f-du">Du</label>
                <input type="date" id="f-du" name="du" class="form-control form-control-sm" value="{{ $periode['start']->toDateString() }}">
            </div>
            <div class="col-6 col-md-2 js-intervalle">
                <label class="form-label mb-1" for="f-au">Au</label>
                <input type="date" id="f-au" name="au" class="form-control form-control-sm" value="{{ $periode['end']->toDateString() }}">
            </div>
            <div class="col-6 col-md-1">
                <label class="form-label mb-1" for="f-devise">Devise</label>
                <select id="f-devise" name="devise" class="form-select form-select-sm">
                    @foreach (\App\Services\DecisionDashboardService::DEVISES as $d)
                        <option value="{{ $d }}" @selected($devise === $d)>{{ $d }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label mb-1" for="f-agent">Agent</label>
                <select id="f-agent" name="agent_id" class="form-select form-select-sm">
                    <option value="">Toute l'institution</option>
                    @foreach ($agentOptions as $agent)
                        <option value="{{ $agent->id }}" @selected((int) $filtres['agent_id'] === (int) $agent->id)>
                            {{ trim($agent->name . ' ' . $agent->postnom . ' ' . $agent->prenom) }} ({{ $agent->role }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-auto d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bx bx-filter-alt me-1"></i> Appliquer</button>
                <a href="{{ route('decision.dashboard') }}" class="btn btn-sm btn-outline-secondary" title="Réinitialiser"><i class="bx bx-reset"></i></a>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
            <a href="{{ $lienFiltre(['ref' => $navigation['precedente'], 'du' => null, 'au' => null]) }}"
                class="btn btn-sm btn-icon btn-outline-secondary @if ($periode['type'] === 'custom') disabled @endif" title="Période précédente">
                <i class="bx bx-chevron-left"></i>
            </a>
            <span class="fw-bold">{{ $periode['label'] }}</span>
            @if ($navigation['suivante'] && $periode['type'] !== 'custom')
                <a href="{{ $lienFiltre(['ref' => $navigation['suivante'], 'du' => null, 'au' => null]) }}"
                    class="btn btn-sm btn-icon btn-outline-secondary" title="Période suivante">
                    <i class="bx bx-chevron-right"></i>
                </a>
            @endif
            <span class="text-muted small">comparée à {{ $periode['prev_label'] }} · montants en {{ $devise }}
                @if ($filtres['agent_id']) · agent filtré @endif
            </span>
        </div>
    </form>

    {{-- 1. Activité de la période --}}
    <h6 class="decision-section">Activité de la période</h6>
    <div class="row g-3 mb-4">
        @foreach ([
            ['Collecte (dépôts et mises)', $montant($act['collecte']['valeur']), $variation($act['collecte']), 'bx-trending-up', 'Dépôts et mises enregistrés sur les comptes des membres pendant la période (chaque opération comptée une seule fois).'],
            ['Retraits', $montant($act['retraits']['valeur']), $variation($act['retraits'], true), 'bx-trending-down', 'Retraits effectués par les membres pendant la période.'],
            ['Épargne nette', $montant($act['epargne_nette']['valeur']), $variation($act['epargne_nette']), 'bx-line-chart', 'Collecte moins retraits : positive, l\'épargne confiée à l\'institution augmente.'],
            ['Crédits octroyés', $nombre($act['credits_nombre']['valeur']) . ' · ' . $montant($act['credits_montant']['valeur']), $variation($act['credits_montant']), 'bx-credit-card', 'Nombre et montant des crédits accordés pendant la période.'],
            ['Capital remboursé', $montant($act['remboursements']['valeur']), $variation($act['remboursements']), 'bx-refresh', 'Part du capital des crédits remboursée pendant la période.'],
            ['Produits encaissés', $montant($act['produits']['valeur']), $variation($act['produits']), 'bx-coin-stack', 'Intérêts, pénalités, frais de crédit et ventes de carnets encaissés pendant la période.'],
            ['Taux de remboursement', $pct($act['taux_remboursement']['valeur']), $variation($act['taux_remboursement']), 'bx-check-shield', 'Montant payé ÷ montant attendu sur les échéances tombées pendant la période.'],
            ['Nouveaux membres', $nombre($act['nouveaux_membres']['valeur']), $variation($act['nouveaux_membres']), 'bx-user-plus', 'Membres inscrits pendant la période (toutes devises).'],
            ['Membres actifs', $nombre($act['membres_actifs']['valeur']), $variation($act['membres_actifs']), 'bx-user-check', 'Membres ayant fait au moins une opération pendant la période (toutes devises).'],
        ] as [$label, $valeur, $var, $icone, $aide])
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="decision-card decision-kpi p-3 h-100">
                    <span class="decision-icon"><i class="bx {{ $icone }} fs-4"></i></span>
                    <span class="flex-grow-1">
                        <span class="decision-label d-flex align-items-center gap-1">
                            {{ $label }}
                            <span class="decision-help" data-bs-toggle="tooltip" title="{{ $aide }}"><i class="bx bx-info-circle"></i></span>
                        </span>
                        <span class="decision-value d-block mt-1">{{ $valeur }}</span>
                        <span class="d-block mt-1">{!! $var !!}</span>
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        {{-- 2. Portefeuille de crédit --}}
        <div class="col-xl-6">
            <h6 class="decision-section">Portefeuille de crédit <span class="text-muted fw-normal">(situation à ce jour)</span></h6>
            <div class="decision-card p-3 h-100">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="decision-label">Encours (capital restant dû)</div>
                        <div class="decision-big">{{ $montant($pf['encours']) }}</div>
                        <div class="small text-muted">{{ $nombre($pf['emprunteurs']) }} emprunteur(s) actif(s) · crédit moyen {{ $montant($pf['credit_moyen']) }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="decision-label">Crédits en retard</div>
                        <div class="decision-big text-{{ $pf['en_retard'] > 0 ? 'danger' : 'success' }}">{{ $nombre($pf['en_retard']) }}</div>
                        <div class="small text-muted">au moins une échéance impayée dépassée</div>
                    </div>
                </div>
                <hr>
                <div class="decision-label d-flex align-items-center gap-1 mb-2">
                    Portefeuille à risque (PAR)
                    <span class="decision-help" data-bs-toggle="tooltip" title="Part de l'encours dont une échéance est impayée depuis plus de N jours. Repères : PAR 30 inférieur à 5 % = sain ; 5 à 10 % = à surveiller ; au-delà = critique."><i class="bx bx-info-circle"></i></span>
                </div>
                @foreach (['PAR 1 jour' => $pf['par1'], 'PAR 30 jours' => $pf['par30'], 'PAR 90 jours' => $pf['par90']] as $label => $valeur)
                    <div class="d-flex justify-content-between small mb-1">
                        <span>{{ $label }}</span>
                        <span class="fw-bold text-{{ $niveauPar($valeur) }}">{{ $pct($valeur) }}</span>
                    </div>
                    <div class="decision-progress mb-2"><span class="bg-{{ $niveauPar($valeur) }}" style="width: {{ min(100, $valeur) }}%"></span></div>
                @endforeach
            </div>
        </div>

        {{-- 3. Épargne, liquidité, portée --}}
        <div class="col-xl-6">
            <h6 class="decision-section">Épargne et liquidité <span class="text-muted fw-normal">(situation à ce jour)</span></h6>
            <div class="decision-card p-3 h-100">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="decision-label d-flex align-items-center gap-1">
                            Dépôts de la clientèle
                            <span class="decision-help" data-bs-toggle="tooltip" title="Ce que l'institution doit à ses clients : somme des soldes positifs de leurs comptes."><i class="bx bx-info-circle"></i></span>
                        </div>
                        <div class="decision-big">{{ $montant($ep['depots']) }}</div>
                        @if ($ep['epargnants'] !== null)
                            <div class="small text-muted">{{ $nombre($ep['epargnants']) }} compte(s) créditeur(s)
                                @if ($ep['soldes_negatifs'] < 0) · soldes négatifs {{ $montant($ep['soldes_negatifs']) }} @endif
                            </div>
                        @endif
                    </div>
                    <div class="col-sm-6">
                        <div class="decision-label d-flex align-items-center gap-1">
                            Liquidité disponible
                            <span class="decision-help" data-bs-toggle="tooltip" title="Caisse centrale + caisses des agents{{ $filtres['agent_id'] ? ' (ici : caisse de l\'agent filtré)' : '' }}."><i class="bx bx-info-circle"></i></span>
                        </div>
                        <div class="decision-big">{{ $montant($liq['total']) }}</div>
                        <div class="small text-muted">centrale {{ $montant($liq['caisse_centrale']) }} · agents {{ $montant($liq['caisses_agents']) }}</div>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="decision-label d-flex align-items-center gap-1">
                        Couverture des dépôts par la liquidité
                        <span class="decision-help" data-bs-toggle="tooltip" title="Liquidité ÷ dépôts de la clientèle. Indique la capacité à servir des retraits massifs. Repère interne : 20 % ou plus = confortable ; 10 à 20 % = à surveiller ; moins de 10 % = tension."><i class="bx bx-info-circle"></i></span>
                    </span>
                    <span class="fw-bold text-{{ $niveauCouverture($liq['couverture']) }}">{{ $pct($liq['couverture']) }}</span>
                </div>
                <div class="decision-progress mb-3"><span class="bg-{{ $niveauCouverture($liq['couverture']) }}" style="width: {{ min(100, (float) $liq['couverture']) }}%"></span></div>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="decision-label d-flex align-items-center gap-1">
                        Portée : membres clients
                        <span class="decision-help" data-bs-toggle="tooltip" title="Membres de catégorie « client » (hors comptes internes) et part des femmes, indicateur suivi par les bailleurs."><i class="bx bx-info-circle"></i></span>
                    </span>
                    <span class="fw-bold">{{ $nombre($portee['membres']) }} · {{ $pct($portee['part_femmes']) }} de femmes</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-7">
            <div class="decision-card decision-alert p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 decision-heading">
                        Centre d’alertes
                        <span class="decision-help" data-bs-toggle="tooltip" data-bs-placement="top" title="Regroupe les membres sans opération récente, les échéances dues aujourd’hui et les crédits en retard.">
                            <i class="bx bx-info-circle"></i>
                        </span>
                    </h5>
                    <span class="decision-pill">{{ collect($creditAlerts['overdue'])->sum('count') }} retards</span>
                </div>

                <div class="row g-3">
                    @foreach ($inactiveBuckets as $bucket)
                        <div class="col-sm-6 col-lg-3">
                            <div class="p-3 bg-light rounded">
                                <div class="decision-label d-flex align-items-center gap-1">
                                    {{ $bucket['label'] }}
                                    <span class="decision-help" data-bs-toggle="tooltip" data-bs-placement="top" title="Clients actifs administrativement sans dernière opération dans cette tranche de jours.">
                                        <i class="bx bx-info-circle"></i>
                                    </span>
                                </div>
                                <div class="decision-value">{{ $bucket['count'] }}</div>
                                <a href="{{ route('rapports.clients') }}" class="small">Voir la liste</a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-sm decision-table mb-0">
                        <thead>
                            <tr>
                                <th>Client inactif</th>
                                <th>Agent</th>
                                <th>Dernière opération</th>
                                <th>Jours</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (collect($inactiveBuckets)->flatMap(fn ($bucket) => $bucket['members'])->take(10) as $member)
                                @php
                                    $lastActivity = $member->last_transaction_at ?? $member->created_at;
                                @endphp
                                <tr @if(auth()->user()->canAny(['afficher-compte-membre', 'depot-compte-membre', 'retrait-compte-membre']))
                                    onclick="window.location.href='{{ route('member.details', $member->id) }}'"
                                    style="cursor: pointer;"
                                @endif >
                                    <td>{{ $fullName($member) }} <br> {{ $member->code }}</td>
                                    <td>{{ $fullName($member->agent) }}</td>
                                    <td>{{ $lastActivity?->format('d/m/Y') ?? 'Non renseigné' }}</td>
                                    <td>{{ $lastActivity ? (int) abs(now()->startOfDay()->diffInDays($lastActivity->copy()->startOfDay(), false)) : '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">Aucun client inactif détecté dans les seuils suivis.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="decision-card p-3 h-100">
                <h5 class="mb-3 decision-heading">
                    Actions prioritaires
                    <span class="decision-help" data-bs-toggle="tooltip" data-bs-placement="top" title="Tâches générées depuis les inactivités longues, les échéances du jour, les retards et les alertes financières.">
                        <i class="bx bx-info-circle"></i>
                    </span>
                </h5>
                <div class="d-grid gap-2">
                    @foreach ($priorities as $priority)
                        <div class="d-flex gap-2 align-items-start p-2 bg-light rounded">
                            <i class="bx bx-target-lock text-primary fs-5"></i>
                            <span>{{ $priority }}</span>
                        </div>
                    @endforeach
                </div>

                <h6 class="fw-bold mt-4 mb-2 decision-heading">
                    Alertes financières
                    <span class="decision-help" data-bs-toggle="tooltip" data-bs-placement="top" title="Détecte une caisse négative, une baisse de collecte ou une hausse de retraits par comparaison avec les derniers jours.">
                        <i class="bx bx-info-circle"></i>
                    </span>
                </h6>
                @forelse ($financialAlerts as $alert)
                    <div class="alert alert-warning py-2 mb-2">{{ $alert }}</div>
                @empty
                    <div class="alert alert-success py-2 mb-0">Aucune alerte financière automatique détectée.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="decision-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 decision-heading">
                        Crédits à surveiller
                        <span class="decision-help" data-bs-toggle="tooltip" data-bs-placement="top" title="Utilise les remboursements non payés : échéance aujourd’hui ou échéance passée pour les retards.">
                            <i class="bx bx-info-circle"></i>
                        </span>
                    </h5>
                    <a href="{{ route('report.credit.overview') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bx bx-show me-1"></i> Voir rapport
                    </a>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded">
                            <div class="decision-label d-flex align-items-center gap-1">
                                Échéances aujourd’hui
                                <span class="decision-help" data-bs-toggle="tooltip" data-bs-placement="top" title="Remboursements non payés dont la date d’échéance est aujourd’hui.">
                                    <i class="bx bx-info-circle"></i>
                                </span>
                            </div>
                            <div class="decision-value">{{ $creditAlerts['todayDue']->count() }}</div>
                        </div>
                    </div>
                    @foreach ($creditAlerts['overdue'] as $bucket)
                        <div class="col-sm-6 col-lg-3">
                            <div class="p-3 bg-light rounded">
                                <div class="decision-label d-flex align-items-center gap-1">
                                    {{ $bucket['label'] }}
                                    <span class="decision-help" data-bs-toggle="tooltip" data-bs-placement="top" title="Remboursements non payés classés selon le nombre de jours depuis l’échéance.">
                                        <i class="bx bx-info-circle"></i>
                                    </span>
                                </div>
                                <div class="decision-value">{{ $bucket['count'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="table-responsive">
                    <table class="table table-sm decision-table mb-0">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Agent</th>
                                <th>Reste</th>
                                <th>Retard</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (collect($creditAlerts['overdue'])->flatMap(fn ($bucket) => $bucket['items'])->take(8) as $repayment)
                                <tr @if(auth()->user()->canAny(['afficher-compte-membre', 'depot-compte-membre', 'retrait-compte-membre']))
                                    onclick="window.location.href='{{ route('member.details', $repayment->credit?->user->id) }}'"
                                    style="cursor: pointer;"
                                @endif>
                                    <td>{{ $fullName($repayment->credit?->user) }} <br>{{ $repayment->credit?->user->code }}</td>
                                    <td>{{ $fullName($repayment->credit?->agent) }}</td>
                                    <td>{{ number_format(max(0, (float) $repayment->total_due - (float) $repayment->paid_amount), 2, ',', ' ') }} {{ $repayment->credit?->currency }}</td>
                                    <td>{{ (int) abs(now()->startOfDay()->diffInDays($repayment->due_date->copy()->startOfDay(), false)) }} jours</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">Aucun remboursement en retard détecté.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-7">
            <div class="decision-card p-3 h-100">
                <h5 class="mb-3 decision-heading">
                    Tendances sur 30 jours
                    <span class="decision-help" data-bs-toggle="tooltip" data-bs-placement="top" title="Graphique construit à partir des montants quotidiens des collectes, retraits, remboursements et octrois de crédit sur 30 jours.">
                        <i class="bx bx-info-circle"></i>
                    </span>
                </h5>
                <div id="decisionTrendChart" style="min-height: 330px;"></div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="decision-card decision-band p-3 h-100">
                <h5 class="mb-3 decision-heading">
                    Analyse intelligente
                    <span class="decision-help" data-bs-toggle="tooltip" data-bs-placement="top" title="Commentaires générés par règles simples à partir des tendances, retards, inactivités et alertes financières disponibles.">
                        <i class="bx bx-info-circle"></i>
                    </span>
                </h5>
                <div class="d-grid gap-2">
                    @foreach ($analysis as $message)
                        <div class="p-2 bg-light rounded">{{ $message }}</div>
                    @endforeach
                </div>

                <h6 class="fw-bold mt-4 mb-2 decision-heading">
                    Recommandations
                    <span class="decision-help" data-bs-toggle="tooltip" data-bs-placement="top" title="Actions proposées à partir des mêmes priorités automatiques affichées en haut de page.">
                        <i class="bx bx-info-circle"></i>
                    </span>
                </h6>
                <div class="d-grid gap-2">
                    @foreach ($priorities as $priority)
                        <div class="d-flex gap-2 align-items-start">
                            <i class="bx bx-check-circle text-success fs-5"></i>
                            <span>{{ $priority }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="decision-card p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 decision-heading">
                Performance des agents
                <span class="decision-help" data-bs-toggle="tooltip" data-bs-placement="top" title="Compare les agents recouvreurs avec leurs clients, collectes, crédits suivis et crédits en retard.">
                    <i class="bx bx-info-circle"></i>
                </span>
            </h5>
            <a href="{{ route('reports.agent-performance') }}" class="btn btn-sm btn-outline-primary">
                <i class="bx bx-bar-chart-alt-2 me-1"></i> Rapport agents
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-sm decision-table mb-0">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Clients</th>
                        <th>Actifs</th>
                        <th>Inactifs</th>
                        <th>Collecte jour</th>
                        <th>Collecte mois</th>
                        <th>Crédits suivis</th>
                        <th>Crédits retard</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($agents as $agent)
                        <tr>
                            <td>{{ $fullName($agent) }}</td>
                            <td>{{ $agent->clients_count }}</td>
                            <td>{{ $agent->active_clients_count }}</td>
                            <td>{{ $agent->inactive_clients_count }}</td>
                            <td>{{ number_format((float) $agent->collection_today, 2, ',', ' ') }}</td>
                            <td>{{ number_format((float) $agent->collection_month, 2, ',', ' ') }}</td>
                            <td>{{ $agent->credits_followed_count }}</td>
                            <td>{{ $agent->overdue_credits_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-muted">Aucun agent recouvreur actif dans les données actuelles.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="decision-card p-3">
        <h5 class="mb-3 decision-heading">
            Raccourcis
            <span class="decision-help" data-bs-toggle="tooltip" data-bs-placement="top" title="Accès directs vers les opérations et rapports déjà existants dans l’application.">
                <i class="bx bx-info-circle"></i>
            </span>
        </h5>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('member.register') }}" class="btn btn-outline-primary"><i class="bx bx-user-plus me-1"></i> Nouveau membre</a>
            <a href="{{ route('credit.grant') }}" class="btn btn-outline-primary"><i class="bx bx-credit-card me-1"></i> Nouveau crédit</a>
            <a href="{{ route('deposit.member') }}" class="btn btn-outline-primary"><i class="bx bx-plus-circle me-1"></i> Nouveau dépôt</a>
            <a href="{{ route('members.withdrawfrom-card') }}" class="btn btn-outline-primary"><i class="bx bx-minus-circle me-1"></i> Nouveau retrait</a>
            <a href="{{ route('report.repayments') }}" class="btn btn-outline-primary"><i class="bx bx-refresh me-1"></i> Encaissement</a>
            <a href="{{ route('rapports.transactions') }}" class="btn btn-outline-primary"><i class="bx bx-file me-1"></i> Rapport journalier</a>
            <a href="{{ route('rapports.depot_retrait') }}" class="btn btn-outline-primary"><i class="bx bx-spreadsheet me-1"></i> Rapport mensuel</a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
            new bootstrap.Tooltip(element);
        });

        const chart = new ApexCharts(document.querySelector('#decisionTrendChart'), {
            chart: { type: 'area', height: 330, toolbar: { show: false } },
            stroke: { curve: 'smooth', width: 2 },
            dataLabels: { enabled: false },
            colors: ['#315bb8', '#d93025', '#188038', '#f29900'],
            series: [
                { name: 'Collectes', data: @json($trends['collectes']) },
                { name: 'Retraits', data: @json($trends['retraits']) },
                { name: 'Remboursements', data: @json($trends['remboursements']) },
                { name: 'Crédits', data: @json($trends['credits']) },
            ],
            xaxis: { categories: @json($trends['labels']) },
            yaxis: { labels: { formatter: value => Number(value).toLocaleString('fr-FR') } },
            tooltip: { y: { formatter: value => Number(value).toLocaleString('fr-FR') } },
            legend: { position: 'top' },
        });

        chart.render();
        // Champs de date selon le type de période
        const typePeriode = document.getElementById('f-periode');
        const afficherChamps = () => {
            const intervalle = typePeriode.value === 'custom';
            document.querySelectorAll('.js-intervalle').forEach(el => el.classList.toggle('d-none', !intervalle));
            document.querySelectorAll('.js-ref').forEach(el => el.classList.toggle('d-none', intervalle));
            document.querySelectorAll('.js-intervalle input').forEach(el => el.disabled = !intervalle);
            document.getElementById('f-ref').disabled = intervalle;
        };
        typePeriode.addEventListener('change', afficherChamps);
        afficherChamps();
    });
</script>
@endsection
