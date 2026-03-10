<div class="mt-4">
    @include('livewire.disbursement.add-disbursement-modal')
    @include('livewire.disbursement.manage-disbursement-types-modal')
    @include('livewire.disbursement.edit-disbursement-modal')

    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="row">
                <div class="col-md-8">
                    <h5 class="mb-0 text-primary fw-bold">
                        <i class="bx bx-money-withdraw me-2"></i> Mes Demandes de Décaissement
                    </h5>
                </div>
                <div class="col-md-4 d-flex justify-content-end gap-2">
                    @can('ajouter-type-decaissement')
                        <button class="btn btn-outline-primary d-flex align-items-center shadow-sm"
                            data-bs-toggle="modal" data-bs-target="#modalManageDisbursementTypes">
                            <i class="bx bx-list-ul me-1"></i> Gérer les Types
                        </button>
                    @endcan
                    <button wire:click="openModal" class="btn btn-primary d-flex align-items-center shadow-sm">
                        <i class="bx bx-plus me-1"></i> Nouvelle Demande
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4 mt-2">
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input wire:model.live.debounce.300ms="search" type="text" class="form-control"
                            placeholder="Rechercher par description...">
                    </div>
                </div>
                <div class="col-md-8 mt-2">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <select wire:model.live="filterType" class="form-select form-select-sm" style="width: auto;">
                            <option value="day">Aujourd'hui</option>
                            <option value="week">Cette semaine</option>
                            <option value="month">Ce mois</option>
                            <option value="range">Intervalle personnalisé</option>
                        </select>

                        @if ($filterType === 'range')
                            <input type="date" wire:model.live="startDate" class="form-control form-control-sm"
                                style="width: auto;">
                            <span>au</span>
                            <input type="date" wire:model.live="endDate" class="form-control form-control-sm"
                                style="width: auto;">
                        @endif
                    </div>
                </div>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th>Date</th>
                            <th>Type de dépense</th>
                            <th>Montant</th>
                            <th>Devise</th>
                            <th>Description</th>
                            @can('ajouter-type-decaissement')
                                <th>Demandeur</th>
                            @endcan
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($disbursementRequests as $request)
                            <tr>
                                <td class="text-xs text-muted">
                                    {{ $request->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <span class="badge bg-label-info">
                                        {{ $request->disbursementType->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="fw-bold">
                                    <span class="text-danger">-{{ number_format($request->amount, 2) }}</span>
                                </td>
                                <td>{{ $request->currency }}</td>
                                <td>
                                    <span class="text-wrap d-inline-block" style="max-width: 300px;">
                                        {{ $request->description }}
                                    </span>
                                </td>
                                @can('ajouter-type-decaissement')
                                    <td class="text-sm">
                                        <i class="bx bx-user-circle me-1"></i>
                                        {{ $request->user?->name . ' ' . $request->user?->postnom }}
                                    </td>
                                @endcan
                                <td>
                                    @if ($request->status === 'pending')
                                        <span class="badge bg-warning">
                                            <i class="bx bx-time-five"></i> En attente
                                        </span>
                                    @elseif($request->status === 'approved')
                                        <span class="badge bg-success">
                                            <i class="bx bx-check-circle"></i> Approuvé
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            Par {{ $request->approvedBy?->name . ' ' . $request->approvedBy?->postnom }}
                                            <br>{{ $request->approved_at?->format('d/m/Y H:i') }}
                                        </small>
                                    @elseif($request->status === 'rejected')
                                        <span class="badge bg-danger">
                                            <i class="bx bx-x-circle"></i> Rejeté
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            Par {{ $request->approvedBy?->name . ' ' . $request->approvedBy?->postnom }}
                                            <br>{{ $request->approved_at?->format('d/m/Y H:i') }}
                                        </small>
                                        @if($request->rejection_reason)
                                            <br>
                                            <small class="text-danger">
                                                <strong>Motif:</strong> {{ $request->rejection_reason }}
                                            </small>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-end">
                                        @can('ajouter-type-decaissement')
                                            @if($request->status === 'pending')
                                                <button wire:click="editRequest({{ $request->id }})"
                                                    class="btn btn-xs btn-outline-primary" title="Modifier">
                                                    <i class="bx bx-edit"></i>
                                                </button>
                                            @endif
                                        @endcan

                                        @if($request->status === 'approved' && $request->transaction_id)
                                            <button wire:click="printReceipt({{ $request->transaction_id }}, 'pos')"
                                                class="btn btn-xs btn-outline-dark" title="Reçu POS">
                                                <i class="bx bx-printer"></i> POS
                                            </button>
                                            <button wire:click="printReceipt({{ $request->transaction_id }}, 'a4')"
                                                class="btn btn-xs btn-outline-primary" title="Reçu Normal">
                                                <i class="bx bxs-file-pdf"></i> A4
                                            </button>
                                        @else
                                            @if($request->status !== 'pending' || !auth()->user()->can('ajouter-type-decaissement'))
                                                <span class="text-muted small">-</span>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bx bx-folder-open fs-1 d-block mb-2"></i>
                                        Aucune demande de décaissement enregistrée.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $disbursementRequests->links() }}
            </div>
        </div>
    </div>
</div>