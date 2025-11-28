<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Bilan Simplifié</h5>
    </div>
    <div class="card-body">
        <form wire:submit.prevent="save">
            <h6 class="mt-2">Actifs</h6>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Cash / Banque</label>
                    <input type="number" class="form-control" wire:model="cash" placeholder="0.00">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Créances Clients</label>
                    <input type="number" class="form-control" wire:model="creances" placeholder="0.00">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Stock Marchandises</label>
                    <input type="number" class="form-control" wire:model="stock" placeholder="0.00">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Actifs Immobilisés</label>
                    <input type="number" class="form-control" wire:model="actifs_immobilises" placeholder="0.00">
                </div>
            </div>

            <h6 class="mt-2">Passifs (Dettes)</h6>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Dettes Formelles (CT)</label>
                    <input type="number" class="form-control" wire:model="dettes_formelles_ct" placeholder="0.00">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Dettes Formelles (MT)</label>
                    <input type="number" class="form-control" wire:model="dettes_formelles_mt" placeholder="0.00">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Dettes Formelles (LT)</label>
                    <input type="number" class="form-control" wire:model="dettes_formelles_lt" placeholder="0.00">
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Dettes Informelles (CT)</label>
                    <input type="number" class="form-control" wire:model="dettes_informelles_ct" placeholder="0.00">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Dettes Informelles (MT)</label>
                    <input type="number" class="form-control" wire:model="dettes_informelles_mt" placeholder="0.00">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Dettes Informelles (LT)</label>
                    <input type="number" class="form-control" wire:model="dettes_informelles_lt" placeholder="0.00">
                </div>
            </div>

            <h6 class="mt-2">Capitaux Propres</h6>
            <div class="mb-3">
                <label class="form-label">Fonds Propres</label>
                <input type="number" class="form-control" wire:model="fonds_propres" placeholder="0.00">
            </div>

            <button type="submit" class="btn btn-primary">Enregistrer Bilan</button>
        </form>

        @if (session()->has('message'))
            <div class="alert alert-success mt-3">
                {{ session('message') }}
            </div>
        @endif
    </div>
</div>