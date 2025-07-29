<div class="mt-0">
    <h4>Rapport Statistique des Membres</h4>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-primary h-100">
                <div class="card-body">
                    <h6>Total Membres</h6>
                    <p class="mb-0">{{ $total }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success h-100">
                <div class="card-body">
                    <h6>Hommes</h6>
                    <p class="mb-0">{{ $totalMale }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning h-100">
                <div class="card-body">
                    <h6>Femmes</h6>
                    <p class="mb-0">{{ $totalFemale }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info h-100">
                <div class="card-body">
                    <h6>Nouveaux (30j)</h6>
                    <p class="mb-0">{{ $newClients }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-3">
        <div class="card-body row g-3 align-items-end">
            <div class="col-md-2">
                <select wire:model.lazy="sexe" class="form-select">
                    <option value="">Sexe</option>
                    <option value="Masculin">Masculin</option>
                    <option value="Féminin">Féminin</option>
                </select>
            </div>

            <div class="col-md-2">
                <select wire:model.lazy="status" class="form-select">
                    <option value="">Statut</option>
                    <option value="1">Actif</option>
                    <option value="0">Inactif</option>
                </select>
            </div>

            <div class="col-md-2">
                <input type="date" wire:model.lazy="startDate" class="form-control" placeholder="Depuis le" />
            </div>

            <div class="col-md-2">
                <input type="date" wire:model.lazy="endDate" class="form-control" placeholder="Jusqu'au" />
            </div>

            <div class="col-md-2">
                <select wire:model.lazy="periodFilter" class="form-select">
                    <option value="">Période</option>
                    <option value="today">Aujourd'hui</option>
                    <option value="this_week">Cette semaine</option>
                    <option value="this_month">Ce mois</option>
                    <option value="this_year">Cette année</option>
                </select>
            </div>

            <div class="col-md-2">
                <button wire:click="exportPdf" class="btn btn-primary w-100" wire:loading.attr="disabled">
                    <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                    <i class="bx bx-download"></i> Télécharger PDF
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