<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-chart-line"></i> Compte de Résultat</h4>
            <button wire:click="exportPDF" class="btn btn-sm btn-danger">
                <i class="fas fa-file-pdf"></i> Exporter PDF
            </button>
        </div>
        <div class="card-body">
            {{-- Filtres --}}
            <div class="row mb-4">
                <div class="col-md-3">
                    <label>Devise</label>
                    <select wire:model.live="devise" class="form-control">
                        @foreach($currencies as $currency)
                            <option value="{{ $currency }}">{{ $currency }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Période</label>
                    <select wire:model.live="period_type" class="form-control">
                        <option value="mois">Ce mois</option>
                        <option value="trimestre">Ce trimestre</option>
                        <option value="annee">Cette année</option>
                        <option value="intervalle">Intervalle personnalisé</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Date début</label>
                    <input type="date" wire:model.live="date_debut" class="form-control" />
                </div>

                <div class="col-md-3">
                    <label>Date fin</label>
                    <input type="date" wire:model.live="date_fin" class="form-control" />
                </div>
            </div>

            {{-- Période affichée --}}
            <div class="alert alert-info">
                <strong>Période :</strong>
                Du {{ \Carbon\Carbon::parse($date_debut)->format('d/m/Y') }}
                au {{ \Carbon\Carbon::parse($date_fin)->format('d/m/Y') }}
                ({{ $devise }})
            </div>

            <div class="row">
                {{-- PRODUITS (Classe 7) --}}
                <div class="col-md-6">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">PRODUITS (Classe 7)</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Code</th>
                                        <th>Intitulé</th>
                                        <th class="text-right">Montant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($produits as $produit)
                                        <tr class="{{ $produit['level'] == 1 ? 'font-weight-bold' : '' }}">
                                            <td>
                                                @if($produit['level'] == 2)
                                                    <span class="ml-2">{{ $produit['code'] }}</span>
                                                @elseif($produit['level'] == 3)
                                                    <span class="ml-4">{{ $produit['code'] }}</span>
                                                @else
                                                    {{ $produit['code'] }}
                                                @endif
                                            </td>
                                            <td>{{ $produit['intitule'] }}</td>
                                            <td class="text-right text-success">
                                                {{ number_format($produit['montant'], 2, ',', ' ') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">
                                                Aucun produit sur la période
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="bg-success text-white font-weight-bold">
                                    <tr>
                                        <td colspan="2" class="text-right">TOTAL PRODUITS</td>
                                        <td class="text-right">
                                            {{ number_format($totalProduits, 2, ',', ' ') }} {{ $devise }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- CHARGES (Classe 6) --}}
                <div class="col-md-6">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">CHARGES (Classe 6)</h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Code</th>
                                        <th>Intitulé</th>
                                        <th class="text-right">Montant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($charges as $charge)
                                        <tr class="{{ $charge['level'] == 1 ? 'font-weight-bold' : '' }}">
                                            <td>
                                                @if($charge['level'] == 2)
                                                    <span class="ml-2">{{ $charge['code'] }}</span>
                                                @elseif($charge['level'] == 3)
                                                    <span class="ml-4">{{ $charge['code'] }}</span>
                                                @else
                                                    {{ $charge['code'] }}
                                                @endif
                                            </td>
                                            <td>{{ $charge['intitule'] }}</td>
                                            <td class="text-right text-danger">
                                                {{ number_format($charge['montant'], 2, ',', ' ') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">
                                                Aucune charge sur la période
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="bg-danger text-white font-weight-bold">
                                    <tr>
                                        <td colspan="2" class="text-right">TOTAL CHARGES</td>
                                        <td class="text-right">
                                            {{ number_format($totalCharges, 2, ',', ' ') }} {{ $devise }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RÉSULTAT NET --}}
            <div class="row mt-4">
                <div class="col-md-12">
                    <div
                        class="card {{ $resultatNet >= 0 ? 'border-primary bg-light' : 'border-warning bg-warning-light' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0">
                                        @if($resultatNet >= 0)
                                            <i class="fas fa-arrow-up text-success"></i> RÉSULTAT NET (Bénéfice)
                                        @else
                                            <i class="fas fa-arrow-down text-danger"></i> RÉSULTAT NET (Perte)
                                        @endif
                                    </h4>
                                    <small class="text-muted">
                                        Produits {{ number_format($totalProduits, 2, ',', ' ') }}
                                        - Charges {{ number_format($totalCharges, 2, ',', ' ') }}
                                    </small>
                                </div>
                                <div class="h2 mb-0 {{ $resultatNet >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format(abs($resultatNet), 2, ',', ' ') }} {{ $devise }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>