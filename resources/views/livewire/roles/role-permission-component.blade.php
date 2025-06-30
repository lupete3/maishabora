
<div>
    <div class="py-4">

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Gestion des Rôles</h5>
                <button wire:click="showCreateModal" class="btn btn-primary">Ajouter un Rôle</button>
            </div>

            <div class="card-body">
                <div class="mb-3">
                    <input type="text" wire:model.live="search" class="form-control" placeholder="Rechercher un rôle...">
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nom du Rôle</th>
                                <th>Permissions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($roles as $role)
                                <tr>
                                    <td>{{ $role->name }}</td>
                                    <td>
                                        @foreach ($role->permissions as $perm)
                                            <span class="badge bg-secondary mb-1">{{ $perm->name }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        <button wire:click="showEditModal({{ $role->id }})" class="btn btn-sm btn-warning">Modifier</button>
                                        <button wire:click="confirmDelete({{ $role->id }})" class="btn btn-sm btn-danger">Supprimer</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">Aucun rôle trouvé.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>
                    {{ $roles->links() }}
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <div wire:ignore.self class="modal fade" tabindex="-1" id="roleModal" aria-labelledby="roleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form wire:submit.prevent="save">
                        <div class="modal-header">
                            <h5 class="modal-title" id="roleModalLabel">{{ $roleId ? 'Modifier le Rôle' : 'Créer un Rôle' }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nom du rôle</label>
                                <input type="text" wire:model.defer="name" id="name" class="form-control" placeholder="Nom du rôle" required>
                                @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Permissions</label>
                                <div class="row">
                                    @foreach ($permissions as $perm)
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="{{ $perm->name }}" wire:model.defer="selectedPermissions" id="perm_{{ $perm->id }}">
                                                <label class="form-check-label" for="perm_{{ $perm->id }}">
                                                    {{ $perm->name }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @error('selectedPermissions') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Confirm Delete Modal -->
        <div wire:ignore.self class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmer la suppression</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir supprimer ce rôle ? Cette action est irréversible.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" wire:click="delete" class="btn btn-danger">Supprimer</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    Livewire.on('modalFormVisible', (visible) => {
        const modal = new bootstrap.Modal(document.getElementById('roleModal'));
        if (visible) {
            modal.show();
        } else {
            modal.hide();
        }
    });

    Livewire.on('confirmingDelete', (visible) => {
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        if (visible) {
            modal.show();
        } else {
            modal.hide();
        }
    });
</script>
