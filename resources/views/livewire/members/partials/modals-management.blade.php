<!-- resources/views/livewire/members/partials/modals-management.blade.php -->

<!-- Modal Modification Solde -->
<div class="modal fade @if($openModifyBalance) show @endif" id="modalModifyBalance" tabindex="-1"
    style="@if($openModifyBalance) display: block; background: rgba(0,0,0,0.5); @else display: none; @endif">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier le solde (Direct)</h5>
                <button type="button" class="btn-close" wire:click="$set('openModifyBalance', false)"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label font-bold">Nouveau Solde</label>
                    <input type="number" step="0.01" class="form-control" wire:model="newBalance">
                    @error('newBalance') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label font-bold">Raison de la modification</label>
                    <textarea class="form-control" wire:model="modificationReason"
                        placeholder="Pourquoi modifiez-vous ce solde ?"></textarea>
                    @error('modificationReason') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                    wire:click="$set('openModifyBalance', false)">Annuler</button>
                <button type="button" class="btn btn-primary" wire:click="updateBalance">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Modification Transaction -->
<div class="modal fade @if($openEditTransaction) show @endif" id="modalEditTransaction" tabindex="-1"
    style="@if($openEditTransaction) display: block; background: rgba(0,0,0,0.5); @else display: none; @endif">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier la transaction</h5>
                <button type="button" class="btn-close" wire:click="$set('openEditTransaction', false)"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label font-bold">Montant</label>
                    <input type="number" step="0.01" class="form-control" wire:model="editAmount">
                    @error('editAmount') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label font-bold">Solde Après</label>
                    <input type="number" step="0.01" class="form-control" wire:model="editBalanceAfter">
                    @error('editBalanceAfter') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label font-bold">Description</label>
                    <textarea class="form-control" wire:model="editDescription"></textarea>
                    @error('editDescription') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                    wire:click="$set('openEditTransaction', false)">Annuler</button>
                <button type="button" class="btn btn-primary" wire:click="updateTransaction">Mettre à jour</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmation Suppression Transaction -->
<div class="modal fade @if($openConfirmDeleteTransaction) show @endif" id="modalDeleteTransaction" tabindex="-1"
    style="@if($openConfirmDeleteTransaction) display: block; background: rgba(0,0,0,0.5); @else display: none; @endif">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Confirmer la suppression</h5>
                <button type="button" class="btn-close"
                    wire:click="$set('openConfirmDeleteTransaction', false)"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette transaction ? Cette action est irréversible et peut créer
                    des incohérences dans l'historique des soldes.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                    wire:click="$set('openConfirmDeleteTransaction', false)">Annuler</button>
                <button type="button" class="btn btn-danger" wire:click="deleteTransaction">Supprimer
                    définitivement</button>
            </div>
        </div>
    </div>
</div>