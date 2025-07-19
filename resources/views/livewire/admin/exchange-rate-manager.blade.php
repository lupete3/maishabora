<div class="card">
    <div class="card-header fw-bold bg-label-warning">Gestion des taux de change</div>
    <div class="card-body">

        @if (session()->has('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form wire:submit.prevent="{{ $editId ? 'update' : 'save' }}">
            <div class="row mb-3 mt-3">
                <div class="col-md-2">
                    <label>De</label>
                    <select wire:model="from_currency" class="form-control">
                        <option value="USD">USD</option>
                        <option value="CDF">CDF</option>
                    </select>
                    @error('from_currency') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="col-md-2">
                    <label>Vers</label>
                    <select wire:model="to_currency" class="form-control">
                        <option value="USD">USD</option>
                        <option value="CDF">CDF</option>
                    </select>
                    @error('to_currency') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="col-md-4">
                    <label>Taux</label>
                    <input type="number" step="0.00000001" wire:model="rate" class="form-control">
                    @error('rate') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="col-md-4">
                    <label>Date d'application</label>
                    <input type="datetime-local" wire:model="applied_at" class="form-control">
                    @error('applied_at') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="d-flex">
                <button class="btn btn-primary me-2" type="submit">
                    {{ $editId ? 'Mettre à jour' : 'Enregistrer' }}
                </button>

                @if($editId)
                    <button type="button" class="btn btn-secondary" wire:click="cancelEdit">Annuler</button>
                @endif
            </div>
        </form>

        <hr>

        <h5 class="mt-4">Historique des taux</h5>
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>De</th>
                    <th>Vers</th>
                    <th>Taux</th>
                    <th>Appliqué le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rates as $r)
                    <tr>
                        <td>{{ $r->from_currency }}</td>
                        <td>{{ $r->to_currency }}</td>
                        <td>{{ $r->rate }}</td>
                        <td>{{ $r->applied_at ? $r->applied_at : '-' }}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" wire:click="edit({{ $r->id }})">
                                Modifier
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
