<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Décision du Comité (5C)</h5>
    </div>
    <div class="card-body">
        <form wire:submit.prevent="submit">
            <div class="row">
                <div class="col-md-2 mb-3">
                    <label class="form-label">Caractère (1-10)</label>
                    <input type="number" class="form-control" wire:model="note_caractere" min="1" max="10">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Capacité (1-10)</label>
                    <input type="number" class="form-control" wire:model="note_capacite" min="1" max="10">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Capital (1-10)</label>
                    <input type="number" class="form-control" wire:model="note_capital" min="1" max="10">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Caution (1-10)</label>
                    <input type="number" class="form-control" wire:model="note_caution" min="1" max="10">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Conditions (1-10)</label>
                    <input type="number" class="form-control" wire:model="note_caracteristiques_financieres" min="1"
                        max="10">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Commentaire Global</label>
                <textarea class="form-control" wire:model="commentaire_global" rows="3"></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Décision Finale</label>
                <select class="form-select" wire:model="decision_finale">
                    <option value="a_revoir">À Revoir</option>
                    <option value="approuve">Approuvé</option>
                    <option value="rejete">Rejeté</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Enregistrer Décision</button>
        </form>

        @if (session()->has('message'))
            <div class="alert alert-success mt-3">
                {{ session('message') }}
            </div>
        @endif
    </div>
</div>
