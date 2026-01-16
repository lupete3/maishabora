<div>
    <div class="row mb-4">
        <div class="col-md-6">
            <h4 class="text-uppercase fw-bold text-primary">Compte de Résultat</h4>
        </div>
        <div class="col-md-6 text-end">
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
                    <input type="date" wire:model.live="date_debut" class="form-control form-control-sm"
                        style="width: 130px;">
                    <span class="fw-bold">-</span>
                    <input type="date" wire:model.live="date_fin" class="form-control form-control-sm"
                        style="width: 130px;">
                @elseif($date_debut && $date_fin && $period_type !== 'tout')
                    <span class="badge bg-info text-dark">
                        {{ \Carbon\Carbon::parse($date_debut)->format('d/m/Y') }} -
                        {{ \Carbon\Carbon::parse($date_fin)->format('d/m/Y') }}
                    </span>
                @endif

                {{-- Recherche --}}
                <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm"
                    style="width: 150px;" placeholder="Rechercher...">

                {{-- Devise --}}
                <select wire:model.lazy="filter_currency" class="form-select form-select-sm" style="width: 100px;">
                    <option value="">Devise</option>
                    <option value="USD">USD</option>
                    <option value="CDF">CDF</option>
                </select>

                {{-- Export --}}
                <button class="btn btn-danger btn-sm" wire:click="export" wire:loading.attr="disabled">
                    <i class="bx bxs-file-pdf"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- CHARGES -->
        <div class="col-md-6">
            <div class="card border-top border-0 border-4 border-danger">
                <div class="card-body">
                    <h5 class="card-title text-danger mb-3 fw-bold">CHARGES (DÉPENSES)</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Intitulé</th>
                                    <th class="text-end">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['Charge'] as $charge)
                                    <tr>
                                        <td>{{ $charge['code'] }}</td>
                                        <td>{{ $charge['intitule'] }}</td>
                                        <td class="text-end">{{ number_format($charge['solde'], 2, ',', ' ') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Aucune charge</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="2" class="text-uppercase">Total Charges</td>
                                    <td class="text-end text-danger">{{ number_format($totals['Charge'], 2, ',', ' ') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRODUITS -->
        <div class="col-md-6">
            <div class="card border-top border-0 border-4 border-success">
                <div class="card-body">
                    <h5 class="card-title text-success mb-3 fw-bold">PRODUITS (RECETTES)</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Intitulé</th>
                                    <th class="text-end">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data['Produit'] as $produit)
                                    <tr>
                                        <td>{{ $produit['code'] }}</td>
                                        <td>{{ $produit['intitule'] }}</td>
                                        <td class="text-end">{{ number_format($produit['solde'], 2, ',', ' ') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Aucun produit</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="2" class="text-uppercase">Total Produits</td>
                                    <td class="text-end text-success">
                                        {{ number_format($totals['Produit'], 2, ',', ' ') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RESULTAT -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card {{ $resultat >= 0 ? 'bg-success' : 'bg-danger' }} text-white">
                <div class="card-body text-center">
                    <h3 class="fw-bold mb-0">
                        RÉSULTAT NET :
                        {{ number_format($resultat, 2, ',', ' ') }}
                        <span class="fs-6 opacity-75">
                            ({{ $resultat >= 0 ? 'BÉNÉFICE' : 'PERTE' }})
                        </span>
                    </h3>
                </div>
            </div>
        </div>
    </div>
</div>