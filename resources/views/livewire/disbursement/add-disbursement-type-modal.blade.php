<div class="modal fade" id="modalAddDisbursementType" tabindex="-1" aria-labelledby="modalAddDisbursementTypeLabel"
    aria-hidden="true" data-focus="false" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAddDisbursementTypeLabel">{{ __('Nouveau Type de Décaissement') }}</h5>
                <button type="button" class="btn-close" aria-label="Close" wire:click='closeTypeModal'></button>
            </div>

            <form wire:submit.prevent="addType">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nom du type</label>
                        <input type="text" wire:model="newTypeName"
                            class="form-control @error('newTypeName') is-invalid @enderror"
                            placeholder="Ex: Frais de bureau, Transport, etc.">
                        @error('newTypeName') <div class="invalid-feedback text-danger">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" wire:click="closeTypeModal" class="btn btn-secondary">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4" wire:loading.attr="disabled">
                        <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Enregistrer le type
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
