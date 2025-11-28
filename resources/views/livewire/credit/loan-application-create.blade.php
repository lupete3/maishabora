<div class="container-fluid mt-4">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Demande de Crédit</h5>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="mb-3">
                    <label class="form-label" for="user_id">Emprunteur (ID)</label>
                    <input type="text" class="form-control" id="user_id" wire:model="user_id"
                        placeholder="ID de l'utilisateur">
                    @error('user_id') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="business_id">Business (Optionnel)</label>
                    <select class="form-select" id="business_id" wire:model.lazy="business_id">
                        <option value="">Sélectionner un business</option>
                        @foreach($businesses as $business)
                            <option value="{{ $business->id }}">{{ $business->type_activite }} - {{ $business->secteur }}
                            </option>
                        @endforeach
                    </select>
                    @error('business_id') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="montant_demande">Montant Demandé</label>
                    <input type="number" class="form-control" id="montant_demande" wire:model="montant_demande"
                        placeholder="Montant">
                    @error('montant_demande') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="duree_mois">Durée (Mois)</label>
                    <input type="number" class="form-control" id="duree_mois" wire:model="duree_mois" placeholder="Durée">
                    @error('duree_mois') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="date_demande">Date de Demande</label>
                    <input type="date" class="form-control" id="date_demande" wire:model="date_demande">
                    @error('date_demande') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form>
            @if (session()->has('message'))
                <div class="alert alert-success mt-3">
                    {{ session('message') }}
                </div>
            @endif
        </div>
    </div>
</div>