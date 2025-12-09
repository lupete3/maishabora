<div>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">🧠 Résumé automatique des transactions</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label>Filtrer par</label>
                    <select wire:model.lazy="filterType" class="form-select">
                        <option value="jour">Jour</option>
                        <option value="semaine">Semaine</option>
                        <option value="mois">Mois</option>
                        <option value="periode">Période personnalisée</option>
                    </select>
                </div>

                @if($filterType === 'periode')
                    <div class="col-md-4">
                        <label>Début</label>
                        <input type="date" wire:model="startDate" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label>Fin</label>
                        <input type="date" wire:model="endDate" class="form-control">
                    </div>
                @endif
            </div>

            @if($loading)
                <div class="alert alert-info text-center">
                    <strong>Analyse en cours...</strong>
                </div>
            @else
                <div class="card mb-3">
                    <div class="card-header bg-success text-white">💰 Dépôts</div>
                    <div class="card-body">
                        <p>{{ $summaryDeposits }}</p>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header bg-danger text-white">💸 Retraits</div>
                    <div class="card-body">
                        <p>{{ $summaryWithdrawals }}</p>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">💳 Crédits</div>
                    <div class="card-body">
                        <p>{{ $summaryCredits }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
