<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Balance des Crédits (Retard)</h4>
        <a href="{{ route('credits-retard.pdf', ['devise' => $selectedCurrency]) }}" class="btn btn-primary shadow-sm"
            target="_blank">
            <i class="bx bx-file me-1"></i> Exporter PDF
        </a>
        <a href="{{ route('credits-retard.csv', ['currency' => $selectedCurrency]) }}" class="btn btn-success shadow-sm ms-2">
            <i class="bx bx-file me-1"></i> Exporter CSV
        </a>
    </div>

    <!-- Statistiques KPI -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card card-border-shadow border-start-danger h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar bg-label-danger p-2 me-2 rounded">
                            <i class="bx bx-error-alt fs-4"></i>
                        </div>
                        <h6 class="mb-0">Total en Retard</h6>
                    </div>
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 me-2">{{ number_format($stats['total_late_amount'], 2) }}</h5>
                        <small class="text-muted">{{ $selectedCurrency == 'all' ? 'GLO' : $selectedCurrency }}</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-border-shadow border-start-warning h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar bg-label-warning p-2 me-2 rounded">
                            <i class="bx bx-folder-open fs-4"></i>
                        </div>
                        <h6 class="mb-0">Dossiers en Retard</h6>
                    </div>
                    <h5 class="mb-0">{{ $stats['case_count'] }} <small
                            class="text-muted fs-6 text-lowercase">dossiers</small></h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-border-shadow border-start-info h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar bg-label-info p-2 me-2 rounded">
                            <i class="bx bx-time fs-4"></i>
                        </div>
                        <h6 class="mb-0">Retard Moyen</h6>
                    </div>
                    <h5 class="mb-0">{{ $stats['avg_late_days'] }} <small
                            class="text-muted fs-6 text-lowercase">jours</small></h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-border-shadow border-start-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar bg-label-primary p-2 me-2 rounded">
                            <i class="bx bx-pie-chart-alt fs-4"></i>
                        </div>
                        <h6 class="mb-0">Impact Pénalités</h6>
                    </div>
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0 me-2">{{ $stats['penalty_impact'] }}%</h5>
                        <span class="badge bg-label-primary">du principal</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <label class="form-label me-2 mb-0 fw-bold text-muted small text-uppercase">Devise</label>
                        <select wire:model.live="selectedCurrency"
                            class="form-select form-select-sm w-auto border-0 bg-light shadow-none">
                            <option value="all">Toutes les devises</option>
                            @foreach ($currencies as $currency)
                                @if($currency !== 'toutes')
                                    <option value="{{ $currency }}">{{ $currency }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-8 text-end">
                    <small class="text-muted italic">Mise à jour en temps réel des indicateurs.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="text-uppercase small fw-bold" style="font-size: 0.7rem;">
                        <th class="ps-3">ID / Membre</th>
                        <th>Dates (Crédit/Prévu)</th>
                        <th>Montant</th>
                        <th>Restant</th>
                        <th>Pénalités</th>
                        <th class="text-center">Jours</th>
                        <th class="text-center">1-30j</th>
                        <th class="text-center">31-60j</th>
                        <th class="text-center">61-90j</th>
                        <th class="text-center">91-180j</th>
                        <th class="text-center">181-360j</th>
                        <th class="text-center">361-720j</th>
                        <th class="text-center pe-3">>720j</th>
                    </tr>
                </thead>
                <tbody class="fs-6" style="font-size: 0.85rem !important;">
                    @forelse ($credits as $d)
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-primary">#{{ $d['credit_id'] }} -
                                        {{ $d['member_code'] }}</span>
                                    <small class="text-truncate" style="max-width: 150px;">{{ $d['member_name'] }}</small>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <small class="text-muted">C:
                                        {{ \Carbon\Carbon::parse($d['credit_date'])->format('d/m/y') }}</small>
                                    <small class="text-danger fw-bold">P:
                                        {{ \Carbon\Carbon::parse($d['credit_payment'])->format('d/m/y') }}</small>
                                </div>
                            </td>
                            <td class="fw-bold">{{ number_format($d['credit_amount'], 0) }} <small
                                    class="text-muted">{{ $d['currency'] }}</small></td>
                            <td class="text-danger fw-bold">{{ number_format($d['remaining_balance'], 0) }}</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="text-warning fw-bold">{{ number_format($d['total_penalty'], 0) }}</span>
                                    <small class="text-muted"
                                        style="font-size: 0.7rem;">{{ $d['penalty_percentage'] }}%</small>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-label-danger">{{ $d['days_late'] }}j</span>
                            </td>
                            <td class="text-center small">{{ $d['range_1'] ? number_format($d['range_1'], 0) : '-' }}</td>
                            <td class="text-center small">{{ $d['range_2'] ? number_format($d['range_2'], 0) : '-' }}</td>
                            <td class="text-center small">{{ $d['range_3'] ? number_format($d['range_3'], 0) : '-' }}</td>
                            <td class="text-center small">{{ $d['range_4'] ? number_format($d['range_4'], 0) : '-' }}</td>
                            <td class="text-center small">{{ $d['range_5'] ? number_format($d['range_5'], 0) : '-' }}</td>
                            <td class="text-center small">{{ $d['range_6'] ? number_format($d['range_6'], 0) : '-' }}</td>
                            <td class="text-center small pe-3">{{ $d['range_7'] ? number_format($d['range_7'], 0) : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center py-4 text-muted small">Aucun crédit en retard découvert.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold border-top">
                    <tr class="fs-6">
                        <td colspan="2" class="ps-3">TOTAL GÉNÉRAL</td>
                        <td>{{ number_format($totaux['credit_amount'], 0) }}</td>
                        <td class="text-danger">{{ number_format($totaux['remaining_balance'], 0) }}</td>
                        <td class="text-warning">{{ number_format($totaux['total_penalty'], 0) }}</td>
                        <td></td>
                        <td class="text-center small">{{ number_format($totaux['range_1'], 0) }}</td>
                        <td class="text-center small">{{ number_format($totaux['range_2'], 0) }}</td>
                        <td class="text-center small">{{ number_format($totaux['range_3'], 0) }}</td>
                        <td class="text-center small">{{ number_format($totaux['range_4'], 0) }}</td>
                        <td class="text-center small">{{ number_format($totaux['range_5'], 0) }}</td>
                        <td class="text-center small">{{ number_format($totaux['range_6'], 0) }}</td>
                        <td class="text-center small pe-3">{{ number_format($totaux['range_7'], 0) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="card-footer bg-white border-top">
            {{ $credits->links() }}
        </div>
    </div>
</div>
