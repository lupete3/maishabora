<!-- resources/views/livewire/agent/partials/modals-management.blade.php -->
<div>
    <!-- Modal de Modification du Solde Agent -->
    @if($openModifyBalance)
        <div class="modal fade show" id="modalModifyAgentBalance" tabindex="-1"
            style="display: block; background: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier le solde agent</h5>
                        <button type="button" class="btn-close" wire:click="closeModifyBalanceModal"></button>
                    </div>
                    <form wire:submit.prevent="updateBalance">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nouveau solde</label>
                                <input type="number" step="0.01"
                                    class="form-control @error('newBalance') is-invalid @enderror"
                                    wire:model.defer="newBalance">
                                @error('newBalance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Raison de la modification</label>
                                <textarea class="form-control @error('modificationReason') is-invalid @enderror"
                                    wire:model.defer="modificationReason" rows="3"
                                    placeholder="Ex: Correction erreur de saisie..."></textarea>
                                @error('modificationReason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="alert alert-warning">
                                <i class="bx bx-error me-2"></i>
                                Cette action modifiera directement le solde et créera une transaction de rectification.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                wire:click="closeModifyBalanceModal">Annuler</button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <span wire:loading class="spinner-border spinner-border-sm me-1"></span>
                                Mettre à jour le solde
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>