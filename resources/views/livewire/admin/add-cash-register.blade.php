<!-- Modal -->
<div class="modal fade" id="modalCashRegister" tabindex="-1" aria-labelledby="modalVenteCarnetLabel" aria-hidden="true"
    data-focus="false" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form wire:submit.prevent="submit">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVenteCarnetLabel">{{ __("Gérer la caisse centrale") }}
                    </h5>
                    <button type="button" class="btn-close" aria-label="Close" wire:click='closeModal'></button>
                </div>

                <div class="modal-body">

                    <div class="row">
                        <div class="col-md-6 mt-2">
                            <label for="currency">{{ __('Devise') }}</label>
                            <select wire:model="currency" id="currency" class="form-control">
                                @foreach($currencies as $curr)
                                    <option value="{{ $curr }}">{{ $curr }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mt-2">
                            <label for="type">Type d'opération</label>
                            <select wire:model="type" id="type" class="form-control">
                                {{-- <option value="in">Entrée</option> --}}
                                <option value="">Choisir un type d'opération</option>
                                <option value="out">Sortie</option>
                            </select>
                        </div>

                        <div class="col-md-6 mt-2">
                            <label for="amount">Montant</label>
                            <input type="number" step="0.01" wire:model="amount" id="amount" class="form-control" />
                            @error('amount') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-6 mt-2">
                            <label for="description">Description</label>
                            <input type="text" wire:model="description" class="form-control" />
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click='closeModal'>{{ __('Fermer')
                        }}</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                        {{ $type === 'in' ? 'Valider' : 'Retirer' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Table des adhésions (inchangée) -->
