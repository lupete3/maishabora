@extends('layouts.backend')

@section('title', 'Carnets non renouvelés')

@section('content')
@php
    $nombre = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $lienFiltre = fn (array $changes) => route('carnets.non-renouveles', array_filter(array_merge($filtres, $changes), fn ($v) => $v !== null && $v !== '' && $v !== false));
    $peutVoirClient = auth()->user()->canAny(['afficher-compte-membre', 'depot-compte-membre', 'retrait-compte-membre']);
    $couleursTranches = ['Moins de 7 jours' => 'success', '7 à 30 jours' => 'warning', 'Plus de 30 jours' => 'danger'];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="bx bx-revision me-1"></i> Carnets non renouvelés</h4>
            <p class="text-muted mb-0">Clients qui ont retiré leur carnet sans en reprendre un : à relancer.</p>
        </div>
        <div>
            <a href="{{ route('carnets.non-renouveles.export', request()->query()) }}" class="btn btn-success">
                <i class="bx bx-spreadsheet me-1"></i> Exporter (Excel)
            </a>
        </div>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('carnets.non-renouveles') }}" class="card p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label mb-1" for="f-periode">Retraits du</label>
                <select id="f-periode" name="periode" class="form-select form-select-sm">
                    @foreach (\App\Services\CardRenewalService::PERIODES as $cle => $libelle)
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
            <div class="col-12 col-md-3">
                <label class="form-label mb-1" for="f-agent">Agent collecteur</label>
                <select id="f-agent" name="agent_id" class="form-select form-select-sm">
                    <option value="">Tous les agents</option>
                    @foreach ($agentOptions as $agent)
                        <option value="{{ $agent->id }}" @selected((int) $filtres['agent_id'] === (int) $agent->id)>
                            {{ trim($agent->name . ' ' . $agent->postnom) }} ({{ $agent->role }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-4 col-md-2 col-xl-1">
                <label class="form-label mb-1" for="f-devise">Devise</label>
                <select id="f-devise" name="devise" class="form-select form-select-sm">
                    <option value="">Toutes</option>
                    @foreach (['CDF', 'USD'] as $d)
                        <option value="{{ $d }}" @selected($filtres['devise'] === $d)>{{ $d }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-4 col-md-2 col-xl-1">
                <label class="form-label mb-1" for="f-type">Carnet</label>
                <select id="f-type" name="type" class="form-select form-select-sm">
                    @foreach (\App\Services\CardRenewalService::TYPES as $cle => $libelle)
                        <option value="{{ $cle }}" @selected($filtres['type'] === $cle)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-4 col-md-2 col-xl-1">
                <label class="form-label mb-1" for="f-retard" title="Nombre de jours minimum depuis le retrait">Retard min.</label>
                <input type="number" min="0" id="f-retard" name="retard_min" class="form-control form-control-sm" value="{{ $filtres['retard_min'] ?: '' }}" placeholder="jours">
            </div>
            <div class="col-12 col-md-auto">
                <div class="form-check mb-1">
                    <input class="form-check-input" type="checkbox" id="f-actifs" name="masquer_actifs" value="1" @checked($filtres['masquer_actifs'])>
                    <label class="form-check-label small" for="f-actifs">Masquer les clients qui ont encore un carnet actif</label>
                </div>
            </div>
            <div class="col-12 col-md-auto d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bx bx-filter-alt me-1"></i> Appliquer</button>
                <a href="{{ route('carnets.non-renouveles') }}" class="btn btn-sm btn-outline-secondary" title="Réinitialiser"><i class="bx bx-reset"></i></a>
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
            @if ($periode['type'] !== 'custom')
                <a href="{{ $lienFiltre(['ref' => $navigation['precedente'], 'du' => null, 'au' => null, 'page' => null]) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Période précédente"><i class="bx bx-chevron-left"></i></a>
            @endif
            <span class="fw-bold">Retraits : {{ $periode['label'] }}</span>
            @if ($navigation['suivante'] && $periode['type'] !== 'custom')
                <a href="{{ $lienFiltre(['ref' => $navigation['suivante'], 'du' => null, 'au' => null, 'page' => null]) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Période suivante"><i class="bx bx-chevron-right"></i></a>
            @endif
            <span class="text-muted small">· renouvellement vérifié à ce jour ({{ now()->format('d/m/Y') }})</span>
        </div>
    </form>

    {{-- Compteurs --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card p-3 h-100">
                <div class="small text-muted text-uppercase fw-bold">Carnets retirés</div>
                <div class="fs-4 fw-bold">{{ $nombre($resume['retraits']) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card p-3 h-100">
                <div class="small text-muted text-uppercase fw-bold">Renouvelés</div>
                <div class="fs-4 fw-bold text-success">{{ $nombre($resume['renouveles']) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card p-3 h-100">
                <div class="small text-muted text-uppercase fw-bold">Clients à relancer</div>
                <div class="fs-4 fw-bold text-danger">{{ $nombre($resume['clients']) }}</div>
                <div class="small text-muted">{{ $nombre($resume['non_renouveles']) }} carnet(s) non renouvelé(s)</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card p-3 h-100">
                <div class="small text-muted text-uppercase fw-bold">Taux de renouvellement</div>
                <div class="fs-4 fw-bold">{{ $resume['taux'] === null ? '—' : number_format($resume['taux'], 1, ',', ' ') . ' %' }}</div>
                @if ($resume['taux'] !== null)
                    <div class="progress mt-1" style="height: 6px;"><div class="progress-bar bg-success" style="width: {{ $resume['taux'] }}%"></div></div>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Liste des clients à relancer --}}
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="fw-bold">Clients à relancer</span>
                    <span class="d-flex gap-1">
                        @foreach ($resume['tranches'] as $label => $n)
                            <span class="badge bg-label-{{ $couleursTranches[$label] }}">{{ $label }} : {{ $n }}</span>
                        @endforeach
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Client</th>
                                <th>Téléphone</th>
                                <th>Agent</th>
                                <th>Carnet retiré</th>
                                <th class="text-end">Retiré le</th>
                                <th class="text-end">Depuis</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($clients as $c)
                                <tr @if ($peutVoirClient) onclick="window.location.href='{{ route('member.details', $c->member_id) }}'" style="cursor: pointer;" @endif>
                                    <td>
                                        {{ $c->client }}<br>
                                        <span class="small text-muted">{{ $c->client_code }} · {{ $c->carnets_termines }} carnet(s) terminé(s)</span>
                                        @if ($c->autre_actif)
                                            <br><span class="badge bg-label-info">A encore un carnet actif</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        @if ($c->telephone)
                                            <a href="tel:{{ $c->telephone }}" onclick="event.stopPropagation()">{{ $c->telephone }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $c->agent }}</td>
                                    <td class="small">
                                        {{ $c->code }}<br>
                                        <span class="text-muted">{{ $c->card_type === 'simple' ? 'Simple' : 'Épargne' }} · {{ $nombre($c->subscription_amount) }} {{ $c->currency }}/jour · versé {{ $nombre($c->mises) }} {{ $c->currency }}</span>
                                    </td>
                                    <td class="text-end text-nowrap">{{ $c->retire_le->format('d/m/Y') }}</td>
                                    <td class="text-end text-nowrap">
                                        <span class="badge bg-{{ \App\Services\CardRenewalService::niveau($c->jours) }}">{{ $c->jours }} j</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Aucun client à relancer pour ces filtres.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($clients->hasPages())
                    <div class="card-footer">{{ $clients->links() }}</div>
                @endif
            </div>
        </div>

        {{-- Par agent --}}
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header fw-bold">Renouvellement par agent</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Agent</th>
                                <th class="text-end">Retirés</th>
                                <th class="text-end">À relancer</th>
                                <th class="text-end">Taux</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($agents as $a)
                                <tr>
                                    <td>
                                        @if ($a['agent_id'])
                                            <a href="{{ $lienFiltre(['agent_id' => $a['agent_id'], 'page' => null]) }}">{{ $a['agent'] }}</a>
                                        @else
                                            {{ $a['agent'] }}
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $a['retraits'] }}</td>
                                    <td class="text-end">{{ $a['a_relancer'] }}</td>
                                    <td class="text-end fw-bold text-{{ $a['taux'] >= 80 ? 'success' : ($a['taux'] >= 60 ? 'warning' : 'danger') }}">{{ number_format($a['taux'], 1, ',', ' ') }} %</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted text-center py-3">Aucun retrait sur la période.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer small text-muted">Du taux le plus faible au plus élevé. Cliquez sur un agent pour filtrer sa liste.</div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
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
