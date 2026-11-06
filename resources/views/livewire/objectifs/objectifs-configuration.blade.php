<div>
    {{-- En-tête : période --}}
    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h4 class="mb-1"><i class="bx bx-cog me-1"></i> Configuration des objectifs</h4>
                <div class="text-muted">
                    {{ $periodLabel }} · du {{ $start->format('d/m/Y') }} au {{ $end->format('d/m/Y') }}
                    · <span class="badge bg-label-primary">{{ $nbGlobal }} objectif(s) global(aux)</span>
                    <span class="badge bg-label-info">{{ $nbAgents }} objectif(s) agents</span>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <select wire:model.live="periodType" class="form-select form-select-sm" style="width:auto">
                    @foreach ($periodes as $key => $libelle)
                        <option value="{{ $key }}">{{ $libelle }}</option>
                    @endforeach
                </select>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary" wire:click="precedente"><i class="bx bx-chevron-left"></i></button>
                    <button class="btn btn-outline-secondary" wire:click="suivante"><i class="bx bx-chevron-right"></i></button>
                </div>
                <input type="date" wire:model.live="reference" class="form-control form-control-sm" style="width:auto">
                <a href="{{ route('objectifs.suivi', ['periodType' => $periodType, 'reference' => $reference]) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bx bx-line-chart me-1"></i> Voir le suivi
                </a>
            </div>
        </div>
    </div>

    <ul class="nav nav-pills mb-3">
        <li class="nav-item"><button class="nav-link @if ($onglet === 'saisie') active @endif" wire:click="$set('onglet', 'saisie')"><i class="bx bx-edit me-1"></i> Saisie des objectifs</button></li>
        <li class="nav-item"><button class="nav-link @if ($onglet === 'outils') active @endif" wire:click="$set('onglet', 'outils')"><i class="bx bx-git-branch me-1"></i> Répartition & reprise</button></li>
        <li class="nav-item"><button class="nav-link @if ($onglet === 'indicateurs') active @endif" wire:click="$set('onglet', 'indicateurs')"><i class="bx bx-slider-alt me-1"></i> Indicateurs</button></li>
    </ul>

    <div wire:loading.delay class="text-muted small mb-2"><span class="spinner-border spinner-border-sm"></span> Chargement…</div>

    {{-- ================= SAISIE ================= --}}
    @if ($onglet === 'saisie')
        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <label class="fw-semibold mb-0">Objectifs de :</label>
                <select wire:model.live="cible" class="form-select" style="max-width: 360px">
                    <option value="global">🏢 Entreprise (objectifs globaux)</option>
                    <optgroup label="Agents collecteurs">
                        @foreach ($agents as $agent)
                            <option value="{{ $agent->id }}">{{ trim($agent->name . ' ' . $agent->postnom . ' ' . $agent->prenom) }}</option>
                        @endforeach
                    </optgroup>
                </select>
                <span class="text-muted small">
                    Laissez une case vide pour ne pas fixer d'objectif (la vider supprime l'objectif existant).
                    La colonne « Réalisé {{ $prevLabel }} » sert de repère.
                </span>
            </div>
        </div>

        <form wire:submit.prevent="enregistrer">
            @foreach ($groupes as $groupe => $items)
                <div class="card mb-3">
                    <div class="card-header fw-bold">
                        <i class="bx {{ $groupesDef[$groupe]['icon'] ?? 'bx-bar-chart' }} me-1"></i> {{ $groupesDef[$groupe]['label'] ?? $groupe }}
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:40%">Indicateur</th>
                                    <th style="width:10%">Devise</th>
                                    <th style="width:25%">Objectif</th>
                                    <th style="width:25%">Réalisé {{ $prevLabel }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $key => $def)
                                    @php $lignesDevise = $def['unit'] === 'amount' ? $devises : [$sansDevise]; @endphp
                                    @foreach ($lignesDevise as $devise)
                                        <tr wire:key="t-{{ $cible }}-{{ $key }}-{{ $devise }}">
                                            @if ($loop->first)
                                                <td rowspan="{{ count($lignesDevise) }}">
                                                    <div class="fw-semibold">
                                                        {{ $def['label'] }}
                                                        @if ($def['direction'] === 'down') <span class="badge bg-label-warning">plafond</span> @endif
                                                        @if ($def['nature'] === 'stock') <span class="badge bg-label-secondary">à date</span> @endif
                                                    </div>
                                                    <small class="text-muted">{{ $def['description'] }}</small>
                                                </td>
                                            @endif
                                            <td>{{ $devise === $sansDevise ? ($def['unit'] === 'percent' ? '%' : '—') : $devise }}</td>
                                            <td>
                                                <input type="number" step="{{ $def['unit'] === 'count' ? '1' : '0.01' }}" min="0"
                                                       wire:model="targets.{{ $key }}.{{ $devise }}"
                                                       class="form-control form-control-sm @error('targets.' . $key . '.' . $devise) is-invalid @enderror"
                                                       placeholder="Non fixé">
                                                @error('targets.' . $key . '.' . $devise) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </td>
                                            <td class="text-muted">
                                                {{ \App\Services\ObjectifProgressService::formater((float) ($reperes[$key][$devise] ?? 0), $def, $devise === $sansDevise ? null : $devise) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            @if (empty($groupes))
                <div class="alert alert-warning">Aucun indicateur actif pour cette cible. Activez-en dans l'onglet « Indicateurs ».</div>
            @else
                <div class="d-flex justify-content-end mb-4">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <i class="bx bx-save me-1"></i> Enregistrer les objectifs
                    </button>
                </div>
            @endif
        </form>
    @endif

    {{-- ================= OUTILS ================= --}}
    @if ($onglet === 'outils')
        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="card h-100">
                    <div class="card-header fw-bold"><i class="bx bx-git-branch me-1"></i> Attribuer un objectif à plusieurs agents</div>
                    <div class="card-body">
                        <p class="text-muted small">
                            Les agents n'ayant pas tous le même volume de clients, vous pouvez répartir un objectif total
                            au prorata de leur portefeuille ou de leurs résultats de la période précédente.
                            Les objectifs existants de ces agents pour cet indicateur sont remplacés.
                        </p>
                        <form wire:submit.prevent="appliquerEnMasse">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Indicateur</label>
                                    <select wire:model.live="bulkIndicator" class="form-select @error('bulkIndicator') is-invalid @enderror">
                                        <option value="">— Choisir —</option>
                                        @foreach ($agentIndicateurs as $key => $def)
                                            <option value="{{ $key }}">{{ ($groupesDef[$def['group']]['label'] ?? '') . ' · ' . $def['label'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('bulkIndicator') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    @if ($bulkIndicator && ($agentIndicateurs[$bulkIndicator]['unit'] ?? null) === 'amount')
                                        <label class="form-label">Devise</label>
                                        <select wire:model="bulkCurrency" class="form-select">
                                            @foreach ($devises as $d) <option value="{{ $d }}">{{ $d }}</option> @endforeach
                                        </select>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mode</label>
                                    <select wire:model.live="bulkMode" class="form-select">
                                        <option value="identique">Même objectif pour chaque agent</option>
                                        <option value="portefeuille">Répartir un total selon le nombre de clients</option>
                                        <option value="historique">Répartir un total selon le réalisé de {{ $prevLabel }}</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ $bulkMode === 'identique' ? 'Objectif par agent' : 'Objectif total à répartir' }}</label>
                                    <input type="number" step="0.01" min="0" wire:model="bulkValue" class="form-control @error('bulkValue') is-invalid @enderror">
                                    @error('bulkValue') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label mb-0">Agents</label>
                                        <button type="button" class="btn btn-sm btn-link" wire:click="toggleBulkAgents">Tout cocher / décocher</button>
                                    </div>
                                    <div class="border rounded p-2 @error('bulkAgents') border-danger @enderror" style="max-height: 220px; overflow:auto">
                                        @foreach ($agents as $agent)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="{{ $agent->id }}" id="ba{{ $agent->id }}" wire:model="bulkAgents">
                                                <label class="form-check-label" for="ba{{ $agent->id }}">{{ trim($agent->name . ' ' . $agent->postnom . ' ' . $agent->prenom) }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('bulkAgents') <div class="text-danger small">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3" wire:loading.attr="disabled"><i class="bx bx-check me-1"></i> Appliquer</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 mb-4">
                <div class="card h-100">
                    <div class="card-header fw-bold"><i class="bx bx-copy me-1"></i> Reprendre la période précédente</div>
                    <div class="card-body">
                        <p class="text-muted small">
                            Recopie tous les objectifs de {{ $prevLabel }} (entreprise et agents) sur {{ $periodLabel }},
                            avec une évolution en % si besoin (ex : 10 pour +10 %).
                        </p>
                        <form wire:submit.prevent="reprendrePeriodePrecedente">
                            <label class="form-label">Évolution (%)</label>
                            <input type="number" step="0.1" wire:model="copyCoefficient" class="form-control mb-2 @error('copyCoefficient') is-invalid @enderror">
                            @error('copyCoefficient') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="copyOverwrite" wire:model="copyOverwrite">
                                <label class="form-check-label" for="copyOverwrite">Remplacer les objectifs déjà fixés sur {{ $periodLabel }}</label>
                            </div>
                            <button type="submit" class="btn btn-outline-primary" wire:loading.attr="disabled"
                                    wire:confirm="Reprendre les objectifs de {{ $prevLabel }} sur {{ $periodLabel }} ?">
                                <i class="bx bx-copy me-1"></i> Reprendre
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ================= INDICATEURS ================= --}}
    @if ($onglet === 'indicateurs')
        <div class="card">
            <div class="card-header fw-bold"><i class="bx bx-slider-alt me-1"></i> Catalogue des indicateurs</div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Désactivez les indicateurs que vous ne suivez pas : ils disparaissent de la saisie (les objectifs déjà fixés restent visibles dans le suivi).
                    L'ordre définit l'affichage à l'intérieur de chaque groupe.
                </p>
                <form wire:submit.prevent="enregistrerIndicateurs">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Actif</th>
                                    <th>Groupe</th>
                                    <th>Indicateur</th>
                                    <th>Unité</th>
                                    <th>Portée</th>
                                    <th>Type</th>
                                    <th style="width:90px">Ordre</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($catalogueComplet as $key => $def)
                                    <tr wire:key="ind-{{ $key }}">
                                        <td><div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" wire:model="indicateurActif.{{ $key }}"></div></td>
                                        <td>{{ $groupesDef[$def['group']]['label'] ?? $def['group'] }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $def['label'] }}</div>
                                            <small class="text-muted">{{ $def['description'] }}</small>
                                        </td>
                                        <td>{{ ['count' => 'Nombre', 'amount' => 'Montant', 'percent' => '%'][$def['unit']] }}</td>
                                        <td>
                                            @foreach ($def['scopes'] as $s)
                                                <span class="badge bg-label-{{ $s === 'agent' ? 'info' : 'primary' }}">{{ $s === 'agent' ? 'Agent' : 'Entreprise' }}</span>
                                            @endforeach
                                        </td>
                                        <td>
                                            {{ $def['nature'] === 'flux' ? 'Cumul période' : 'Situation à date' }}
                                            @if ($def['direction'] === 'down') <span class="badge bg-label-warning">plafond</span> @endif
                                        </td>
                                        <td><input type="number" min="0" class="form-control form-control-sm" wire:model="indicateurOrdre.{{ $key }}"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2"><i class="bx bx-save me-1"></i> Enregistrer le paramétrage</button>
                </form>
            </div>
        </div>
    @endif
</div>
