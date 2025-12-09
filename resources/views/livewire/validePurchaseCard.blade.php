{{-- Modal de confirmation avant validation --}}
<div class="modal fade @if($showConfirmationModal) show d-block @endif" tabindex="-1"
     @if($showConfirmationModal) style="background-color: rgba(0,0,0,0.5);" @endif
     role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-warning text-dark rounded-top-4">
                <h5 class="modal-title fw-bold">Confirmer l'achat de la carte</h5>
                <button type="button" class="btn-close" wire:click="$set('showConfirmationModal', false)"></button>
            </div>

            <div class="modal-body">
                <p class="fw-semibold mb-3">Veuillez vérifier les détails avant de confirmer :</p>
                <ul class="list-group list-group-flush mb-3">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Membre :</span>
                        <span class="fw-bold">{{ $selectedMemberName }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Code carte :</span>
                        <span class="fw-bold">{{ $code }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Devise :</span>
                        <span class="fw-bold">{{ $currency }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Prix de la carte :</span>
                        <span class="fw-bold text-primary">{{ number_format($price, 2) }} {{ $currency }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Montant quotidien à épargner :</span>
                        <span class="fw-bold text-success">{{ number_format($subscription_amount, 2) }} {{ $currency }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Agent :</span>
                        <span class="fw-bold">
                            {{ optional(\App\Models\User::find($agent_id))->name ?? 'Aucun' }}
                        </span>
                    </li>
                </ul>
                <p class="text-muted small mb-0">
                    Assurez-vous que toutes les informations sont correctes avant de confirmer cet achat.
                </p>
            </div>

            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" wire:click="$set('showConfirmationModal', false)">
                    Annuler
                </button>
                <button type="button" class="btn btn-success" wire:click="confirmPurchase" wire:loading.attr="disabled">
                    <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Confirmer l'achat
                </button>
            </div>
        </div>
    </div>
</div>
