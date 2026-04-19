<div class="modal fade" id="modalAddDisbursement" tabindex="-1" aria-labelledby="modalAddDisbursementLabel"
    aria-hidden="true" data-focus="false" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAddDisbursementLabel">{{ __('Nouveau Décaissement') }}</h5>
                <button type="button" class="btn-close" aria-label="Close" wire:click='closeModal'></button>
            </div>

            <form wire:submit.prevent="submit">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Type de décaissement</label>
                        <select wire:model="disbursement_type_id"
                            class="form-control @error('disbursement_type_id') is-invalid @enderror">
                            <option value="">Cliquer pour selectionner...</option>
                            @foreach($disbursementTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @error('disbursement_type_id') <div class="invalid-feedback text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Devise</label>
                            <select wire:model="currency" class="form-control">
                                <option value="CDF">CDF</option>
                                <option value="USD">USD</option>
                            </select>
                            @error('currency') <div class="invalid-feedback text-danger">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Montant</label>
                            <input type="number" step="0.01" wire:model="amount"
                                class="form-control @error('amount') is-invalid @enderror" placeholder="0.00">
                            @error('amount') <div class="invalid-feedback text-danger">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description / Motif</label>
                        <textarea wire:model="description"
                            class="form-control @error('description') is-invalid @enderror" rows="3"
                            placeholder="Indiquez la raison de cette sortie..."></textarea>
                        @error('description') <div class="invalid-feedback text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" wire:click="closeModal" class="btn btn-secondary">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4" wire:loading.attr="disabled">
                        <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Confirmer le décaissement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
