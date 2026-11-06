<div>
    {{-- En-tête : période --}}
    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="mb-1"><i class="bx bx-target-lock me-1"></i> Suivi des objectifs</h4>
                <div class="text-muted">
                    {{ $periodLabel }} · du {{ $start->format('d/m/Y') }} au {{ $end->format('d/m/Y') }}
                    · temps écoulé : <strong>{{ $tempsEcoule }} %</strong>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <select wire:model.live="periodType" class="form-select form-select-sm" style="width:auto">
                    @foreach ($periodes as $key => $libelle)
                        <option value="{{ $key }}">{{ $libelle }}</option>
                    @endforeach
                </select>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary" wire:click="precedente" title="Période précédente"><i class="bx bx-chevron-left"></i></button>
                    <button class="btn btn-outline-secondary" wire:click="aujourdHui">En cours</button>
                    <button class="btn btn-outline-secondary" wire:click="suivante" title="Période suivante"><i class="bx bx-chevron-right"></i></button>
                </div>
                <input type="date" wire:model.live="reference" class="form-control form-control-sm" style="width:auto">
                @if ($canManage)
                    <a href="{{ route('objectifs.configuration', ['periodType' => $periodType, 'reference' => $reference]) }}"
                       class="btn btn-sm btn-primary"><i class="bx bx-cog me-1"></i> Fixer les objectifs</a>
                @endif
            </div>
        </div>
    </div>

    {{-- Onglets --}}
    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
            <button class="nav-link @if ($vue === 'global') active @endif" wire:click="$set('vue', 'global')">
                <i class="bx bx-buildings me-1"></i> Entreprise
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link @if ($vue === 'agents') active @endif" wire:click="$set('vue', 'agents')">
                <i class="bx bx-user-pin me-1"></i> Agents collecteurs
            </button>
        </li>
    </ul>

    <div wire:loading.delay class="text-muted small mb-2"><span class="spinner-border spinner-border-sm"></span> Calcul en cours…</div>

    @if ($vue === 'global')
        {{-- ===== Objectifs globaux ===== --}}
        @if (empty($globalGroupes))
            <div class="alert alert-info">
                Aucun objectif global n'est fixé pour cette période.
                @if ($canManage)
                    <a href="{{ route('objectifs.configuration', ['periodType' => $periodType, 'reference' => $reference]) }}">Les fixer maintenant</a>.
                @endif
            </div>
        @else
            <div class="alert alert-light border d-flex justify-content-between align-items-center">
                <span>Score global de l'entreprise (moyenne des objectifs, plafonnée à 100 % chacun)</span>
                <span class="fs-4 fw-bold">{{ $scoreGlobal }} %</span>
            </div>
            <div class="row">
                @foreach ($globalGroupes as $groupe => $lignes)
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header fw-bold">
                                <i class="bx {{ $groupesDef[$groupe]['icon'] ?? 'bx-bar-chart' }} me-1"></i>
                                {{ $groupesDef[$groupe]['label'] ?? $groupe }}
                            </div>
                            <div class="card-body">
                                @foreach ($lignes as $ligne)
                                    @include('livewire.objectifs.partials.ligne-progression', ['ligne' => $ligne])
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        {{-- ===== Agents ===== --}}
        @if ($agentDetail)
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <button class="btn btn-sm btn-outline-secondary me-2" wire:click="voirAgent(null)"><i class="bx bx-arrow-back"></i> Classement</button>
                        <span class="fw-bold">{{ $agentDetail['info']['nom'] }}</span>
                    </div>
                    <div>
                        <span class="badge bg-label-success">{{ $agentDetail['info']['ok'] }} dans les temps</span>
                        <span class="badge bg-label-warning">{{ $agentDetail['info']['vigilance'] }} vigilance</span>
                        <span class="badge bg-label-danger">{{ $agentDetail['info']['retard'] }} en retard</span>
                        <span class="badge bg-primary ms-1">Score {{ $agentDetail['info']['score'] }} %</span>
                    </div>
                </div>
            </div>
            <div class="row">
                @foreach ($agentDetail['groupes'] as $groupe => $lignes)
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header fw-bold">
                                <i class="bx {{ $groupesDef[$groupe]['icon'] ?? 'bx-bar-chart' }} me-1"></i>
                                {{ $groupesDef[$groupe]['label'] ?? $groupe }}
                            </div>
                            <div class="card-body">
                                @foreach ($lignes as $ligne)
                                    @include('livewire.objectifs.partials.ligne-progression', ['ligne' => $ligne])
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif ($classement->isEmpty())
            <div class="alert alert-info">Aucun objectif par agent n'est fixé pour cette période.</div>
        @else
            <div class="card">
                <div class="card-header fw-bold">Classement des agents</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Agent</th>
                                <th class="text-center">Objectifs</th>
                                <th class="text-center">Atteints</th>
                                <th class="text-center">Dans les temps</th>
                                <th class="text-center">Vigilance</th>
                                <th class="text-center">En retard</th>
                                <th style="min-width:180px">Score</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classement as $i => $row)
                                @php
                                    // Couleur comparée au temps écoulé : à mi-mois, 50 % est dans les temps
                                    $repere = max(1, $tempsEcoule);
                                    $couleur = $row['score'] >= $repere ? 'success' : ($row['score'] >= $repere * config('objectifs.seuils.vigilance') / 100 ? 'warning' : 'danger');
                                @endphp
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td class="fw-semibold">{{ $row['nom'] }}</td>
                                    <td class="text-center">{{ $row['total'] }}</td>
                                    <td class="text-center">{{ $row['atteints'] }}</td>
                                    <td class="text-center text-success">{{ $row['ok'] }}</td>
                                    <td class="text-center text-warning">{{ $row['vigilance'] }}</td>
                                    <td class="text-center text-danger">{{ $row['retard'] }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height:8px">
                                                <div class="progress-bar bg-{{ $couleur }}" style="width: {{ min(100, $row['score']) }}%"></div>
                                            </div>
                                            <small class="fw-bold">{{ $row['score'] }} %</small>
                                        </div>
                                    </td>
                                    <td><button class="btn btn-sm btn-outline-primary" wire:click="voirAgent({{ $row['id'] }})">Détail</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="text-muted small mt-2">
                Score = moyenne des objectifs de l'agent, chacun plafonné à 100 %. Le repère noir sur les barres indique où l'agent devrait être à date.
            </p>
        @endif
    @endif
</div>
