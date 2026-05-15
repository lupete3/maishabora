<div>
    <h5 class="mb-4">Balance Comptable</h5>
    <div class="card">

        <div class="card-header border-bottom">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                <!-- Recherche -->
                <div class="w-100 w-lg-auto">
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" placeholder="Rechercher..."
                            wire:model.live.debounce.300ms="search">
                    </div>
                </div>

                <!-- Filtres -->
                <div class="d-flex flex-wrap align-items-center gap-2 w-100 w-lg-auto">
                    {{-- Filtre Période --}}
                    <select wire:model.live="period_type" class="form-select form-select-sm ">
                        <option value="tout">Tout</option>
                        <option value="jour">Aujourd'hui</option>
                        <option value="semaine">Cette semaine</option>
                        <option value="mois">Ce mois</option>
                        <option value="trimestre">Ce trimestre</option>
                        <option value="annee">Cette année</option>
                        <option value="intervalle">Intervalle</option>
                    </select>

                    {{-- Dates personnalisées --}}
                    @if ($period_type === 'intervalle')
                        <div class="row g-1 g-sm-2 align-items-center mt-2 mt-sm-0 w-100 w-sm-auto ms-0 ms-sm-1"
                            style="max-width: 350px;">
                            <div class="col">
                                <input type="date" wire:model.live="date_debut" class="form-control form-control-sm">
                            </div>
                            <div class="col-auto">
                                <span class="fw-bold">-</span>
                            </div>
                            <div class="col">
                                <input type="date" wire:model.live="date_fin" class="form-control form-control-sm">
                            </div>
                        </div>
                    @elseif($date_debut && $date_fin && $period_type !== 'tout')
                        <span class="badge bg-info text-dark">
                            {{ \Carbon\Carbon::parse($date_debut)->format('d/m/Y') }} -
                            {{ \Carbon\Carbon::parse($date_fin)->format('d/m/Y') }}
                        </span>
                    @endif

                    {{-- Devise --}}
                    <select class="form-select form-select-sm " wire:model.lazy="filter_devise">
                        <option value="">Devise</option>
                        @foreach ($currencies as $cur)
                            <option value="{{ $cur }}">{{ $cur }}</option>
                        @endforeach
                    </select>

                    {{-- Bouton Export --}}
                    <button class="btn btn-sm btn-warning ms-auto ms-lg-0" wire:click="exportPdf"
                        wire:loading.attr="disabled">
                        <span wire:loading class="spinner-border spinner-border-sm me-1" role="status"></span>
                        <i class="bx bx-download"></i> PDF
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>N° Compte</th>
                        <th>Intitulé du compte</th>
                        <th>Total Débit</th>
                        <th>Total Crédit</th>
                        <th>Solde Débiteur</th>
                        <th>Solde Créditeur</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($comptesData as $row)
                        <tr>
                            <td>{{ $row['code'] }}</td>
                            <td>{{ $row['intitule'] }}</td>
                            <td class="text-end">{{ number_format($row['total_debit'], 2, ',', ' ') }}</td>
                            <td class="text-end">{{ number_format($row['total_credit'], 2, ',', ' ') }}</td>
                            <td class="text-end">
                                {{ $row['solde_debiteur'] > 0 ? number_format($row['solde_debiteur'], 2, ',', ' ') : '' }}
                            </td>
                            <td class="text-end">
                                {{ $row['solde_crediteur'] > 0 ? number_format($row['solde_crediteur'], 2, ',', ' ') : '' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">Aucun compte</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $comptes->links() }}
        </div>
    </div>
</div>
