<div>
    <h4>Rapport Statistique des Carnets</h4>

    <div class="row mb-3">
        <div class="col-md-2">
            <select wire:model.lazy="status" class="form-control" id="status">
                <option value="">Tous les carnets</option>
                <option value="open">En cours</option>
                <option value="closed">Clôturé</option>
            </select>
        </div>
        <div class="col-md-2">
            <select wire:model.lazy="currency" class="form-control">
                <option value="">Toutes les devises</option>
                <option value="CDF">CDF</option>
                <option value="USD">USD</option>
            </select>
        </div>

        <div class="col-md-2">
            <select wire:model.lazy="periodFilter" class="form-control">
                <option value="">Toutes les périodes</option>
                <option value="today">Aujourd'hui</option>
                <option value="this_week">Cette semaine</option>
                <option value="this_month">Ce mois</option>
                <option value="this_year">Cette année</option>
            </select>
        </div>

        <div class="col-md-2">
            <select wire:model.lazy="status" class="form-control">
                <option value="">Touts les status</option>
                <option value="open">Actif</option>
                <option value="closed">Inactif</option>
            </select>
        </div>

        <div class="col-md-2">
            <input type="number" min="0" max="31" wire:model.lazy="minDaysFilled" class="form-control" placeholder="Min jr remplis">
        </div>

        <div class="col-md-2">
            <input type="number" min="0" max="31" wire:model.lazy="exactDaysFilled" class="form-control" placeholder="Jours remplis exacts">
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h6>Total des carnets</h6>
                <h4>{{ $totalCarnets }}</h4>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h6>Carnets en CDF</h6>
                <h4>{{ $carnetsCDF }}</h4>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h6>Carnets en USD</h6>
                <h4>{{ $carnetsUSD }}</h4>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h6>Montant total à épargner</h6>
                <h4>{{ number_format($totalToSave, 2).' '.$currency }}</h4>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h6>Montant total épargné</h6>
                <h4>{{ number_format($totalSaved, 2).' '.$currency }}</h4>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h6>Total des jours contribué</h6>
                <h4>{{ $totalContributedDays }} / {{ $totalCarnets * 31 }}</h4>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h5>Liste des carnets filtrés ({{ $carnets->total() }} résultats)</h5>
            <div class="row mb-3">
                <div class="col-md-6">
                    <input wire:model.live="search" type="text" placeholder="Recherche membre..." class="form-control" />
                </div>
                <div class="col-md-3">
                    <select wire:model.lazy="currency" class="form-control">
                        <option value="">-- Devise --</option>
                        <option value="CDF">CDF</option>
                        <option value="USD">USD</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button wire:click="exportPdf" class="btn btn-primary w-100" wire:loading.attr="disabled">
                        <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                        <i class="bx bx-download"></i> Télécharger PDF
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Nom du membre</th>
                            <th>Montant/Jour</th>
                            <th>Jours payés</th>
                            <th>Taux de remplissage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($carnets as $carnet)
                            @php
                                $pourcentage = round(($carnet->contributed_days_count / 31) * 100, 1);
                            @endphp
                            <tr>
                                <td>{{ $carnet->member->name.' '.$carnet->member->postnom.' '.$carnet->member->prenom ?? 'N/A' }}</td>
                                <td>{{ number_format($carnet->subscription_amount, 2) }} {{$carnet->currency }}</td>
                                <td>{{ $carnet->contributed_days_count }}</td>
                                <td>
                                    {{ $pourcentage }}%
                                    <div class="progress bg-label-success" style="height: 6px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                            style="width: {{ $pourcentage }}%" 
                                            aria-valuenow="{{ $pourcentage }}" 
                                            aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">Aucun carnet trouvé.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-2">
                    {{ $carnets->links() }}
                </div>
            </div>
        </div>
    </div>
</div>