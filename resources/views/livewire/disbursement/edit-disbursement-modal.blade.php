<div class="modal fade" id="modalEditDisbursement" tabindex="-1" aria-labelledby="modalEditDisbursementLabel"
    aria-hidden="true" data-focus="false" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditDisbursementLabel">{{ __('Modifier la Demande de Décaissement') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form wire:submit.prevent="updateRequest">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Type de dépense</label>
                        <select wire:model="edit_disbursement_type_id"
                            class="form-control @error('edit_disbursement_type_id') is-invalid @enderror">
                            <option value="">Sélectionner un type</option>
                            @foreach ($disbursementTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @error('edit_disbursement_type_id') <div class="invalid-feedback text-danger">{{ $message }}
                        </div> @enderror
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Montant</label>
                            <input type="number" step="0.01" wire:model="edit_amount"
                                class="form-control @error('edit_amount') is-invalid @enderror" placeholder="0.00">
                            @error('edit_amount') <div class="invalid-feedback text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Devise</label>
                            <select wire:model="edit_currency"
                                class="form-control @error('edit_currency') is-invalid @enderror">
                                <option value="CDF">CDF</option>
                                <option value="USD">USD</option>
                            </select>
                            @error('edit_currency') <div class="invalid-feedback text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description / Justification</label>
                        <textarea wire:model="edit_description"
                            class="form-control @error('edit_description') is-invalid @enderror" rows="3"
                            placeholder="Expliquez la raison de ce décaissement..."></textarea>
                        @error('edit_description') <div class="invalid-feedback text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4" wire:loading.attr="disabled">
                        <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Mettre à jour la demande
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>