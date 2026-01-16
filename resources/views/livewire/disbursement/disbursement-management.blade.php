<div class="mt-4">
    @include('livewire.disbursement.add-disbursement-modal')
    @include('livewire.disbursement.add-disbursement-type-modal')

    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="row">
                <div class="col-md-8">
                    <h5 class="mb-0 text-primary fw-bold">
                        <i class="bx bx-money-withdraw me-2"></i> Historique des Décaissements
                    </h5>
                </div>
                <div class="col-md-4 d-flex justify-content-end gap-2">
                    @can('ajouter-type-decaissement')
                        <button wire:click="openTypeModal"
                            class="btn btn-outline-primary d-flex align-items-center shadow-sm">
                            <i class="bx bx-list-plus me-1"></i> Type
                        </button>
                    @endcan
                    <button wire:click="openModal" class="btn btn-primary d-flex align-items-center shadow-sm">
                        <i class="bx bx-plus me-1"></i> Décaissement
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
                            <th>Agent</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($disbursements as $disbursement)
                            <tr>
                                <td class="text-xs text-muted">
                                    {{ $disbursement->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <span class="badge bg-label-info">
                                        {{ $disbursement->disbursementType->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="fw-bold">
                                    <span class="text-danger">-{{ number_format($disbursement->amount, 2) }}</span>
                                </td>
                                <td>{{ $disbursement->currency }}</td>
                                <td>
                                    <span class="text-wrap d-inline-block" style="max-width: 300px;">
                                        {{ $disbursement->description }}
                                    </span>
                                </td>
                                <td class="text-sm">
                                    <i class="bx bx-user-circle me-1"></i>
                                    {{ $disbursement->user->name ?? 'N/A' }}
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-end">
                                        <button wire:click="printReceipt({{ $disbursement->id }}, 'pos')"
                                            class="btn btn-xs btn-outline-dark" title="Reçu POS">
                                            <i class="bx bx-printer"></i> POS
                                        </button>
                                        <button wire:click="printReceipt({{ $disbursement->id }}, 'a4')"
                                            class="btn btn-xs btn-outline-primary" title="Reçu Normal">
                                            <i class="bx bxs-file-pdf"></i> A4
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bx bx-folder-open fs-1 d-block mb-2"></i>
                                        Aucun décaissement enregistré.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $disbursements->links() }}
            </div>
        </div>
    </div>
</div>