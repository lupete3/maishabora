<!-- resources/views/livewire/agent/agent-performance.blade.php -->
<div>
    <button type="button" class="btn btn-primary" wire:click="open" wire:loading.attr="disabled" wire:target="open">
        <i class="bx bx-bar-chart-alt-2 me-1"></i> Performance agents
    </button>

    @if ($showModal)
        <div class="modal fade show d-block" tabindex="-1" role="dialog" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bx bx-trophy me-1"></i> Performance des agents
                        </h5>
                        <button type="button" class="btn-close" wire:click="close" aria-label="Fermer"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-4">
                            {{-- 1. AGENTS --}}
                            <div class="col-lg-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="m-0">1. Agents à comparer</h6>
                                    <button type="button" class="btn btn-xs btn-outline-primary" wire:click="toggleAllAgents">
                                        Tout / Aucun
                                    </button>
                                </div>
                                <div class="border rounded p-2" style="max-height: 260px; overflow-y: auto;">
                                    @forelse ($agents as $agent)
                                        <div class="form-check mb-1">
                                            <input class="form-check-input" type="checkbox"
                                                id="perf-agent-{{ $agent->id }}"
                                                value="{{ $agent->id }}"
                                                wire:model="selectedAgents">
                                            <label class="form-check-label" for="perf-agent-{{ $agent->id }}">
                                                ({{ $agent->id }}) {{ $agent->name }} {{ $agent->postnom }}
                                            </label>
                                        </div>
                                    @empty
                                        <small class="text-muted">Aucun agent disponible.</small>
                                    @endforelse
                                </div>
                                @error('selectedAgents') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            {{-- 2. TYPES DE TRANSACTION --}}
                            <div class="col-lg-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="m-0">2. Types de transaction</h6>
                                    <button type="button" class="btn btn-xs btn-outline-primary" wire:click="toggleAllTypes">
                                        Tout / Aucun
                                    </button>
                                </div>
                                <div class="border rounded p-2">
                                    @foreach ($types as $key => $label)
                                        <div class="form-check mb-1">
                                            <input class="form-check-input" type="checkbox"
                                                id="perf-type-{{ $key }}"
                                                value="{{ $key }}"
                                                wire:model="selectedTypes">
                                            <label class="form-check-label" for="perf-type-{{ $key }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                @error('selectedTypes') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            {{-- 3. PÉRIODE & CLASSEMENT --}}
                            <div class="col-lg-4">
                                <h6 class="mb-2">3. Période et classement</h6>

                                <label class="form-label mb-1">Période</label>
                                <select class="form-select mb-2" wire:model.live="filter">
                                    @foreach ($filters as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>

                                @if ($filter === 'custom')
                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label mb-1">Début</label>
                                            <input type="date" class="form-control" wire:model="startDate">
                                            @error('startDate') <small class="text-danger">{{ $message }}</small> @enderror
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label mb-1">Fin</label>
                                            <input type="date" class="form-control" wire:model="endDate">
                                            @error('endDate') <small class="text-danger">{{ $message }}</small> @enderror
                                        </div>
                                    </div>
                                @endif

                                <label class="form-label mb-1">Classer par</label>
                                <select class="form-select mb-3" wire:model.live="sortBy">
                                    @foreach ($criteria as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>

                                <button type="button" class="btn btn-primary w-100" wire:click="evaluate"
                                    wire:loading.attr="disabled" wire:target="evaluate">
                                    <span wire:loading wire:target="evaluate" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                    <i class="bx bx-line-chart me-1"></i> Évaluer
                                </button>
                            </div>
                        </div>

                        {{-- RÉSULTATS --}}
                        @if ($hasResults)
                            <hr class="my-4">

                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                                <div>
                                    <h6 class="m-0">Classement — {{ $criteria[$sortBy] }}</h6>
                                    <small class="text-muted">
                                        {{ $periodLabel }} ·
                                        {{ collect($selectedTypes)->map(fn ($t) => $types[$t] ?? $t)->join(', ') }}
                                    </small>
                                </div>
                                <div class="d-flex gap-2">
                                    <span class="badge bg-label-primary p-2">Opérations : {{ number_format($totals['count'], 0, ',', ' ') }}</span>
                                    <span class="badge bg-label-success p-2">CDF : {{ number_format($totals['cdf'], 2, ',', ' ') }}</span>
                                    <span class="badge bg-label-info p-2">USD : {{ number_format($totals['usd'], 2, ',', ' ') }}</span>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 70px;">Rang</th>
                                            <th>Agent</th>
                                            <th class="text-end">Opérations</th>
                                            <th class="text-end">Montant CDF</th>
                                            <th class="text-end">Montant USD</th>
                                            <th>Détail par type</th>
                                            <th style="min-width: 140px;">Part ({{ $criteria[$sortBy] }})</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($results as $row)
                                            @php
                                                $rankClass = match ($row['rank']) {
                                                    1 => 'bg-warning',
                                                    2 => 'bg-secondary',
                                                    3 => 'bg-info',
                                                    default => 'bg-label-secondary',
                                                };
                                            @endphp
                                            <tr wire:key="perf-row-{{ $row['id'] }}">
                                                <td><span class="badge {{ $rankClass }}">#{{ $row['rank'] }}</span></td>
                                                <td>
                                                    <strong>{{ $row['name'] }}</strong>
                                                    <br><small class="text-muted">ID {{ $row['id'] }}</small>
                                                </td>
                                                <td class="text-end {{ $sortBy === 'count' ? 'fw-bold' : '' }}">
                                                    {{ number_format($row['count'], 0, ',', ' ') }}
                                                </td>
                                                <td class="text-end {{ $sortBy === 'cdf' ? 'fw-bold' : '' }}">
                                                    {{ number_format($row['cdf'], 2, ',', ' ') }}
                                                </td>
                                                <td class="text-end {{ $sortBy === 'usd' ? 'fw-bold' : '' }}">
                                                    {{ number_format($row['usd'], 2, ',', ' ') }}
                                                </td>
                                                <td>
                                                    @forelse ($row['by_type'] as $type => $nb)
                                                        <span class="badge bg-label-dark me-1 mb-1">{{ $types[$type] ?? $type }} : {{ $nb }}</span>
                                                    @empty
                                                        <small class="text-muted">Aucune opération</small>
                                                    @endforelse
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="progress flex-grow-1" style="height: 8px;">
                                                            <div class="progress-bar" role="progressbar"
                                                                style="width: {{ $row['share'] }}%"></div>
                                                        </div>
                                                        <small>{{ $row['share'] }}%</small>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" wire:click="close">Fermer</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
