<div>
    {{-- Filtres --}}
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Agent</label>
                    <select wire:model.live="filterAgent" class="form-select border-primary-subtle">
                        <option value="">Tous les agents</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }} {{ $agent->postnom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Du</label>
                    <input type="date" wire:model.live="filterDateFrom" class="form-control border-primary-subtle">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Au</label>
                    <input type="date" wire:model.live="filterDateTo" class="form-control border-primary-subtle">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Devise</label>
                    <select wire:model.live="filterCurrency" class="form-select border-primary-subtle">
                        <option value="all">Toutes</option>
                        <option value="USD">USD</option>
                        <option value="CDF">CDF</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Marge Simulation (%)</label>
                    <input type="number" wire:model.live="marginPercent" class="form-control border-success-subtle"
                        min="0" max="100">
                </div>
                <div class="col-md-1 text-end">
                    <button wire:click="exportPdf" wire:loading.attr="disabled" class="btn btn-outline-danger w-100"
                        title="Exporter PDF">
                        <i wire:loading.remove wire:target="exportPdf" class="bx bxs-file-pdf fs-4"></i>
                        <span wire:loading wire:target="exportPdf" class="spinner-border spinner-border-sm"
                            role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="fw-medium d-block mb-1">Total Carnets</span>
                            <div class="d-flex align-items-baseline mt-2">
                                <h4 class="mb-0 me-2 text-white">{{ $totals['cards'] }}</h4>
                            </div>
                            <small class="text-white-50">Nouveaux/Gérés</small>
                        </div>
                        <div class="avatar rounded p-2">
                            <i class="bx bx-credit-card bx-md"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 bg-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="fw-medium d-block mb-1">Ventes Carnets</span>
                            <div class="mt-2">
                                <h5 class="mb-0 text-white">{{ number_format($totals['card_revenue_usd'], 2) }} USD</h5>
                                <h5 class="mb-0 text-white">
                                    {{ number_format($totals['card_revenue_cdf'], 0, ',', ' ') }} CDF
                                </h5>
                            </div>
                        </div>
                        <div class="avatar rounded p-2">
                            <i class="bx bx-money bx-md"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 bg-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="fw-medium d-block mb-1">Retenues (Bénéfices)</span>
                            <div class="mt-2">
                                <h5 class="mb-0 text-white">{{ number_format($totals['retained_usd'], 2) }} USD</h5>
                                <h5 class="mb-0 text-white">{{ number_format($totals['retained_cdf'], 0, ',', ' ') }}
                                    CDF</h5>
                            </div>
                        </div>
                        <div class="avatar rounded p-2">
                            <i class="bx bx-stats bx-md"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="fw-medium d-block mb-1">Total Collectes</span>
                            <div class="mt-2">
                                <h5 class="mb-0 text-white">{{ number_format($totals['collection_usd'], 2) }} USD</h5>
                                <h5 class="mb-0 text-white">{{ number_format($totals['collection_cdf'], 0, ',', ' ') }}
                                    CDF</h5>
                            </div>
                        </div>
                        <div class="avatar rounded p-2">
                            <i class="bx bx-wallet-alt bx-md"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Table --}}
    <div class="card shadow-sm border-0 mt-2">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 fw-bold">
                <i class="bx bxs-user-detail me-2"></i>Détail des performances par agent
            </h5>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Agent</th>
                        <th class="text-center">Carnets</th>
                        <th class="text-end">Vente Carnets</th>
                        <th class="text-end">Retenues (First Mise)</th>
                        <th class="text-end">Collectes (Mises)</th>
                        <th class="text-end text-success">Gains Simulés ({{ $marginPercent }}%)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($performance as $agent)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2 bg-label-primary rounded-circle">
                                        <span class="avatar-initial">{{ substr($agent->name, 0, 1) }}</span>
                                    </div>
                                    <span class="fw-bold">{{ $agent->name }} {{ $agent->postnom }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-label-info">{{ $agent->metrics['card_count'] }}</span>
                            </td>
                            <td class="text-end">
                                <div class="small fw-bold">{{ number_format($agent->metrics['card_revenue_usd'], 2) }} $
                                </div>
                                <div class="text-muted" style="font-size: 0.7rem;">
                                    {{ number_format($agent->metrics['card_revenue_cdf'], 0, ',', ' ') }} FC
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="small fw-bold">{{ number_format($agent->metrics['retained_usd'], 2) }} $</div>
                                <div class="text-muted" style="font-size: 0.7rem;">
                                    {{ number_format($agent->metrics['retained_cdf'], 0, ',', ' ') }} FC
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="small fw-bold">{{ number_format($agent->metrics['collection_usd'], 2) }} $</div>
                                <div class="text-muted" style="font-size: 0.7rem;">
                                    {{ number_format($agent->metrics['collection_cdf'], 0, ',', ' ') }} FC
                                </div>
                            </td>
                            <td class="text-end">
                                @php
                                    $margin = (float) ($this->marginPercent ?: 0);
                                    $earningsUsd = ($agent->metrics['retained_usd'] * $margin) / 100;
                                    $earningsCdf = ($agent->metrics['retained_cdf'] * $margin) / 100;
                                @endphp
                                <div class="small fw-bold text-success">{{ number_format($earningsUsd, 2) }} $</div>
                                <div class="text-muted" style="font-size: 0.7rem;">
                                    {{ number_format($earningsCdf, 0, ',', ' ') }} FC
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Aucun agent trouvé avec ces critères</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td>TOTAL GENERAL</td>
                        <td class="text-center">{{ $totals['cards'] }}</td>
                        <td class="text-end">
                            <div>{{ number_format($totals['card_revenue_usd'], 2) }} $</div>
                            <div class="small text-muted">{{ number_format($totals['card_revenue_cdf'], 0, ',', ' ') }}
                                FC</div>
                        </td>
                        <td class="text-end">
                            <div>{{ number_format($totals['retained_usd'], 2) }} $</div>
                            <div class="small text-muted">{{ number_format($totals['retained_cdf'], 0, ',', ' ') }} FC
                            </div>
                        </td>
                        <td class="text-end">
                            <div>{{ number_format($totals['collection_usd'], 2) }} $</div>
                            <div class="small text-muted">{{ number_format($totals['collection_cdf'], 0, ',', ' ') }} FC
                            </div>
                        </td>
                        <td class="text-end text-success">
                            @php
                                $totalEarningsUsd = ($totals['retained_usd'] * $this->marginPercent) / 100;
                                $totalEarningsCdf = ($totals['retained_cdf'] * $this->marginPercent) / 100;
                            @endphp
                            <div>{{ number_format($totalEarningsUsd, 2) }} $</div>
                            <div class="small text-muted">{{ number_format($totalEarningsCdf, 0, ',', ' ') }} FC</div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $performance->links() }}
        </div>
    </div>
</div>
