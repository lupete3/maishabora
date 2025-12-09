<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Tableau de Flux de Trésorerie (TFR)</h5>
    </div>
    <div class="card-body">
        <form wire:submit.prevent="save">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">Type d'Activité</label>
                    <input type="text" class="form-control" wire:model="type_activite"
                        placeholder="Type d'activité">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">CA Mensuel Estimé</label>
                    <input type="number" class="form-control" wire:model="chiffre_affaires_mensuel_estime"
                        placeholder="0.00">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Achats Mensuels / CAMV</label>
                    <input type="number" class="form-control" wire:model="camv_ou_achats_mensuels" placeholder="0.00">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Charges Activité Mensuelles</label>
                    <input type="number" class="form-control" wire:model="charges_activite_mensuelles"
                        placeholder="0.00">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Autres Revenus Mensuels</label>
                    <input type="number" class="form-control" wire:model="autres_revenus_mensuels" placeholder="0.00">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Charges Ménage Mensuelles</label>
                    <input type="number" class="form-control" wire:model="charges_menage_mensuelles" placeholder="0.00">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Retraits Propriétaire (Mensuel)</label>
                    <input type="number" class="form-control" wire:model="owner_withdrawals_monthly" placeholder="0.00">
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" wire:click="calculate">Calculer</button>
                <button type="submit" class="btn btn-primary">Enregistrer TFR</button>
            </div>
        </form>

        @if (session()->has('message'))
            <div class="alert alert-success mt-3">
                {{ session('message') }}
            </div>
        @endif
    </div>
</div>