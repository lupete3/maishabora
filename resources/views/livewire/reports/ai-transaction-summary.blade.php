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
                <div class="alert alert-info text-center border-0 shadow-sm mt-3">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    <strong>L'IA analyse les flux financiers...</strong>
                </div>
            @else
                <!-- Analyse Globale -->
                <div class="card my-4 border-primary shadow-sm">
                    <div class="card-header bg-label-primary d-flex align-items-center">
                        <i class="bx bxs-pie-chart-alt-2 me-2"></i>
                        <h6 class="mb-0 text-primary">Business Intelligence - Analyse Globale</h6>
                    </div>
                    <div class="card-body mt-3">
                        <div class="white-space-pre-wrap lead h5">
                            {{ $summaryGlobal }}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-3 shadow-none border">
                            <div class="card-header bg-success text-white py-2">💰 Dépôts</div>
                            <div class="card-body pt-3">
                                <p class="mb-0 small">{{ $summaryDeposits }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card mb-3 shadow-none border">
                            <div class="card-header bg-danger text-white py-2">💸 Retraits</div>
                            <div class="card-body pt-3">
                                <p class="mb-0 small">{{ $summaryWithdrawals }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card mb-3 shadow-none border">
                            <div class="card-header bg-primary text-white py-2">💳 Crédits</div>
                            <div class="card-body pt-3">
                                <p class="mb-0 small">{{ $summaryCredits }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
</div>
</div>
</div>
