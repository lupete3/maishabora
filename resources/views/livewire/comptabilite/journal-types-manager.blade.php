<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">📘 Gestion des Types de Journaux</h5>
        @can('ajouter-type-journal', App\Models\User::class)
            <button class="btn btn-primary" wire:click="openCreateModal">
                <i class="bx bx-plus"></i> Nouveau Type
            </button>
        @endcan
    </div>

    <div class="card">
        <!-- Recherche -->
        <div class="card-header row">
            <div class="col-md-6 col-lg-4">
                <input type="text" class="form-control" placeholder="Rechercher..." wire:model.live="search">
            </div>
        </div>
        <hr>

        <!-- Tableau -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Libellé</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($types as $type)
                        <tr>
                            <td>{{ $type->libelle }}</td>
                            <td class="text-end">
                                @can('modifier-type-journal', App\Models\User::class)
                                    <button class="btn btn-sm btn-warning" wire:click="openEditModal({{ $type->id }})">
                                        <i class="bx bx-edit"></i>
                                    </button>
                                @endcan
                                @can('supprimer-type-journal', App\Models\User::class)
                                    <button class="btn btn-sm btn-danger" wire:click="delete({{ $type->id }})"
                                        onclick="return confirm('Supprimer ce type de journal ?')">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center">Aucun type trouvé.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="card-footer">
            {{ $types->links() }}
        </div>
    </div>

    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="journalTypeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form wire:submit.prevent="save" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ $modalMode === 'create' ? 'Créer un Type de Journal' : 'Modifier un Type de Journal' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Libellé</label>
                        <input type="text" class="form-control" wire:model.defer="libelle">
                        @error('libelle')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        {{ $modalMode === 'create' ? 'Enregistrer' : 'Mettre à jour' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
