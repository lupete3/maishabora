<!-- Modal -->
<div class="modal fade" id="modalMembre" tabindex="-1" aria-labelledby="modalMembreLabel" aria-hidden="true"
    data-focus="false" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form wire:submit.prevent="{{ $editModal ? 'update' : 'submit' }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMembreLabel">{{ __("Information de l'utilisateur") }}
                    </h5>
                    <button type="button" class="btn-close" aria-label="Close" wire:click='closeModal'></button>
                </div>

                <div class="modal-body">

                    <!-- Membres -->
                    <div class="row g-3">

                    <!-- Nom -->
                    <div class="col-md-4 mb-1">
                        <label for="name" class="form-label">Nom</label>
                        <input type="text" wire:model.defer="name" id="name" class="form-control" placeholder="Nom"
                            required autofocus />
                        @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Postnom -->
                    <div class="col-md-4 mb-1">
                        <label for="postnom" class="form-label">Postnom</label>
                        <input type="text" wire:model.defer="postnom" id="postnom" class="form-control"
                            placeholder="Postnom" required />
                        @error('postnom') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Prenom -->
                    <div class="col-md-4 mb-1">
                        <label for="prenom" class="form-label">Prénom (optionnel)</label>
                        <input type="text" wire:model.defer="prenom" id="prenom" class="form-control"
                            placeholder="Prénom" />
                        @error('prenom') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Date de naissance -->
                    <div class="col-md-4 mb-1">
                        <label for="date_naissance" class="form-label">Date de naissance</label>
                        <input type="date" wire:model.defer="date_naissance" id="date_naissance" class="form-control"
                            required />
                        @error('date_naissance') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Téléphone -->
                    <div class="col-md-4 mb-1">
                        <label for="telephone" class="form-label">Téléphone</label>
                        <input type="text" wire:model.defer="telephone" id="telephone" class="form-control"
                            placeholder="+243........" required />
                        @error('telephone') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Profession -->
                    <div class="col-md-4 mb-1">
                        <label for="profession" class="form-label">Profession</label>
                        <input type="text" wire:model.defer="profession" id="profession" class="form-control"
                            placeholder="Ex: Agriculteur" />
                        @error('profession') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Email -->
                    <div class="col-md-8 mb-1">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" wire:model.defer="email" id="email" class="form-control"
                            placeholder="exemple@domaine.com" required />
                        @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Status physique -->
                    <div class="col-md-4 mb-1">
                        <label for="adresse_physique" class="form-label">Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="status" wire:model.defer="status">
                            <label class="form-check-label" for="status">Actif</label>
                        </div>
                        @error('adresse_physique') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Mot de passe (pour la mise à jour uniquement) -->
                    @if ($editModal)
                        <div class="col-md-6 mb-1">
                            <label for="password" class="form-label">Nouveau mot de passe (optionnel)</label>
                            <input type="password" wire:model.defer="password" id="password" class="form-control"
                                placeholder="Laisser vide pour ne pas changer" />
                            @error('password') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div class="col-md-6 mb-1">
                        <label for="password" class="form-label">Rôles</label>

                        <div class="form-check">
                            @foreach ($roles_user as $role)
                                <input class="form-check-input" type="checkbox" wire:model.defer="roles" value="{{ $role->name }}" id="role_{{ $role->id }}">
                                <label class="form-check-label" for="role_{{ $role->id }}">{{ $role->name }}</label><br>
                            @endforeach
                        </div>
                        @error('roles') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- Adresse physique -->
                    <div class="col-md-12 mb-1">
                        <label for="adresse_physique" class="form-label">Adresse physique</label>
                        <textarea wire:model.defer="adresse_physique" id="adresse_physique" class="form-control"
                            rows="3"></textarea>
                        @error('adresse_physique') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        wire:click='closeModal'>{{ __('Fermer') }}</button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <span wire:loading class="spinner-border spinner-border-sm me-2"
                            role="status"></span>

                        {{ $editModal ? __('Mettre à jour') : __('Ajouter') }}
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
