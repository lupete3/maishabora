<div class="container-fluid mt-4">
    <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Liste des Demandes de Crédit</h5>
        <a href="#" class="btn btn-primary btn-sm">Nouvelle Demande</a>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-4">
                <input type="text" class="form-control" placeholder="Rechercher un membre..." wire:model.debounce.300ms="search">
            </div>
            <div class="col-md-3">
                <select class="form-select" wire:model="statusFilter">
                    <option value="">Tous les statuts</option>
                    <option value="en_analyse">En Analyse</option>
                    <option value="approuve">Approuvé</option>
                    <option value="rejete">Rejeté</option>
                    <option value="debourse">Déboursé</option>
                    <option value="cloture">Clôturé</option>
                </select>
            </div>
        </div>

        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Membre</th>
                        <th>Montant</th>
                        <th>Durée</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($loans as $loan)
                        <tr>
                            <td><strong>#{{ $loan->id }}</strong></td>
                            <td>{{ $loan->user->name }} {{ $loan->user->postnom }}</td>
                            <td>{{ number_format($loan->montant_demande, 2) }}</td>
                            <td>{{ $loan->duree_mois }} mois</td>
                            <td>{{ $loan->date_demande }}</td>
                            <td>
                                @php
                                    $badges = [
                                        'en_analyse' => 'bg-label-warning',
                                        'approuve' => 'bg-label-success',
                                        'rejete' => 'bg-label-danger',
                                        'debourse' => 'bg-label-info',
                                        'cloture' => 'bg-label-secondary',
                                    ];
                                @endphp
                                <span class="badge {{ $badges[$loan->statut] ?? 'bg-label-primary' }} me-1">{{ ucfirst(str_replace('_', ' ', $loan->statut)) }}</span>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{ route('credit.applications.show', $loan->id) }}"><i class="bx bx-edit-alt me-1"></i> Éditer</a>
                                        <a class="dropdown-item" href="{{ route('credit.applications.show', $loan->id) }}"><i class="bx bx-show me-1"></i> Voir Détails</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">Aucune demande trouvée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $loans->links() }}
        </div>
    </div>
</div>
</div>
