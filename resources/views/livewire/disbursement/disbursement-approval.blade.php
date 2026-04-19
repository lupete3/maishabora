<div class="mt-4">
    {{-- Modal de rejet --}}
    <div wire:ignore.self class="modal fade" id="modalRejectDisbursement" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="bx bx-x-circle"></i> Rejeter la demande
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Motif du rejet <span class="text-danger">*</span></label>
                        <textarea wire:model="rejectionReason" class="form-control" rows="4"
                            placeholder="Expliquez pourquoi cette demande est rejetée..."></textarea>
                        @error('rejectionReason')
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" wire:click="reject" class="btn btn-danger">
                        <i class="bx bx-x-circle"></i> Rejeter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="row">
                <div class="col-md-8">
                    <h5 class="mb-0 text-primary fw-bold">
                        <i class="bx bx-check-shield me-2"></i> Approbation des Décaissements
                    </h5>
                    <small class="text-muted">Demandes en attente de validation</small>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4 mt-2">
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input wire:model.live.debounce.300ms="search" type="text" class="form-control"
                            placeholder="Rechercher...">
                    </div>
                </div>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th>Date</th>
                            <th>Demandeur</th>
                            <th>Type</th>
                            <th>Montant</th>
                            <th>Devise</th>
                            <th>Description</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($pendingRequests as $request)
                            <tr>
                                <td class="text-xs text-muted">
                                    {{ $request->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="bx bx-user-circle me-2 fs-5"></i>
                                        <div>
                                            <strong>{{ $request->user->name ?? 'N/A' }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $request->user->role ?? '' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-label-info">
                                        {{ $request->disbursementType->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="fw-bold">
                                    <span class="text-danger fs-6">{{ number_format($request->amount, 2) }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-label-secondary">{{ $request->currency }}</span>
                                </td>
                                <td>
                                    <span class="text-wrap d-inline-block" style="max-width: 350px;">
                                        {{ $request->description }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-end">
                                        <button wire:click="approve({{ $request->id }})" class="btn btn-sm btn-success"
                                            onclick="return confirm('Êtes-vous sûr de vouloir approuver cette demande ?')">
                                            <i class="bx bx-check-circle"></i> Approuver
                                        </button>
                                        <button wire:click="openRejectModal({{ $request->id }})"
                                            class="btn btn-sm btn-danger">
                                            <i class="bx bx-x-circle"></i> Rejeter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bx bx-check-shield fs-1 d-block mb-2 text-success"></i>
                                        <strong>Aucune demande en attente</strong>
                                        <br>
                                        <small>Toutes les demandes ont été traitées.</small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $pendingRequests->links() }}
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        window.addEventListener('openModal', event => {
            const modalId = event.detail.name || event.detail[0].name;
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            modal.show();
        });

        window.addEventListener('closeModal', event => {
            const modalId = event.detail.name || event.detail[0].name;
            const modalElement = document.getElementById(modalId);
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
        });
    </script>
@endpush
