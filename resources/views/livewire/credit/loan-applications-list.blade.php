<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-file"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ $stats['total'] }}</h4>
                    </div>
                    <p class="mb-1">Total Demandes</p>
                    <p class="mb-0">
                        <small class="text-muted">Toutes les demandes</small>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-warning h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-warning"><i class="bx bx-time-five"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ $stats['pending'] }}</h4>
                    </div>
                    <p class="mb-1">En Analyse</p>
                    <p class="mb-0">
                        <small class="text-muted">Attente de décision</small>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-success h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-success"><i class="bx bx-dollar"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($stats['total_usd'], 0) }} $</h4>
                    </div>
                    <p class="mb-1">Total Demandé (USD)</p>
                    <p class="mb-0">
                        <small class="text-muted">Cumul en dollars</small>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3 mb-4">
            <div class="card card-border-shadow-info h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-info"><i class="bx bx-wallet"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($stats['total_cdf'], 0) }} FC</h4>
                    </div>
                    <p class="mb-1">Total Demandé (CDF)</p>
                    <p class="mb-0">
                        <small class="text-muted">Cumul en francs</small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Liste des Demandes de Crédit</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('credit.applications.print-blank') }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-printer me-1"></i> Fiche Terrain Vierge
                </a>
                @can('ajouter-demandes-credit')
                    <a href="{{ route('credit.applications.create') }}" class="btn btn-primary btn-sm">Nouvelle Demande</a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @if (session()->has('message'))
                <div class="alert alert-success alert-dismissible" role="alert">
                    {{ session('message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row mb-4">
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Rechercher un membre..."
                        wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-3">
                    <select class="form-select" wire:model.lazy="statusFilter">
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
                                <td @if(auth()->user()->canAny(['afficher-compte-membre', 'depot-compte-membre', 'retrait-compte-membre']))
                                    onclick="window.location.href='{{ route('member.details', $loan->user->id) }}'"
                                    style="cursor: pointer;"
                                @endif>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold"></span>{{ $loan->user->name }}
                                        {{ $loan->user->postnom }}</span>
                                        <small class="text-muted">{{ $loan->user->code }}</small>
                                    </div>
                                </td>
                                <td>{{ number_format($loan->montant_demande, 2) }} {{ $loan->currency }}</td>
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
                                    <span
                                        class="badge {{ $badges[$loan->statut] ?? 'bg-label-primary' }} me-1">{{ ucfirst(str_replace('_', ' ', $loan->statut)) }}</span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown">
                                            <i class="bx bx-dots-vertical-rounded"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item"
                                                href="{{ route('credit.applications.show', $loan->id) }}"><i
                                                    class="bx bx-edit-alt me-1"></i> Éditer</a>
                                            <a class="dropdown-item"
                                                href="{{ route('credit.applications.show', $loan->id) }}"><i
                                                    class="bx bx-show me-1"></i> Voir Détails</a>
                                            <a class="dropdown-item" target="_blank"
                                                href="{{ route('credit.applications.print-filled', $loan->id) }}"><i
                                                    class="bx bx-printer me-1"></i> Imprimer Dossier</a>
                                            <div class="dropdown-divider"></div>
                                            <button class="dropdown-item text-danger"
                                                onclick="confirm('Êtes-vous sûr de vouloir supprimer cette demande ?') || event.stopImmediatePropagation()"
                                                wire:click="delete({{ $loan->id }})">
                                                <i class="bx bx-trash me-1"></i> Supprimer
                                            </button>
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
