<!-- resources/views/livewire/members/partials/modals-management.blade.php -->

<!-- Modal Modification Solde -->
<div class="modal fade @if ($openModifyBalance) show @endif" id="modalModifyBalance" tabindex="-1"
    style="@if ($openModifyBalance) display: block; background: rgba(0,0,0,0.5); @else display: none; @endif">
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
                    @error('newBalance')
                        <span class="text-danger text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label font-bold">Raison de la modification</label>
                    <textarea class="form-control" wire:model="modificationReason" placeholder="Pourquoi modifiez-vous ce solde ?"></textarea>
                    @error('modificationReason')
                        <span class="text-danger text-xs">{{ $message }}</span>
                    @enderror
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
<div class="modal fade @if ($openEditTransaction) show @endif" id="modalEditTransaction" tabindex="-1"
    style="@if ($openEditTransaction) display: block; background: rgba(0,0,0,0.5); @else display: none; @endif">
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
                    @error('editAmount')
                        <span class="text-danger text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label font-bold">Solde Après</label>
                    <input type="number" step="0.01" class="form-control" wire:model="editBalanceAfter">
                    @error('editBalanceAfter')
                        <span class="text-danger text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label font-bold">Description</label>
                    <textarea class="form-control" wire:model="editDescription"></textarea>
                    @error('editDescription')
                        <span class="text-danger text-xs">{{ $message }}</span>
                    @enderror
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
<div class="modal fade @if ($openConfirmDeleteTransaction) show @endif" id="modalDeleteTransaction" tabindex="-1"
    style="@if ($openConfirmDeleteTransaction) display: block; background: rgba(0,0,0,0.5); @else display: none; @endif">
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

<!-- Modal Print Receipt -->
<!-- Modal Print Receipt -->
<div class="modal fade @if ($openPrintReceiptModal) show @endif" id="modalPrintReceipt" tabindex="-1"
    style="@if ($openPrintReceiptModal) display:block;background:rgba(67,89,113,.5); @else display:none; @endif">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">

            <!-- Header -->
            <div class="modal-header bg-label-primary">
                <div>
                    <h5 class="modal-title mb-1">
                        <i class="bx bx-printer me-2"></i>
                        Impression du reçu
                    </h5>
                    <small class="text-muted">
                        Choisissez le format d'impression souhaité
                    </small>
                </div>

                <button type="button" class="btn-close" wire:click="closePrintReceiptModal">
                </button>
            </div>

            <!-- Body -->
            <div class="modal-body py-4">

                <div class="text-center mb-4">
                    <h6 class="mb-1">Votre reçu est prêt</h6>
                    <p class="text-muted mb-0">
                        Sélectionnez le format adapté à votre imprimante.
                    </p>
                </div>

                <div class="row g-3">

                    @if ($printReceiptUrlPC)
                        <div class="col-md-6">
                            <a href="{{ $printReceiptUrlPC }}" target="_blank" rel="noopener"
                                class="text-decoration-none">

                                <div class="card border-primary h-100 shadow-sm hover-shadow">
                                    <div class="card-body text-center">

                                        <div class="avatar mx-auto mb-3">
                                            <span class="avatar-initial rounded bg-label-primary">
                                                <i class="bx bx-file fs-4"></i>
                                            </span>
                                        </div>

                                        <h6 class="mb-1">Format A4</h6>

                                        <small class="text-muted">
                                            Imprimante classique
                                            <br>
                                            PDF pleine page
                                        </small>

                                        <div class="mt-3">
                                            <span class="btn btn-primary btn-sm">
                                                <i class="bx bx-printer me-1"></i>
                                                Imprimer
                                            </span>
                                        </div>

                                    </div>
                                </div>
                            </a>
                        </div>
                    @endif

                    @if ($printReceiptUrlPOS)
                        <div class="col-md-6">
                            <a href="{{ $printReceiptUrlPOS }}" target="_blank" rel="noopener"
                                class="text-decoration-none">

                                <div class="card border-secondary h-100 shadow-sm">
                                    <div class="card-body text-center">

                                        <div class="avatar mx-auto mb-3">
                                            <span class="avatar-initial rounded bg-label-secondary">
                                                <i class="bx bx-receipt fs-4"></i>
                                            </span>
                                        </div>

                                        <h6 class="mb-1">Format POS</h6>

                                        <small class="text-muted">
                                            Imprimante thermique
                                            <br>
                                            Ticket de caisse
                                        </small>

                                        <div class="mt-3">
                                            <span class="btn btn-secondary btn-sm">
                                                <i class="bx bx-printer me-1"></i>
                                                Imprimer
                                            </span>
                                        </div>

                                    </div>
                                </div>
                            </a>
                        </div>
                    @endif

                </div>

            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" wire:click="closePrintReceiptModal">
                    <i class="bx bx-x me-1"></i>
                    Fermer
                </button>
            </div>

        </div>
    </div>
</div>
