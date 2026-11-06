<!-- resources/views/livewire/agent/agent-performance.blade.php -->
<div>
    <button type="button" class="btn btn-primary" wire:click="open" wire:loading.attr="disabled" wire:target="open">
        <i class="bx bx-bar-chart-alt-2 me-1"></i> Performance agents
    </button>

    @if ($showModal)
        @php
            $fmt = fn ($n, $d = 2) => number_format($n, $d, ',', ' ');
        @endphp

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
                            <div class="col-lg-4" x-data="{ q: '' }">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="m-0">1. Agents
                                        <small class="text-muted fw-normal">
                                            (<span x-text="$wire.selectedAgents.length"></span> sélectionné(s))
                                        </small>
                                    </h6>
                                    <button type="button" class="btn btn-xs btn-outline-primary" wire:click="toggleAllAgents">
                                        Tout / Aucun
                                    </button>
                                </div>
                                <input type="search" class="form-control form-control-sm mb-2" placeholder="Rechercher un agent…" x-model="q">
                                <div class="border rounded p-2" style="max-height: 220px; overflow-y: auto;">
                                    @forelse ($agents as $agent)
                                        <div class="form-check mb-1" wire:key="perf-agent-{{ $agent->id }}"
                                            data-label="{{ Str::lower($agent->id . ' ' . $agent->name . ' ' . $agent->postnom) }}"
                                            x-show="!q || $el.dataset.label.includes(q.toLowerCase())">
                                            <input class="form-check-input" type="checkbox"
                                                id="perf-agent-{{ $agent->id }}"
                                                value="{{ $agent->id }}"
                                                wire:model="selectedAgents">
                                            <label class="form-check-label" for="perf-agent-{{ $agent->id }}">
                                                {{ $agent->name }} {{ $agent->postnom }}
                                                <small class="text-muted">#{{ $agent->id }}</small>
                                            </label>
                                        </div>
                                    @empty
                                        <small class="text-muted">Aucun agent disponible.</small>
                                    @endforelse
                                </div>
                                @error('selectedAgents') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            {{-- 2. TYPES --}}
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
                                                id="perf-type-{{ $key }}" value="{{ $key }}" wire:model="selectedTypes">
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

                            <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
                                <div>
                                    <h6 class="m-0">Classement par {{ Str::lower($criteria[$sortBy]) }}</h6>
                                    <small class="text-muted">
                                        {{ $periodLabel }} ·
                                        {{ collect($evaluatedTypes)->map(fn ($t) => $types[$t] ?? $t)->join(', ') }}
                                    </small>
                                </div>
                                <button type="button" class="btn btn-sm btn-danger" wire:click="exportPDF"
                                    wire:loading.attr="disabled" wire:target="exportPDF">
                                    <span wire:loading wire:target="exportPDF" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                    <i class="bx bx-file me-1"></i> Exporter PDF
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" style="width: 60px;">Rang</th>
                                            <th>Agent</th>
                                            <th class="text-end">Opérations</th>
                                            <th class="text-end">CDF</th>
                                            <th class="text-end">USD</th>
                                            <th>Opérations par type</th>
                                            <th style="min-width: 150px;">Part du total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($results as $row)
                                            @php
                                                $rankStyle = match ($row['rank']) {
                                                    1 => 'background:#f1c206;color:#000;',
                                                    2 => 'background:#c0c0c0;color:#000;',
                                                    3 => 'background:#cd7f32;color:#fff;',
                                                    default => 'background:#eceef1;color:#566a7f;',
                                                };
                                            @endphp
                                            <tr wire:key="perf-row-{{ $row['id'] }}">
                                                <td class="text-center"><span class="badge" style="{{ $rankStyle }}">{{ $row['rank'] }}</span></td>
                                                <td>
                                                    <span class="fw-semibold">{{ $row['name'] }}</span>
                                                    <small class="text-muted">#{{ $row['id'] }}</small>
                                                </td>
                                                <td class="text-end text-nowrap {{ $sortBy === 'count' ? 'fw-bold' : '' }}">{{ $fmt($row['count'], 0) }}</td>
                                                <td class="text-end text-nowrap {{ $sortBy === 'cdf' ? 'fw-bold' : '' }}">{{ $fmt($row['cdf']) }}</td>
                                                <td class="text-end text-nowrap {{ $sortBy === 'usd' ? 'fw-bold' : '' }}">{{ $fmt($row['usd']) }}</td>
                                                <td>
                                                    @foreach ($row['by_type'] as $type => $v)
                                                        <span class="badge {{ $v['count'] ? 'bg-label-dark' : 'bg-label-secondary opacity-50' }} me-1 mb-1">
                                                            {{ $types[$type] ?? $type }} : {{ $fmt($v['count'], 0) }}
                                                        </span>
                                                    @endforeach
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="progress flex-grow-1" style="height: 8px;">
                                                            <div class="progress-bar" role="progressbar" style="width: {{ $row['share'] }}%"></div>
                                                        </div>
                                                        <small class="text-nowrap">{{ $fmt($row['share'], 1) }} %</small>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-light fw-bold">
                                        <tr>
                                            <td colspan="2">TOTAL</td>
                                            <td class="text-end text-nowrap">{{ $fmt($totals['count'], 0) }}</td>
                                            <td class="text-end text-nowrap">{{ $fmt($totals['cdf']) }}</td>
                                            <td class="text-end text-nowrap">{{ $fmt($totals['usd']) }}</td>
                                            <td>
                                                @foreach ($typeSummary as $type => $v)
                                                    <span class="badge bg-label-primary me-1 mb-1">{{ $types[$type] ?? $type }} : {{ $fmt($v['count'], 0) }}</span>
                                                @endforeach
                                            </td>
                                            <td>100 %</td>
                                        </tr>
                                    </tfoot>
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
