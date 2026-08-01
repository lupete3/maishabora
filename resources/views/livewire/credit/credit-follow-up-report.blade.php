<!-- resources/views/livewire/credit-follow-up-report.blade.php -->
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Rapport Suivi des Crédits</h4>
        <div>
            <button wire:click="exportToPdf" class="btn btn-primary shadow-sm" wire:loading.attr="disabled">
                <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                <i class="bx bx-download"></i> Exporter PDF
            </button>
        </div>
    </div>

    <!-- Récapitulatif Moderne -->
    <div class="row g-3 mb-4">
        <!-- Crédits Totaux (Principal) -->
        <div class="col-md-6 col-lg-3">
            <div class="card card-border-shadow border-start-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar bg-label-primary p-2 me-2 rounded">
                            <i class="bx bx-money fs-4"></i>
                        </div>
                        <h6 class="mb-0">Crédits Totaux</h6>
                    </div>
                    @foreach ($totals['totalByCurrency'] as $curr => $total)
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="text-muted small">{{ $curr }}</span>
                            <span class="fw-bold fs-6">{{ number_format($total, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Récupéré (Remboursé) -->
        <div class="col-md-6 col-lg-3">
            <div class="card card-border-shadow border-start-success h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar bg-label-success p-2 me-2 rounded">
                            <i class="bx bx-check-double fs-4"></i>
                        </div>
                        <h6 class="mb-0">Remboursés</h6>
                    </div>
                    @foreach ($totals['totalPaidByCurrency'] as $curr => $total)
                        <div class="mt-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">{{ $curr }}</span>
                                <span class="fw-bold text-success fs-6">{{ number_format($total, 2) }}</span>
                            </div>
                            <div class="progress mt-1" style="height: 4px;">
                                <div class="progress-bar bg-success" role="progressbar"
                                    style="width: {{ $totals['recoveryRateByCurrency'][$curr] ?? 0 }}%"
                                    aria-valuenow="{{ $totals['recoveryRateByCurrency'][$curr] ?? 0 }}" aria-valuemin="0"
                                    aria-valuemax="100"></div>
                            </div>
                            <div class="text-end">
                                <small class="text-success fw-bold"
                                    style="font-size: 0.7rem;">{{ number_format($totals['recoveryRateByCurrency'][$curr] ?? 0, 1) }}%
                                    Recouv.</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Reste à Payer -->
        <div class="col-md-6 col-lg-3">
            <div class="card card-border-shadow border-start-danger h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar bg-label-danger p-2 me-2 rounded">
                            <i class="bx bx-wallet fs-4"></i>
                        </div>
                        <h6 class="mb-0">En cours (Restant)</h6>
                    </div>
                    @foreach ($totals['totalUnpaidByCurrency'] as $curr => $total)
                        <div class="mt-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">{{ $curr }}</span>
                                <span class="fw-bold text-danger fs-6">{{ number_format($total, 2) }}</span>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-label-danger mt-1" style="font-size: 0.7rem;">
                                    {{ number_format($totals['debtRatioByCurrency'][$curr] ?? 0, 1) }}% restant
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Gains (Intérêts & Pénalités) -->
        <div class="col-md-6 col-lg-3">
            <div class="card card-border-shadow border-start-warning h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar bg-label-warning p-2 me-2 rounded">
                            <i class="bx bx-trending-up fs-4"></i>
                        </div>
                        <h6 class="mb-0">Gains & Frais</h6>
                    </div>
                    @foreach ($totals['totalByCurrency'] as $curr => $total)
                        <div class="mt-2 border-bottom pb-1 mb-1 last-child-no-border">
                            <div class="d-flex justify-content-between small">
                                <span class="text-muted">Intérêts ({{ $curr }})</span>
                                <span
                                    class="fw-bold text-warning">{{ number_format($totals['interestByCurrency'][$curr] ?? 0, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span class="text-muted">Pénalités ({{ $curr }})</span>
                                <span
                                    class="fw-bold text-warning">{{ number_format($totals['penaltyByCurrency'][$curr] ?? 0, 2) }}</span>
                            </div>
                            <div class="text-end">
                                <small class="text-warning fw-bold"
                                    style="font-size: 0.7rem;">+{{ number_format($totals['interestMarginByCurrency'][$curr] ?? 0, 1) }}%
                                    gain</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres Avancés -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Membre</label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="searchMember" class="form-control"
                            placeholder="Rechercher..." />
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Devise</label>
                    <select wire:model.live.debounce.300ms="currency" class="form-select">
                        <option value="">Toutes</option>
                        <option value="USD">USD ($)</option>
                        <option value="CDF">CDF (FC)</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Statut</label>
                    <select wire:model.live.debounce.300ms="status" class="form-select">
                        <option value="">Tous</option>
                        <option value="paid">Remboursé</option>
                        <option value="unpaid">En cours</option>
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label small fw-bold text-muted">Période & Agent</label>
                    <div class="row g-2">
                        <div class="col-4">
                            <input type="date" wire:model.live.debounce.300ms="startDate" class="form-control" />
                        </div>
                        <div class="col-4">
                            <input type="date" wire:model.live.debounce.300ms="endDate" class="form-control" />
                        </div>
                        <div class="col-4">
                            <input type="text" wire:model.live.debounce.300ms="searchAgent" class="form-control"
                                placeholder="Agent..." />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
            <div class="card-table table-responsive">
                <table class="table card-table table-vcenter table-striped table-hover small">
                    <thead class="table-light">
                        <tr class="text-uppercase" style="font-size: 0.75rem;">
                            <th>ID</th>
                            <th>Membre</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Restant</th>
                            <th>Pénalité</th>
                            <th>Agent</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="fs-6">
                        @forelse ($credits as $credit)
                        <tr>
                            <td class="text-muted">#{{ $credit->id }}</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-primary">{{ $credit->user->code }}</span>
                                    <small>{{ $credit->user->name.' '.$credit->user->postnom }}</small>
                                </div>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($credit->start_date)->format('d/m/Y') }}</td>
                            <td class="fw-bold text-dark">{{ number_format($credit->amount, 2) }} <small>{{ $credit->currency }}</small></td>
                            <td>
                                @php
                                    $remaining = $credit->repayments->sum('expected_amount') - $credit->repayments->sum('paid_amount');
                                @endphp
                                @if ($remaining > 0)
                                    <span class="text-danger fw-bold">{{ number_format($remaining, 2) }}</span>
                                @else
                                    <span class="text-success fw-bold">SOLDE</span>
                                @endif
                            </td>
                            <td class="text-warning">
                                {{ number_format($credit->repayments->sum('penalty'), 2) }}
                            </td>
                            <td>
                                <small>{{ $credit->agent ? $credit->agent->name : 'N/A' }}</small>
                            </td>
                            <td class="text-center">

                                @can('modifier-credit')
                                <div class="form-check form-switch d-flex justify-content-center">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        wire:click="toggleCreditStatus({{ $credit->id }})"
                                        @checked($credit->is_paid)

                                        @if(
                                            !auth()->user()->hasAnyRole([
                                                'Admin',
                                                'SUPER IT'
                                            ])
                                        )
                                            disabled
                                        @endif
                                    >
                                </div>
                                @endcan

                                @if($credit->is_paid)
                                    <small class="text-success fw-bold">
                                        SOLDÉ
                                    </small>
                                @else
                                    <small class="text-warning fw-bold">
                                        EN COURS
                                    </small>
                                @endif

                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted small">Aucun crédit trouvé.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex justify-content-between align-items-center">
                <span class="text-muted small">
                    Affichage de {{ $credits->firstItem() }} à {{ $credits->lastItem() }} sur {{ $credits->total() }}
                </span>
                <div>
                    {{ $credits->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
