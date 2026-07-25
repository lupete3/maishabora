@extends('layouts.backend')

@section('title', 'Cockpit décisionnel')

@section('content')
@php
    $money = function ($values) {
        if (! is_array($values) || count($values) === 0) {
            return '0';
        }

        return collect($values)->map(function ($amount, $currency) {
            return number_format((float) $amount, 2, ',', ' ') . ' ' . $currency;
        })->implode(' <br> ');
    };

    $fullName = fn ($user) => trim(($user?->name ?? '') . ' ' . ($user?->postnom ?? '') . ' ' . ($user?->prenom ?? '')) ?: 'Non renseigné';
@endphp

<style>
    .decision-shell { background: #f6f7fb; }
    .decision-card { border: 1px solid #e6e8ef; border-radius: 8px; background: #fff; box-shadow: 0 1px 2px rgba(35, 38, 47, .04); }
    .decision-kpi { display: flex; gap: .85rem; min-height: 118px; color: inherit; transition: transform .15s ease, box-shadow .15s ease; }
    .decision-kpi:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(35, 38, 47, .08); color: inherit; }
    .decision-icon { width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; background: #eef4ff; color: #315bb8; flex: 0 0 auto; }
    .decision-label { color: #697386; font-size: .78rem; text-transform: uppercase; letter-spacing: 0; font-weight: 700; }
    .decision-value { color: #1f2937; font-size: 0.8rem; line-height: 1.3; font-weight: 800; word-break: break-word; }
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
    @media (max-width: 767.98px) {
        .decision-value { font-size: 0.8rem; }
        .decision-kpi { min-height: auto; }
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y decision-shell">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Cockpit décisionnel</h4>
            <p class="text-muted mb-0">Vue isolée d’aide à la décision, calculée avec les données déjà disponibles.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-start">
            <a href="{{ route('rapports.transactions') }}" class="btn btn-sm btn-outline-primary">
                <i class="bx bx-file me-1"></i> Rapport journalier
            </a>
            <a href="{{ route('report.credit.overview') }}" class="btn btn-sm btn-outline-primary">
                <i class="bx bx-credit-card me-1"></i> Crédits
            </a>
            <a href="{{ route('member.register') }}" class="btn btn-sm btn-primary">
                <i class="bx bx-user-plus me-1"></i> Nouveau membre
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ($summary as $item)
            <div class="col-12 col-sm-6 col-xl-3">
                <a href="{{ $item['url'] }}" class="decision-card decision-kpi p-3 text-decoration-none">
                    <span class="decision-icon"><i class="bx {{ $item['icon'] }} fs-4"></i></span>
                    <span>
                        <span class="decision-label d-flex align-items-center gap-1">
                            {{ $item['label'] }}
                            <span class="decision-help" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $item['source'] ?? 'Donnée calculée automatiquement à partir des informations disponibles.' }}">
                                <i class="bx bx-info-circle"></i>
                            </span>
                        </span>
                        <span class="decision-value d-block mt-1">
                            {!! !empty($item['is_money']) ? $money($item['value']) : number_format((float) $item['value'], 0, ',', ' ') !!}
                        </span>
                    </span>
                </a>
            </div>
        @endforeach
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
        <div class="col-xl-5">
            <div class="decision-card p-3 h-100">
                <div class="d-flex flex-column gap-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <h5 class="mb-0 decision-heading">
                            Activités du jour
                            <span class="decision-help" data-bs-toggle="tooltip" data-bs-placement="top" title="Compte uniquement les opérations client liées à un compte membre. Le filtre agent utilise le client propriétaire du compte, puis son agent responsable.">
                                <i class="bx bx-info-circle"></i>
                            </span>
                        </h5>
                        @if ($selectedCurrency || $selectedAgentId)
                            <a href="{{ route('decision.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bx bx-reset me-1"></i> Réinitialiser
                            </a>
                        @endif
                    </div>

                    <form method="GET" action="{{ route('decision.dashboard') }}" class="row g-2 align-items-end">
                        <div class="col-12 col-sm-4">
                            <label for="activity-currency" class="form-label mb-1">Devise</label>
                            <select id="activity-currency" name="currency" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Toutes</option>
                                <option value="USD" @selected($selectedCurrency === 'USD')>USD</option>
                                <option value="CDF" @selected($selectedCurrency === 'CDF')>CDF</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="activity-agent" class="form-label mb-1">Agent</label>
                            <select id="activity-agent" name="agent_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">Tous les agents</option>
                                @foreach ($agentOptions as $agent)
                                    <option value="{{ $agent->id }}" @selected((int) $selectedAgentId === (int) $agent->id)>
                                        {{ trim($agent->name . ' ' . $agent->postnom . ' ' . $agent->prenom) }} - {{ $agent->role }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-2">
                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                <i class="bx bx-filter-alt"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm decision-table mb-0">
                        <thead>
                            <tr>
                                <th>Activité</th>
                                <th>Nombre</th>
                                <th>Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($activity as $line)
                                <tr>
                                    <td>{{ $line['label'] }}</td>
                                    <td>{{ number_format($line['count'], 0, ',', ' ') }}</td>
                                    <td>{!! $line['amount'] ? $money($line['amount']) : '-' !!}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
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
        window.setTimeout(() => window.location.reload(), 60000);
    });
</script>
@endsection
