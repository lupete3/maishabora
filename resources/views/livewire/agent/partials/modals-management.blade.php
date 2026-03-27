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

    <!-- Modal de Modification de Transaction -->
    @if($openEditTransaction)
        <div class="modal fade show" id="modalEditTransaction" tabindex="-1"
            style="display: block; background: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier la transaction</h5>
                        <button type="button" class="btn-close" wire:click="closeEditModal"></button>
                    </div>
                    <form wire:submit.prevent="updateTransaction">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Montant</label>
                                <input type="number" step="0.01"
                                    class="form-control @error('editAmount') is-invalid @enderror"
                                    wire:model.defer="editAmount">
                                @error('editAmount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Solde Après</label>
                                <input type="number" step="0.01"
                                    class="form-control @error('editBalanceAfter') is-invalid @enderror"
                                    wire:model.defer="editBalanceAfter">
                                @error('editBalanceAfter') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description / Motif</label>
                                <textarea class="form-control @error('editDescription') is-invalid @enderror"
                                    wire:model.defer="editDescription" rows="3"></textarea>
                                @error('editDescription') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle me-2"></i>
                                Le solde de l'agent sera automatiquement ajusté de la différence.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeEditModal">Annuler</button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <span wire:loading class="spinner-border spinner-border-sm me-1"></span>
                                Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal de Suppression de Transaction -->
    @if($openDeleteTransaction)
        <div class="modal fade show" id="modalDeleteTransaction" tabindex="-1"
            style="display: block; background: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">Supprimer la transaction</h5>
                        <button type="button" class="btn-close" wire:click="closeDeleteModal"></button>
                    </div>
                    <form wire:submit.prevent="deleteTransaction">
                        <div class="modal-body">
                            <div class="alert alert-danger">
                                <i class="bx bx-error me-2"></i>
                                <strong>Attention !</strong> Cette action est irréversible. Elle supprimera la transaction
                                et ajustera automatiquement les soldes (Agent et Membre si applicable).
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Motif de la suppression</label>
                                <textarea class="form-control @error('deleteReason') is-invalid @enderror"
                                    wire:model.defer="deleteReason" rows="3"
                                    placeholder="Expliquez pourquoi vous supprimez cette opération..."></textarea>
                                @error('deleteReason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="closeDeleteModal">Annuler</button>
                            <button type="submit" class="btn btn-danger" wire:loading.attr="disabled">
                                <span wire:loading class="spinner-border spinner-border-sm me-1"></span>
                                Confirmer la suppression
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>