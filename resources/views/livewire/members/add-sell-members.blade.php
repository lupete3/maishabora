<!-- Modal -->
<div class="modal fade" id="modalVenteCarnet" tabindex="-1" aria-labelledby="modalVenteCarnetLabel" aria-hidden="true"
    data-focus="false" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form wire:submit.prevent="{{ $editModal ? 'updateCard' : 'sellCard' }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVenteCarnetLabel">{{ __("Vendre un carnet d'adhésion") }}
                    </h5>
                    <button type="button" class="btn-close" aria-label="Close" wire:click='closeModal'></button>
                </div>

                <div class="modal-body">

                    <!-- Membres -->
                    <div wire:ignore>
                        <label for="user_id" class="form-label">{{ __('Choisir un membre') }}</label>
                        <select wire:model="user_id" id="user_id" style="width: 100%"
                            class="form-control select2 @error('user_id') is-invalid @enderror " >

                            <option value="" disabled>{{ __('-- Sélectionner --') }}</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} {{ $user->postnom }}
                                    ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        wire:click='closeModal'>{{ __('Fermer') }}</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading wire:target="sellCard" class="spinner-border spinner-border-sm me-2"
                            role="status"></span>
                        <span wire:loading wire:target="updateCard" class="spinner-border spinner-border-sm me-2"
                            role="status"></span>
                        {{ $editModal ? __('Mettre à jour') : __('Vendre le carnet') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.addEventListener('openModal', event => {
        const modalId = '#' + event.detail.name;

        $(document).ready(function () {
            const selectElement = $(modalId).find('.select2');
            selectElement.select2({
                dropdownParent: $(modalId)
            });

            // Synchroniser avec Livewire
            selectElement.on('change', function (e) {
                const data = $(this).val();
                @this.set('user_id', data); // synchronise avec la propriété Livewire
            });
        });
    });
</script>


<!-- Table des adhésions (inchangée) -->
