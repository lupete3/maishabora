<div class="modal fade" id="modalManageDisbursementTypes" tabindex="-1"
    aria-labelledby="modalManageDisbursementTypesLabel" aria-hidden="true" data-focus="false" wire:ignore.self>
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalManageDisbursementTypesLabel">
                    {{ __('Gestion des Types de Décaissement') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <!-- Add New Type Form -->
                <div class="card mb-4 bg-light border-0">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">{{ $isEditingType ? 'Modifier le Type' : 'Ajouter un Nouveau Type' }}
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <input type="text" wire:model="{{ $isEditingType ? 'typeName' : 'newTypeName' }}"
                                    class="form-control" placeholder="Nom du type (ex: Transport, Loyer...)">
                                @error($isEditingType ? 'typeName' : 'newTypeName')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-4 d-flex gap-2">
                                @if($isEditingType)
                                    <button wire:click="updateType" class="btn btn-primary flex-grow-1"
                                        wire:loading.attr="disabled">
                                        Mettre à jour
                                    </button>
                                    <button wire:click="cancelEditType" class="btn btn-outline-secondary">
                                        <i class="bx bx-x"></i>
                                    </button>
                                @else
                                    <button wire:click="addType" class="btn btn-primary flex-grow-1"
                                        wire:loading.attr="disabled">
                                        Ajouter
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Existing Types Table -->
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nom du Type</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($disbursementTypes as $type)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="fw-semibold">{{ $type->name }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button wire:click="editType({{ $type->id }})" class="btn btn-outline-primary"
                                                title="Modifier">
                                                <i class="bx bx-edit-alt"></i>
                                            </button>
                                            <button wire:click="deleteType({{ $type->id }})"
                                                wire:confirm="Êtes-vous sûr de vouloir supprimer ce type ?"
                                                class="btn btn-outline-danger" title="Supprimer">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>