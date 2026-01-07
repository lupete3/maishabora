<!-- Modal -->
<div class="modal fade" id="modalRetraitMembre" tabindex="-1" aria-labelledby="modalRetraitMembreLabel" aria-hidden="true"
    data-focus="false" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="modalRetraitMembreLabel">{{ __('Effectuer un retrait') }}</h5>
                <button type="button" class="btn-close" aria-label="Close" wire:click='closeRetraitModal'></button>
            </div>

            <div class="modal-body row">
                <div class="col-md-12">
                    <select name="type" wire:model.lazy='type' class="form-control">
                        <option value="">Choisir type d'operation</option>
                        <option value="carte">Compte Epargne (Carnet)</option>
                        <option value="normal">Compte Courant</option>
                    </select>
                </div>
            </div>

            @if ($operation_type == 'normal')
                <form wire:submit.prevent="showConfirmRetraitNormal">

                    <div class="modal-body row">

                        <div class="col-md-6 mb-3">
                            <label>Devise</label>
                            <select wire:model="currency" class="form-control">
                                <option value="">Choisir devise</option>
                                <option value="USD">USD</option>
                                <option value="CDF">CDF</option>
                            </select>
                            @error('currency')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Montant</label>
                            <input type="number" step="0.01" wire:model="amount" class="form-control" />
                            @error('amount')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Montant à retenir</label>
                            <input type="number" step="0.00" wire:model="a_retenir" class="form-control" />
                            @error('a_retenir')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <label>Description (facultatif)</label>
                            <input type="text" wire:model="description" class="form-control" />
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            wire:click='closeRetraitModal'>{{ __('Fermer') }}</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                            {{ __('Rétirer') }}
                        </button>
                    </div>
                </form>
            @endif

            @if ($operation_type == 'carte')
                <form wire:submit.prevent="showConfirmRetraitNormal">

                    <div class="modal-body row">

                        <div class="col-md-12 mb-3">
                            <label>Choisir une carte</label>
                            <select wire:model.lazy="card_id" class="form-control">
                                <option value="">Sélectionner une carte</option>
                                @foreach ($cards as $card)
                                    <option value="{{ $card->id }}">
                                        Code : {{ $card->code }} | Total épargné : {{ number_format($card->total_saved, 2) }} {{ $card->currency }}
                                    </option>
                                @endforeach
                            </select>
                            @error('card_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        @if ($this->selectedCard && !$this->selectedCard->first_mise_retained)
                            <p class="text-danger">Le carnet a été créé avant ce changement.
                             La première mise de {{ $this->selectedCard->subscription_amount }} {{ $this->selectedCard->currency }} n'ayant pas été retenue, le retrait doit se faire via un retrait normal et 
                             le retenu est de : {{ $this->selectedCard->subscription_amount }} {{ $this->selectedCard->currency }} </p>
                        @endif

                        {{-- <div class="col-md-12 mb-3">
                                <label>Description (facultatif)</label>
                                <input type="text" wire:model="description" class="form-control" />
                            </div> --}}

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            wire:click='closeRetraitModal'>{{ __('Fermer') }}</button>
                        
                            <button type="button" class="btn btn-primary" @disab wire:loading.attr="disabled"
                            @disabled($this->selectedCard && !$this->selectedCard->first_mise_retained)>
                                <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                                {{ __('Rétirer') }}
                            </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    @include('livewire.admin.confirm-retrait')

</div>
