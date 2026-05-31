<div class="mt-0">
    <h4>Rapport Statistique des Membres</h4>

    <div class="row g-3 mb-4">
        <!-- Total Membres -->
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-user"></i></span>
                        </div>
                        <h6 class="ms-1 mb-0">Total Membres</h6>
                    </div>
                    <div class="d-flex align-items-center">
                        <h3 class="mb-0 me-2">{{ number_format($total) }}</h3>
                        <small class="text-muted">Inscrits</small>
                    </div>
                </div>
            </div>
        </div>
        <!-- Hommes -->
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-success"><i class="bx bx-male-sign"></i></span>
                        </div>
                        <h6 class="ms-1 mb-0">Hommes</h6>
                    </div>
                    <div class="d-flex align-items-center">
                        <h3 class="mb-0 me-2">{{ number_format($totalMale) }}</h3>
                        <small class="text-success fw-semibold">
                            <i class='bx bx-chevron-right'></i> {{ $percentMale }}%
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <!-- Femmes -->
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-warning"><i
                                    class="bx bx-female-sign"></i></span>
                        </div>
                        <h6 class="ms-1 mb-0">Femmes</h6>
                    </div>
                    <div class="d-flex align-items-center">
                        <h3 class="mb-0 me-2">{{ number_format($totalFemale) }}</h3>
                        <small class="text-warning fw-semibold">
                            <i class='bx bx-chevron-right'></i> {{ $percentFemale }}%
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <!-- Nouveaux -->
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-info"><i class="bx bx-user-plus"></i></span>
                        </div>
                        <h6 class="ms-1 mb-0">Nouveaux (30j)</h6>
                    </div>
                    <div class="d-flex align-items-center">
                        <h3 class="mb-0 me-2">{{ number_format($newClients) }}</h3>
                        <small class="text-info">Recrues</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-3">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-2 mb-3">
                <select wire:model.lazy="sexe" class="form-select">
                    <option value="">Sexe</option>
                    <option value="Masculin">Masculin</option>
                    <option value="Féminin">Féminin</option>
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <select wire:model.lazy="status" class="form-select">
                    <option value="">Statut</option>
                    <option value="1">Actif</option>
                    <option value="0">Inactif</option>
                </select>
            </div>

            <div class="col-md-2 mb-3">
                <input type="date" wire:model.lazy="startDate" class="form-control" placeholder="Depuis le" />
            </div>

            <div class="col-md-2 mb-3">
                <input type="date" wire:model.lazy="endDate" class="form-control" placeholder="Jusqu'au" />
            </div>

            <div class="col-md-2 mb-3">
                <select wire:model.lazy="periodFilter" class="form-select">
                    <option value="">Période</option>
                    <option value="today">Aujourd'hui</option>
                    <option value="this_week">Cette semaine</option>
                    <option value="this_month">Ce mois</option>
                    <option value="this_year">Cette année</option>
                </select>
            </div>

            <div class="col-md-2 mb-3 d-flex gap-2">
                <button wire:click="exportPdf" class="btn btn-primary flex-grow-1 p-2" wire:loading.attr="disabled" title="Télécharger PDF">
                    <span wire:loading class="spinner-border spinner-border-sm me-1" role="status"></span>
                    <i class="bx bx-file"></i> PDF
                </button>
                <button wire:click="exportExcel" class="btn btn-success flex-grow-1 p-2" wire:loading.attr="disabled" title="Exporter Excel">
                    <span wire:loading class="spinner-border spinner-border-sm me-1" role="status"></span>
                    <i class="bx bx-spreadsheet"></i> Excel
                </button>
            </div>
        </div>
    </div>

    <!-- Liste des membres -->
    <div class="table-responsive card">
        <table class="table table-striped mb-0">
            <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Nom</th>
                    <th>Sexe</th>
                    <th>Téléphone</th>
                    <th>Profession</th>
                    <th>Date Adhésion</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clients as $client)
                    <tr>
                        <td>{{ $client->code }}</td>
                        <td>{{ $client->name }} {{ $client->postnom }} {{ $client->prenom }}</td>
                        <td>{{ $client->sexe }}</td>
                        <td>{{ $client->telephone }}</td>
                        <td>{{ $client->profession }}</td>
                        <td>{{ \Carbon\Carbon::parse($client->created_at)->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge {{ $client->status ? 'bg-success' : 'bg-secondary' }}">
                                {{ $client->status ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">Aucun membre trouvé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-2">
        {{ $clients->links() }}
    </div>
</div>
