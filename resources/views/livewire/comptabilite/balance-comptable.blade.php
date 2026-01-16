<div>
    <h5 class="mb-4">Balance Comptable</h5>
    <div class="card">

        <div class="card-header">
            <div class="row">
            <div class="row align-items-center">
                <div class="col-md-5 mt-2">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" placeholder="Rechercher..." wire:model.live.debounce.300ms="search">
                    </div>
                </div>
                <div class="col-md-7 mt-2 text-end">
                    <div class="d-flex justify-content-end gap-2 align-items-center">
                        {{-- Filtre Période --}}
                        <select wire:model.live="period_type" class="form-select form-select-sm" style="width: 130px;">
                            <option value="tout">Tout</option>
                            <option value="jour">Aujourd'hui</option>
                            <option value="semaine">Cette semaine</option>
                            <option value="mois">Ce mois</option>
                            <option value="trimestre">Ce trimestre</option>
                            <option value="annee">Cette année</option>
                            <option value="intervalle">Intervalle</option>
                        </select>

                        {{-- Dates personnalisées --}}
                        @if($period_type === 'intervalle')
                            <input type="date" wire:model.live="date_debut" class="form-control form-control-sm" style="width: 130px;">
                            <span class="fw-bold">-</span>
                            <input type="date" wire:model.live="date_fin" class="form-control form-control-sm" style="width: 130px;">
                        @elseif($date_debut && $date_fin && $period_type !== 'tout')
                            <span class="badge bg-info text-dark">
                                {{ \Carbon\Carbon::parse($date_debut)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($date_fin)->format('d/m/Y') }}
                            </span>
                        @endif

                        {{-- Devise --}}
                        <select class="form-select form-select-sm" wire:model.lazy="filter_devise" style="width: 100px;">
                            <option value="">Devise</option>
                            @foreach ($currencies as $cur)
                                <option value="{{ $cur }}">{{ $cur }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3 mt-2">
                    <button class="btn btn-sm btn-warning float-end" wire:click="exportPdf"
                        wire:loading.attr="disabled">
                        <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                        <i class="bx bx-download"></i> Exporter PDF
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
