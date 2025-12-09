<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">📑 Gestion des Comptes</h5>
        @can('ajouter-compte-comptable', App\Models\User::class)
            <button class="btn btn-primary" wire:click="openCreateModal">
                <i class="bx bx-plus"></i> Nouveau Compte
            </button>
        @endcan
    </div>

    <div class="card">
        <!-- Recherche -->
        <div class="card-header row">
            <div class="col-md-6 col-lg-4">
                <input type="text" class="form-control" placeholder="Rechercher..." wire:model.live.debounce.300ms="search">
            </div>
        </div>
        <hr>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Intitulé</th>
                        <th>Type</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                        <tr>
                            <td>{{ $account->code }}</td>
                            <td>{{ $account->intitule }}</td>
                            <td>
                                <span class="badge bg-{{ $account->type === 'Actif' ? 'success' : 'danger' }}">
                                    {{ $account->type }}
                                </span>
                            </td>
                            <td class="text-end">
                                @can('modifier-compte-comptable', App\Models\User::class)
                                    <button class="btn btn-sm btn-warning" wire:click="openEditModal({{ $account->id }})">
                                        <i class="bx bx-edit"></i>
                                    </button>
                                @endcan
                                @can('supprimer-compte-comptable', App\Models\User::class)
                                    <button class="btn btn-sm btn-danger" wire:click="delete({{ $account->id }})"
                                        onclick="return confirm('Supprimer ce compte ?')">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">Aucun compte trouvé.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="card-footer">
            {{ $accounts->links() }}
        </div>

    </div>

    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="accountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form wire:submit.prevent="save" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {{ $modalMode === 'create' ? 'Créer un Compte' : 'Modifier un Compte' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Code</label>
                        <input type="text" class="form-control" wire:model.defer="code">
                        @error('code')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label>Intitulé</label>
                        <input type="text" class="form-control" wire:model.defer="intitule">
                        @error('intitule')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label>Type</label>
                        <select class="form-select" wire:model.defer="type">
                            <option value="">-- Choisir --</option>
                            <option value="Actif">Actif</option>
                            <option value="Passif">Passif</option>
                            <option value="Produit">Produit</option>
                            <option value="Charge">Charge</option>
                        </select>
                        @error('type')
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
