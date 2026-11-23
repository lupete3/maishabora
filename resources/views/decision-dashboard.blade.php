@extends('layouts.backend')

@section('title', 'Cockpit décisionnel')

@section('content')
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
    .decision-decision { border-radius: 8px; border-left: 4px solid; background: #fafbfd; }
    .decision-decision-danger { border-left-color: #d93025; background: #fdf3f2; }
    .decision-decision-warning { border-left-color: #f29900; background: #fff8ec; }
    .decision-decision-info { border-left-color: #315bb8; background: #f2f6ff; }
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

    {{-- Alertes et décisions (étape 3) --}}
    @php
        $styles = [
            'danger' => ['bx-error-circle', 'Critique'],
            'warning' => ['bx-error', 'À surveiller'],
            'info' => ['bx-info-circle', 'À traiter'],
        ];
        $compte = collect($alertes)->countBy('niveau');
    @endphp
    <div class="decision-card p-3 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h5 class="mb-0 decision-heading">
                Alertes et décisions
                <span class="decision-help" data-bs-toggle="tooltip" title="Règles appliquées aux chiffres de cette page (période, devise et agent choisis). Chaque alerte donne le constat chiffré et une décision proposée."><i class="bx bx-info-circle"></i></span>
            </h5>
            <div class="d-flex gap-1">
                @foreach (['danger' => 'critique(s)', 'warning' => 'à surveiller', 'info' => 'à traiter'] as $niveau => $libelle)
                    @if ($compte->get($niveau))
                        <span class="badge bg-label-{{ $niveau }}">{{ $compte->get($niveau) }} {{ $libelle }}</span>
                    @endif
                @endforeach
            </div>
        </div>

        @forelse ($alertes as $alerte)
            <div class="decision-decision decision-decision-{{ $alerte['niveau'] }} p-3 mb-2">
                <div class="d-flex flex-column flex-md-row gap-2 justify-content-between">
                    <div class="d-flex gap-2">
                        <i class="bx {{ $styles[$alerte['niveau']][0] }} fs-4 text-{{ $alerte['niveau'] }}"></i>
                        <div>
                            <div class="fw-bold">
                                {{ $alerte['titre'] }}
                                <span class="badge bg-label-{{ $alerte['niveau'] }} ms-1">{{ $styles[$alerte['niveau']][1] }}</span>
                            </div>
                            <div class="small text-muted">{{ $alerte['constat'] }}</div>
                            <div class="small mt-1"><span class="fw-bold">Décision proposée :</span> {{ $alerte['decision'] }}</div>
                        </div>
                    </div>
                    @if ($alerte['lien'])
                        <div class="flex-shrink-0">
                            <a href="{{ $alerte['lien'] }}" class="btn btn-sm btn-outline-{{ $alerte['niveau'] === 'info' ? 'primary' : $alerte['niveau'] }}">
                                {{ $alerte['lien_libelle'] }} <i class="bx bx-right-arrow-alt"></i>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="alert alert-success mb-0">
                <i class="bx bx-check-circle me-1"></i> Aucune alerte sur cette période : les indicateurs sont dans les repères.
            </div>
        @endforelse
    </div>

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

    {{-- 4. Graphiques de la période --}}
    <h6 class="decision-section">Graphiques · {{ $periode['label'] }} · {{ $devise }}</h6>
    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="decision-card p-3 h-100">
                <h5 class="mb-1 decision-heading">
                    Évolution de l'activité
                    <span class="decision-help" data-bs-toggle="tooltip" title="Collecte et retraits (barres), crédits octroyés et capital remboursé (lignes), découpés par {{ ['hour' => 'heure', 'day' => 'jour', 'week' => 'semaine', 'month' => 'mois'][$periode['granularite']] }}. Mêmes règles que les indicateurs ci-dessus."><i class="bx bx-info-circle"></i></span>
                </h5>
                <p class="small text-muted mb-2">Des barres vertes au-dessus des rouges : l'épargne progresse.</p>
                <div id="chartActivite" style="min-height: 320px;"></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="decision-card p-3 h-100">
                <h5 class="mb-1 decision-heading">
                    Composition des produits
                    <span class="decision-help" data-bs-toggle="tooltip" title="Revenus encaissés pendant la période, par source."><i class="bx bx-info-circle"></i></span>
                </h5>
                <p class="small text-muted mb-2">Total : {{ $montant($act['produits']['valeur']) }}</p>
                @if ($act['produits']['valeur'] > 0)
                    <div id="chartProduits" style="min-height: 300px;"></div>
                @else
                    <div class="text-muted py-5 text-center">Aucun produit encaissé sur la période.</div>
                @endif
            </div>
        </div>
        <div class="col-xl-{{ empty($graphiques['agents']) ? '12' : '6' }}">
            <div class="decision-card p-3 h-100">
                <h5 class="mb-1 decision-heading">
                    Encours et portefeuille à risque
                    <span class="decision-help" data-bs-toggle="tooltip" title="Capital restant dû (aire) et PAR 30 (ligne, échelle de droite) à la fin de chaque tranche. Valeurs passées reconstituées à partir des dates de paiement des échéances ; le dernier point correspond à la situation actuelle."><i class="bx bx-info-circle"></i></span>
                </h5>
                <p class="small text-muted mb-2">Un encours qui monte avec un PAR stable ou en baisse : croissance saine.</p>
                <div id="chartPortefeuille" style="min-height: 300px;"></div>
            </div>
        </div>
        @if (! empty($graphiques['agents']))
            <div class="col-xl-6">
                <div class="decision-card p-3 h-100">
                    <h5 class="mb-1 decision-heading">
                        Comparaison des agents
                        <span class="decision-help" data-bs-toggle="tooltip" title="Collecte de la période (écritures de l'agent) et encours actuel des crédits qu'il suit ; le PAR 30 de chaque agent apparaît au survol. Les 10 premiers."><i class="bx bx-info-circle"></i></span>
                    </h5>
                    <p class="small text-muted mb-2">Survolez une barre pour voir le PAR 30 de l'agent.</p>
                    <div id="chartAgents" style="min-height: 300px;"></div>
                </div>
            </div>
        @endif
    </div>

    @php
        $peutVoirClient = auth()->user()->canAny(['afficher-compte-membre', 'depot-compte-membre', 'retrait-compte-membre']);
    @endphp

    {{-- 5. Listes de travail --}}
    <h6 class="decision-section">Listes de travail <span class="text-muted fw-normal">(situation à ce jour · {{ $devise }})</span></h6>
    <div class="row g-3 mb-4">
        <div class="col-xl-7">
            <div class="decision-card decision-alert p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 decision-heading">
                        Crédits à surveiller
                        <span class="decision-help" data-bs-toggle="tooltip" title="Crédits non soldés ayant au moins une échéance impayée dépassée, classés selon l'ancienneté de la plus vieille échéance impayée (balance âgée). Montant = capital restant dû."><i class="bx bx-info-circle"></i></span>
                    </h5>
                    <a href="{{ route('report.credit.overview') }}" class="btn btn-sm btn-outline-primary"><i class="bx bx-show me-1"></i> Rapport</a>
                </div>
                <div class="row g-2 mb-3">
                    @foreach ($credits['tranches'] as $tranche)
                        <div class="col-6 col-lg-3">
                            <div class="p-2 bg-light rounded h-100">
                                <div class="decision-label">{{ $tranche['label'] }}</div>
                                <div class="decision-value">{{ $nombre($tranche['nombre']) }}</div>
                                <div class="small text-muted">{{ $montant($tranche['montant']) }}</div>
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
                                <th class="text-end">Restant dû</th>
                                <th class="text-end">Retard</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($credits['liste'] as $credit)
                                <tr @if ($peutVoirClient) onclick="window.location.href='{{ route('member.details', $credit['client_id']) }}'" style="cursor: pointer;" @endif>
                                    <td>{{ $credit['client'] }}<br><span class="small text-muted">{{ $credit['code'] }}</span></td>
                                    <td>{{ $credit['agent'] }}</td>
                                    <td class="text-end text-nowrap">{{ $montant($credit['restant']) }}</td>
                                    <td class="text-end text-nowrap">
                                        <span class="fw-bold text-{{ $credit['jours'] > 90 ? 'danger' : ($credit['jours'] > 30 ? 'warning' : 'body') }}">{{ $credit['jours'] }} j</span><br>
                                        <span class="small text-muted">{{ $credit['impayees'] }} échéance(s)</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">Aucun crédit en retard dans cette devise.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($credits['nombre'] > count($credits['liste']))
                    <p class="small text-muted mt-2 mb-0">Les 10 plus gros restants dus sur {{ $credits['nombre'] }} crédits en retard ({{ $montant($credits['total']) }}).</p>
                @endif
            </div>
        </div>

        <div class="col-xl-5">
            <div class="decision-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 decision-heading">
                        Clients inactifs avec épargne
                        <span class="decision-help" data-bs-toggle="tooltip" title="Clients actifs sans opération depuis au moins 30 jours qui ont encore un solde positif dans la devise : risque de départ et épargne qui dort."><i class="bx bx-info-circle"></i></span>
                    </h5>
                    <a href="{{ route('agent.clients-non-collectes') }}" class="btn btn-sm btn-outline-primary"><i class="bx bx-walk me-1"></i> À visiter</a>
                </div>
                <div class="row g-2 mb-3">
                    @foreach ($inactifs['tranches'] as $tranche)
                        <div class="col-6">
                            <div class="p-2 bg-light rounded h-100">
                                <div class="decision-label">{{ $tranche['label'] }}</div>
                                <div class="decision-value">{{ $nombre($tranche['nombre']) }}</div>
                                <div class="small text-muted">{{ $montant($tranche['epargne']) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="table-responsive">
                    <table class="table table-sm decision-table mb-0">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th class="text-end">Épargne</th>
                                <th class="text-end">Inactif</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($inactifs['liste'] as $client)
                                <tr @if ($peutVoirClient) onclick="window.location.href='{{ route('member.details', $client['id']) }}'" style="cursor: pointer;" @endif>
                                    <td>{{ $client['client'] }}<br><span class="small text-muted">{{ $client['agent'] }}</span></td>
                                    <td class="text-end text-nowrap">{{ $montant($client['epargne']) }}</td>
                                    <td class="text-end text-nowrap">{{ $client['jours'] }} j<br><span class="small text-muted">{{ $client['derniere']->format('d/m/Y') }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">Aucun client inactif avec épargne dans cette devise.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- 6. Performance des agents --}}
    <div class="decision-card p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 decision-heading">
                Performance des agents · {{ $periode['label'] }}
                <span class="decision-help" data-bs-toggle="tooltip" title="Clients rattachés à l'agent, clients ayant fait une opération sur la période, collecte de la période (écritures de l'agent), encours et PAR 30 actuels des crédits qu'il suit, crédits en retard. Comptes internes exclus."><i class="bx bx-info-circle"></i></span>
            </h5>
            <a href="{{ route('reports.agent-performance') }}" class="btn btn-sm btn-outline-primary"><i class="bx bx-bar-chart-alt-2 me-1"></i> Rapport agents</a>
        </div>
        <div class="table-responsive">
            <table class="table table-sm decision-table mb-0">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th class="text-end">Clients</th>
                        <th class="text-end">Actifs</th>
                        <th class="text-end">Collecte</th>
                        <th class="text-end">Encours</th>
                        <th class="text-end">PAR 30</th>
                        <th class="text-end">En retard</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($agents as $agent)
                        <tr>
                            <td>{{ $agent['nom'] }}<br><span class="small text-muted">{{ $agent['role'] }}</span></td>
                            <td class="text-end">{{ $nombre($agent['clients']) }}</td>
                            <td class="text-end">{{ $nombre($agent['actifs']) }}@if ($agent['taux_activite'] !== null)<br><span class="small text-muted">{{ $pct($agent['taux_activite']) }}</span>@endif</td>
                            <td class="text-end text-nowrap">{{ $montant($agent['collecte']) }}</td>
                            <td class="text-end text-nowrap">{{ $montant($agent['encours']) }}</td>
                            <td class="text-end">
                                @if ($agent['encours'] > 0)
                                    <span class="fw-bold text-{{ $niveauPar($agent['par30']) }}">{{ $pct($agent['par30']) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">{{ $nombre($agent['retards']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">Aucun agent avec des clients, une collecte ou des crédits dans cette devise.</td></tr>
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

        // ---- Graphiques (une seule devise à la fois) ----
        const devise = @json($devise);
        const g = @json($graphiques);
        const entier = v => v === null || v === undefined ? '—' : Math.round(Number(v)).toLocaleString('fr-FR');
        const montant = v => v === null || v === undefined ? '—' : entier(v) + ' ' + devise;
        // Axe compact : 1,2 M ; 350 k
        const compact = v => {
            const n = Math.abs(Number(v));
            if (n >= 1e6) return (Number(v) / 1e6).toLocaleString('fr-FR', { maximumFractionDigits: 1 }) + ' M';
            if (n >= 1e3) return (Number(v) / 1e3).toLocaleString('fr-FR', { maximumFractionDigits: 0 }) + ' k';
            return entier(v);
        };
        const commun = { toolbar: { show: false }, fontFamily: 'inherit', zoom: { enabled: false } };

        new ApexCharts(document.querySelector('#chartActivite'), {
            chart: { ...commun, type: 'line', height: 320, stacked: false },
            series: [
                { name: 'Collecte', type: 'column', data: g.flux.collecte },
                { name: 'Retraits', type: 'column', data: g.flux.retraits },
                { name: 'Crédits octroyés', type: 'line', data: g.flux.credits },
                { name: 'Capital remboursé', type: 'line', data: g.flux.remboursements },
            ],
            colors: ['#188038', '#d93025', '#315bb8', '#f29900'],
            stroke: { width: [0, 0, 3, 3], curve: 'smooth' },
            plotOptions: { bar: { columnWidth: '60%', borderRadius: 2 } },
            dataLabels: { enabled: false },
            xaxis: { categories: g.labels, tickAmount: Math.min(g.labels.length, 12), labels: { rotate: -45, hideOverlappingLabels: true } },
            yaxis: { labels: { formatter: compact } },
            tooltip: { shared: true, intersect: false, y: { formatter: montant } },
            legend: { position: 'top' },
        }).render();

        if (document.querySelector('#chartProduits')) {
            const produits = @json($act['produits_detail']);
            new ApexCharts(document.querySelector('#chartProduits'), {
                chart: { ...commun, type: 'donut', height: 300 },
                series: Object.values(produits).map(Number),
                labels: Object.keys(produits),
                colors: ['#315bb8', '#d93025', '#f29900', '#188038'],
                dataLabels: { formatter: v => v.toLocaleString('fr-FR', { maximumFractionDigits: 0 }) + ' %' },
                tooltip: { y: { formatter: montant } },
                legend: { position: 'bottom' },
                plotOptions: { pie: { donut: { labels: { show: true, total: { show: true, label: 'Total', formatter: w => compact(w.globals.seriesTotals.reduce((a, b) => a + b, 0)) } } } } },
            }).render();
        }

        new ApexCharts(document.querySelector('#chartPortefeuille'), {
            chart: { ...commun, type: 'line', height: 300 },
            series: [
                { name: 'Encours', type: 'area', data: g.portefeuille.encours },
                { name: 'PAR 30 (%)', type: 'line', data: g.portefeuille.par30 },
            ],
            colors: ['#315bb8', '#d93025'],
            stroke: { width: [2, 3], curve: 'straight' },
            fill: { type: ['gradient', 'solid'], gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
            dataLabels: { enabled: false },
            xaxis: { categories: g.labels, tickAmount: Math.min(g.labels.length, 12), labels: { rotate: -45, hideOverlappingLabels: true } },
            yaxis: [
                { title: { text: 'Encours (' + devise + ')' }, labels: { formatter: compact } },
                { opposite: true, min: 0, max: 100, title: { text: 'PAR 30' }, labels: { formatter: v => entier(v) + ' %' } },
            ],
            // Repère des 5 % sur l'échelle du PAR
            annotations: { yaxis: [{ y: 5, yAxisIndex: 1, borderColor: '#f29900', strokeDashArray: 4, label: { text: 'PAR 30 = 5 %', style: { color: '#fff', background: '#f29900' } } }] },
            tooltip: { shared: true, intersect: false, y: [{ formatter: montant }, { formatter: v => v === null ? '—' : Number(v).toLocaleString('fr-FR') + ' %' }] },
            legend: { position: 'top' },
        }).render();

        if (document.querySelector('#chartAgents')) {
            new ApexCharts(document.querySelector('#chartAgents'), {
                chart: { ...commun, type: 'bar', height: Math.max(300, g.agents.length * 42) },
                series: [
                    { name: 'Collecte de la période', data: g.agents.map(a => a.collecte) },
                    { name: 'Encours actuel', data: g.agents.map(a => a.encours) },
                ],
                colors: ['#188038', '#315bb8'],
                plotOptions: { bar: { horizontal: true, barHeight: '70%', borderRadius: 2 } },
                dataLabels: { enabled: false },
                xaxis: { categories: g.agents.map(a => a.nom), labels: { formatter: compact } },
                tooltip: {
                    shared: true, intersect: false,
                    y: { formatter: montant },
                    x: { formatter: (nom, { dataPointIndex }) => nom + ' · PAR 30 : ' + Number(g.agents[dataPointIndex].par30).toLocaleString('fr-FR') + ' %' },
                },
                legend: { position: 'top' },
            }).render();
        }

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
