{{-- Modal de prévisualisation --}}
<div class="modal fade @if($showPreview) show d-block @endif" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-3">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-show-alt me-2"></i> Prévisualisation du virement
                </h5>
                <button type="button" class="btn-close btn-close-white" wire:click="$set('showPreview', false)"></button>
            </div>

            <div class="modal-body">
                @if (!empty($previewData))
                    <ul class="list-group mb-3">
                        <li class="list-group-item d-flex justify-content-between">
                            <strong>Type de bénéficiaire :</strong>
                            <span>{{ $previewData['type'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <strong>Bénéficiaire :</strong>
                            <span>{{ $previewData['beneficiaire'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <strong>Devise :</strong>
                            <span>{{ $previewData['devise'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <strong>Montant :</strong>
                            <span>{{ $previewData['montant'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <strong>Description :</strong>
                            <span>{{ $previewData['description'] }}</span>
                        </li>
                    </ul>
                    <div class="mb-4 mx-auto" style="max-width: 400px;">
                        <label for="password" class="form-label fw-bold">Entrez votre mot de passe pour confirmer</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror text-center"
                            id="password" wire:model="password" placeholder="••••••••">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="alert alert-warning">
                        ⚠️ Vérifiez bien les informations avant de confirmer le virement.
                    </div>
                @endif
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="$set('showPreview', false)">Annuler</button>
                <button type="button" class="btn btn-success" wire:click="confirmTransfer">
                    <i class="bx bx-check-circle me-1"></i> Confirmer et exécuter
                </button>
            </div>
        </div>
    </div>
</div>
