<!-- Modal -->
<div class="modal fade" id="modalRetraitMembre" tabindex="-1" aria-labelledby="modalRetraitMembreLabel"
    aria-hidden="true" data-focus="false" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form wire:submit.prevent="submitRetrait">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalRetraitMembreLabel">{{ __("Effectuer un retrait") }}</h5>
                    <button type="button" class="btn-close" aria-label="Close" wire:click='closeRetraitModal'></button>
                </div>

                <div class="modal-body row">


                    <div class="col-md-6 mb-3">
                        <label>Devise</label>
                        <select wire:model="currency" class="form-control">
                            <option value="">Choisir devise</option>
                            <option value="USD">USD</option>
                            <option value="CDF">CDF</option>
                        </select>
                        @error('currency') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Montant</label>
                        <input type="number" step="0.01" wire:model="amount" class="form-control" />
                        @error('amount') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    {{-- <div class="col-md-12 mb-3">
                        <label>Description (facultatif)</label>
                        <input type="text" wire:model="description" class="form-control" />
                    </div> --}}

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click='closeRetraitModal'>{{ __('Fermer')
                        }}</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                        {{ __('Rétirer') }}
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>


<!-- Table des adhésions (inchangée) -->
