<div>
    {{-- Summary Cards --}}
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-start h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-3 bg-label-success rounded">
                            <i class="bx bx-trending-up fs-4"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block">Surplus USD ouvert</small>
                            <h5 class="mb-0 text-success">{{ number_format($surplusUsd, 2) }} $</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-start h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-3 bg-label-success rounded">
                            <i class="bx bx-trending-up fs-4"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block">Surplus CDF ouvert</small>
                            <h5 class="mb-0 text-success">{{ number_format($surplusCdf, 2) }} Fc</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-start h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-3 bg-label-danger rounded">
                            <i class="bx bx-trending-down fs-4"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block">Déficit USD ouvert</small>
                            <h5 class="mb-0 text-danger">{{ number_format($deficitUsd, 2) }} $</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="card border-start h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-3 bg-label-danger rounded">
                            <i class="bx bx-trending-down fs-4"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block">Déficit CDF ouvert</small>
                            <h5 class="mb-0 text-danger">{{ number_format($deficitCdf, 2) }} Fc</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="bx bx-filter-alt me-2"></i>Filtres</h5>
            <button wire:click="resetFilters" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-reset me-1"></i>Réinitialiser
            </button>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @if(in_array(auth()->user()->role, ['admin', 'comptable', 'caissier']))
                    <div class="col-md-2">
                        <label class="form-label">Agent</label>
                        <select wire:model.live="filterAgent" class="form-select form-select-sm">
                            <option value="">Tous</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->name }} {{ $agent->postnom }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label">Statut</label>
                    <select wire:model.live="filterStatus" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="ouvert">Ouvert</option>
                        <option value="en_cours">En cours</option>
                        <option value="cloture">Clôturé</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Devise</label>
                    <select wire:model.live="filterCurrency" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        <option value="USD">USD</option>
                        <option value="CDF">CDF</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select wire:model.live="filterType" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="surplus">Surplus</option>
                        <option value="deficit">Déficit</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date début</label>
                    <input type="date" wire:model.live="filterDateFrom" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date fin</label>
                    <input type="date" wire:model.live="filterDateTo" class="form-control form-control-sm">
                </div>
            </div>
        </div>
    </div>

    {{-- Main Table --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bx bx-error-circle me-2"></i>Écarts de Caisse
                @if($totalOuvert > 0)
                    <span class="badge bg-danger ms-2">{{ $totalOuvert }} ouvert(s)</span>
                @endif
            </h5>
            <a href="{{ route('ecarts.export', [
                'filterAgent' => $filterAgent,
                'filterStatus' => $filterStatus,
                'filterCurrency' => $filterCurrency,
                'filterType' => $filterType,
                'filterDateFrom' => $filterDateFrom,
                'filterDateTo' => $filterDateTo,
            ]) }}" class="btn btn-danger">
                <i class="bx bxs-file-pdf me-1"></i>Exporter PDF
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Agent</th>
                            <th>Date Clôture</th>
                            <th>Type</th>
                            <th>Devise</th>
                            <th>Montant</th>
                            <th>Description</th>
                            <th>Statut</th>
                            <th>Résolu par</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ecarts as $ecart)
                            <tr>
                                <td>{{ $ecart->id }}</td>
                                <td>{{ $ecart->user->name ?? '-' }} {{ $ecart->user->postnom ?? '-' }}</td>
                                <td>{{ $ecart->cloture ? \Carbon\Carbon::parse($ecart->cloture->closing_date)->format('d/m/Y') : '-' }}
                                </td>
                                <td>
                                    @if($ecart->type === 'surplus')
                                        <span class="badge bg-success"><i class="bx bx-trending-up me-1"></i>Surplus</span>
                                    @else
                                        <span class="badge bg-danger"><i class="bx bx-trending-down me-1"></i>Déficit</span>
                                    @endif
                                </td>
                                <td>{{ $ecart->currency }}</td>
                                <td class="fw-bold">
                                    {{ number_format($ecart->amount, 2) }}
                                    {{ $ecart->currency === 'USD' ? '$' : 'Fc' }}
                                </td>
                                <td>
                                    <small>{{ Str::limit($ecart->description ?? '-', 40) }}</small>
                                </td>
                                <td>
                                    @if($ecart->status === 'ouvert')
                                        <span class="badge bg-warning text-dark"><i
                                                class="bx bx-time-five me-1"></i>Ouvert</span>
                                    @elseif($ecart->status === 'en_cours')
                                        <span class="badge bg-info"><i class="bx bx-loader-alt me-1"></i>En cours</span>
                                    @else
                                        <span class="badge bg-success"><i class="bx bx-check-circle me-1"></i>Clôturé</span>
                                    @endif
                                </td>
                                <td>
                                    @if($ecart->resolvedBy)
                                        <small>{{ $ecart->resolvedBy->name }}<br>
                                            {{ $ecart->resolved_at?->format('d/m/Y H:i') }}</small>
                                    @else
                                        <small class="text-muted">-</small>
                                    @endif
                                </td>
                                <td>
                                    @if(in_array(auth()->user()->role, ['admin', 'comptable', 'caissier']))
                                        @if($ecart->status === 'ouvert' || $ecart->status === 'en_cours')
                                            <button wire:click="openResolutionModal({{ $ecart->id }})"
                                                class="btn btn-sm btn-outline-primary" title="Résoudre">
                                                <span wire:loading wire:target="openResolutionModal({{ $ecart->id }})"
                                                    class="spinner-border spinner-border-sm me-1"></span>
                                                <i class="bx bx-check-shield"></i>
                                            </button>
                                        @endif
                                        @if($ecart->status === 'cloture')
                                            <button wire:click="reopenEcart({{ $ecart->id }})"
                                                class="btn btn-sm btn-outline-warning" title="Réouvrir">
                                                <span wire:loading wire:target="reopenEcart({{ $ecart->id }})"
                                                    class="spinner-border spinner-border-sm me-1"></span>
                                                <i class="bx bx-undo"></i>
                                            </button>
                                        @endif
                                    @endif

                                    {{-- Show resolution note in a popover --}}
                                    @if($ecart->resolution_note)
                                        <button type="button" class="btn btn-sm btn-outline-info" title="Voir la note"
                                            data-bs-toggle="popover" data-bs-trigger="hover focus"
                                            data-bs-content="{{ $ecart->resolution_note }}">
                                            <i class="bx bx-note"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="bx bx-check-circle fs-3 d-block mb-2"></i>
                                    Aucun écart trouvé.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $ecarts->links() }}
        </div>
    </div>

    {{-- Resolution Modal --}}
    @if($showResolutionModal)
        <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bx bx-check-shield me-2"></i>Résoudre l'écart</h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeResolutionModal"></button>
                    </div>
                    <div class="modal-body">
                        @php $selectedEcart = \App\Models\EcartCaisse::with(['user', 'cloture'])->find($selectedEcartId); @endphp
                        @if($selectedEcart)
                            <div class="alert alert-{{ $selectedEcart->type === 'surplus' ? 'success' : 'danger' }} mb-3">
                                <strong>{{ $selectedEcart->type === 'surplus' ? 'Surplus' : 'Déficit' }}</strong> de
                                <strong>{{ number_format((float) $selectedEcart->amount, 2) }}
                                    {{ $selectedEcart->currency === 'USD' ? '$' : 'Fc' }}</strong>
                                — Agent : <strong>{{ $selectedEcart->user->name ?? '-' }}</strong>
                                — Clôture du :
                                <strong>{{ $selectedEcart->cloture ? $selectedEcart->cloture->closing_date->format('d/m/Y') : '-' }}</strong>
                            </div>
                            @if($selectedEcart->description)
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Description initiale :</label>
                                    <p class="text-muted">{{ $selectedEcart->description }}</p>
                                </div>
                            @endif
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-bold">Nouveau statut</label>
                            <select wire:model="resolutionStatus" class="form-select">
                                <option value="en_cours">En cours (investigation)</option>
                                <option value="cloture">Clôturé (résolu)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Justification / Note de résolution <span
                                    class="text-danger">*</span></label>
                            <textarea wire:model="resolutionNote" class="form-control" rows="4"
                                placeholder="Ex: L'agent avait oublié d'enregistrer un dépôt de 10$ pour le client X. Régularisation effectuée."></textarea>
                            @error('resolutionNote')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button wire:click="closeResolutionModal" class="btn btn-secondary">Annuler</button>
                        <button wire:click="resolveEcart" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading wire:target="resolveEcart"
                                class="spinner-border spinner-border-sm me-1"></span>
                            Confirmer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>