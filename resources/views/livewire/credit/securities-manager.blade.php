<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Garanties</h5>
    </div>
    <div class="card-body">
        <form wire:submit.prevent="add" class="mb-4">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Type</label>
                    <input type="text" class="form-control" wire:model="type" placeholder="Ex: Hypothèque">
                    @error('type') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Valeur Estimée</label>
                    <input type="number" class="form-control" wire:model="valeur_estimee" placeholder="0.00">
                    @error('valeur_estimee') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Nature du Bien</label>
                    <input type="text" class="form-control" wire:model="nature_bien" placeholder="Ex: Maison">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Propriétaire</label>
                    <input type="text" class="form-control" wire:model="proprietaire" placeholder="Nom">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" wire:model="description" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Ajouter Garantie</button>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Nature</th>
                        <th>Valeur</th>
                        <th>Propriétaire</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($securities as $s)
                        <tr>
                            <td>{{ $s['type'] }}</td>
                            <td>{{ $s['nature_bien'] }}</td>
                            <td>{{ number_format($s['valeur_estimee'], 2) }}</td>
                            <td>{{ $s['proprietaire'] }}</td>
                            <td>
                                <button class="btn btn-sm btn-danger" wire:click="delete({{ $s['id'] }})">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>