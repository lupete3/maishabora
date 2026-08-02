<!-- resources/views/livewire/global-credit-dashboard.blade.php -->

<div class="container mt-4">
    <!-- Statistiques des crédits -->
    <div class="row g-2 mb-4">
        <!-- Crédits Totaux -->
        <div class="col-md-4">
            <div class="card card-border-shadow border-start-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-muted mb-0">Crédits Totaux</h6>
                        <div class="avatar bg-primary text-white rounded-circle shadow">
                            <i class="bx bx-money fs-4 m-2"></i>
                        </div>
                    </div>
                    <div class="row text-center mt-3">
                        <div class="col-6 border-end">
                            <div class="fw-bold text-dark fs-6">{{ $totalCreditsCount['USD'] ?? 0 }}</div>
                            <small class="text-success fw-bold" style="font-size: 0.75rem;">{{ number_format($totalCreditsValue['USD'] ?? 0, 2) }}
                                $</small>
                        </div>
                        <div class="col-6">
                            <div class="fw-bold text-dark fs-6">{{ $totalCreditsCount['CDF'] ?? 0 }}</div>
                            <small class="text-primary fw-bold" style="font-size: 0.75rem;">{{ number_format($totalCreditsValue['CDF'] ?? 0, 0) }}
                                FC</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- En cours -->
        <div class="col-md-4">
            <div class="card card-border-shadow border-start-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-muted mb-0">En cours</h6>
                        <div class="avatar bg-success text-white rounded-circle shadow">
                            <i class="bx bx-hourglass fs-4 m-2"></i>
                        </div>
                    </div>
                    <div class="row text-center mt-3">
                        <div class="col-6 border-end">
                            <div class="fw-bold text-dark fs-6">{{ $creditsInProgressCount['USD'] ?? 0 }}</div>
                            <small
                                class="text-success fw-bold" style="font-size: 0.75rem;">{{ number_format($creditsInProgressValue['USD'] ?? 0, 2) }}
                                $</small>
                        </div>
                        <div class="col-6">
                            <div class="fw-bold text-dark fs-6">{{ $creditsInProgressCount['CDF'] ?? 0 }}</div>
                            <small
                                class="text-primary fw-bold" style="font-size: 0.75rem;">{{ number_format($creditsInProgressValue['CDF'] ?? 0, 0) }}
                                FC</small>
                        </div>
                    </div>
                    <div class="mt-2 text-center">
                        <span class="badge bg-label-success small" style="font-size: 0.7rem;">
                            <i class="bx bx-check-circle"></i> Actifs :
                            {{ number_format($inProgressRate['USD'] ?? 0, 1) }}% (USD) /
                            {{ number_format($inProgressRate['CDF'] ?? 0, 1) }}% (CDF)
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- En retard -->
        <div class="col-md-4">
            <div class="card card-border-shadow border-start-danger h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-muted mb-0">En retard</h6>
                        <div class="avatar bg-danger text-white rounded-circle shadow">
                            <i class="bx bx-error fs-4 m-2"></i>
                        </div>
                    </div>
                    <div class="row text-center mt-3">
                        <div class="col-6 border-end">
                            <div class="fw-bold text-dark fs-6">{{ $overdueCreditsCount['USD'] ?? 0 }}</div>
                            <small class="text-success fw-bold" style="font-size: 0.75rem;">{{ number_format($overdueCreditsValue['USD'] ?? 0, 2) }}
                                $</small>
                        </div>
                        <div class="col-6">
                            <div class="fw-bold text-dark fs-6">{{ $overdueCreditsCount['CDF'] ?? 0 }}</div>
                            <small class="text-primary fw-bold" style="font-size: 0.75rem;">{{ number_format($overdueCreditsValue['CDF'] ?? 0, 0) }}
                                FC</small>
                        </div>
                    </div>
                    @if(max($overdueRate['USD'] ?? 0, $overdueRate['CDF'] ?? 0) > 0)
                        <div class="mt-2 text-center">
                            <span class="badge bg-label-danger">
                                <i class="bx bx-trending-up"></i> Taux de ret. :
                                {{ number_format($overdueRate['USD'] ?? 0, 1) }}% (USD) /
                                {{ number_format($overdueRate['CDF'] ?? 0, 1) }}% (CDF)
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Pénalités -->
        <div class="col-md-6 mt-2">
            <div class="card card-border-shadow border-start-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-muted mb-0">Pénalités cumulées</h6>
                        <div class="avatar bg-warning text-white rounded-circle shadow">
                            <i class="bx bx-error-circle fs-4 m-2"></i>
                        </div>
                    </div>
                    <div class="row text-center mt-3">
                        <div class="col-6 border-end">
                            <div class="text-muted small">USD</div>
                            <div class="fw-bold text-dark fs-6">{{ number_format($totalPenalties['USD'] ?? 0, 2) }} $</div>
                            <small class="text-warning fw-bold" style="font-size: 0.7rem;">{{ number_format($penaltyWeight['USD'] ?? 0, 1) }}% du recouv.</small>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">CDF</div>
                            <div class="fw-bold text-dark fs-6">{{ number_format($totalPenalties['CDF'] ?? 0, 0) }} FC</div>
                            <small class="text-warning fw-bold" style="font-size: 0.7rem;">{{ number_format($penaltyWeight['CDF'] ?? 0, 1) }}% du recouv.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reste à Payer (Restant) -->
        <div class="col-md-6 mt-2">
            <div class="card card-border-shadow border-start-info h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-muted mb-0">Reste à Payer (Global)</h6>
                        <div class="avatar bg-info text-white rounded-circle shadow">
                            <i class="bx bx-wallet fs-4 m-2"></i>
                        </div>
                    </div>
                    <div class="row text-center mt-3">
                        <div class="col-6 border-end">
                            <div class="text-muted small">USD</div>
                            <div class="fw-bold text-danger fs-6">{{ number_format($remainingBalanceValue['USD'] ?? 0, 2) }} $</div>
                            <small class="text-info fw-bold" style="font-size: 0.7rem;">{{ number_format($debtRatio['USD'] ?? 0, 1) }}% restant</small>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">CDF</div>
                            <div class="fw-bold text-danger fs-6">{{ number_format($remainingBalanceValue['CDF'] ?? 0, 0) }} FC</div>
                            <small class="text-info fw-bold" style="font-size: 0.7rem;">{{ number_format($debtRatio['CDF'] ?? 0, 1) }}% restant</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Nouveaux Détails Financiers -->
    <div class="row g-2 mb-4">
        <!-- Total à Rembourser -->
        <div class="col-md-6">
            <div class="card bg-label-primary border-0 shadow-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar bg-primary text-white rounded p-2 me-3">
                            <i class="bx bx-calculator fs-4"></i>
                        </div>
                        <h6 class="mb-0">Total Attendu (Principal + Intérêts)</h6>
                    </div>
                    <div class="d-flex justify-content-around mt-3">
                        <div class="text-center">
                            <div class="text-muted small">USD</div>
                            <h6 class="mb-0 fw-bold">{{ number_format($totalToRepayValue['USD'] ?? 0, 2) }} $</h6>
                            <small class="text-primary fw-bold" style="font-size: 0.7rem;">+{{ number_format($interestMargin['USD'] ?? 0, 1) }}% d'intérêts</small>
                        </div>
                        <div class="vr"></div>
                        <div class="text-center">
                            <div class="text-muted small">CDF</div>
                            <h6 class="mb-0 fw-bold">{{ number_format($totalToRepayValue['CDF'] ?? 0, 0) }} FC</h6>
                            <small class="text-primary fw-bold" style="font-size: 0.7rem;">+{{ number_format($interestMargin['CDF'] ?? 0, 1) }}% d'intérêts</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Remboursé -->
        <div class="col-md-6">
            <div class="card bg-label-success border-0 shadow-none h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar bg-success text-white rounded p-2 me-3">
                            <i class="bx bx-check-double fs-4"></i>
                        </div>
                        <h6 class="mb-0">Total Déjà Remboursé</h6>
                    </div>
                    <div class="d-flex justify-content-around mt-3">
                        <div class="text-center">
                            <div class="text-muted small">USD</div>
                            <h6 class="mb-0 fw-bold text-success">{{ number_format($totalRepaidValue['USD'] ?? 0, 2) }} $</h6>
                            <div class="progress mt-1" style="height: 4px; width: 60px; margin: 0 auto;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $recoveryRate['USD'] ?? 0 }}%" aria-valuenow="{{ $recoveryRate['USD'] ?? 0 }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-success fw-bold" style="font-size: 0.7rem;">{{ number_format($recoveryRate['USD'] ?? 0, 1) }}%</small>
                        </div>
                        <div class="vr"></div>
                        <div class="text-center">
                            <div class="text-muted small">CDF</div>
                            <h6 class="mb-0 fw-bold text-success">{{ number_format($totalRepaidValue['CDF'] ?? 0, 0) }} FC</h6>
                            <div class="progress mt-1" style="height: 4px; width: 60px; margin: 0 auto;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $recoveryRate['CDF'] ?? 0 }}%" aria-valuenow="{{ $recoveryRate['CDF'] ?? 0 }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-success fw-bold" style="font-size: 0.7rem;">{{ number_format($recoveryRate['CDF'] ?? 0, 1) }}%</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Caisse Centrale & Échéances en retard -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="row">
                <div class="col-md-5">
                    <div class="card h-100">
                        <div class="card-header bg-label-primary fw-bold">
                            Soldes Caisse Centrale
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach ($cashRegisters as $cr)
                                    <div class="col-md-12 mt-3">
                                        <div class="card border shadow-sm h-100">
                                            <div class="card-body text-center">
                                                <h5 class="card-title text-primary fw-bold">
                                                    {{ $cr->currency }}
                                                </h5>
                                                <p class="card-text" style="font-size: 24px; font-weight: bold;">
                                                    {{ number_format($cr->balance, 2) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- <div class="col-md-7">
                    <div class="card h-100">
                        <div class="card-header bg-label-secondary fw-bold">
                            Statistiques des Cartes de Membre
                        </div>
                        <div class="m-4" wire:ignore>
                            <livewire:membership-card-stats wire:key="card-stats" />
                        </div>
                    </div>
                </div> --}}
            </div>
        </div>
    </div>

    <div class="mb-4" wire:ignore>
        <livewire:credit.credit-overview wire:key="credit-overview" />
    </div>

    <!-- Liste des crédits -->
    {{-- <div class="card">
        <div class="card-header bg-label-secondary fw-bold">
            Liste des crédits en cours
        </div>
        <div class="card-body">
            <!-- Filtres et Recherche -->
            <div class="row g-3 mb-4 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Recherche</label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                            placeholder="Nom, postnom ou code membre...">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Devise</label>
                    <select wire:model.live="currency" class="form-select">
                        <option value="all">Toutes</option>
                        <option value="USD">USD ($)</option>
                        <option value="CDF">CDF (FC)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Date Début</label>
                    <input type="date" wire:model.live="dateStart" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Date Fin</label>
                    <input type="date" wire:model.live="dateEnd" class="form-control">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Membre</th>
                            <th>Devise</th>
                            <th>Montant</th>
                            <th>Total à Rembourser</th>
                            <th>Total Payé</th>
                            <th>Pénalités</th>
                            <th>Taux</th>
                            <th>Échéances</th>
                            <th>Date de début</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($credits as $credit)
                            <tr>
                                <td><span class="fw-bold text-primary">{{ $credit->user->code }}</span> -
                                    {{ $credit->user->name . ' ' . $credit->user->postnom }}
                                </td>
                                <td>{{ $credit->currency }}</td>
                                <td>{{ number_format($credit->amount, 2) }}</td>
                                <td class="fw-bold">{{ number_format($credit->repayments->sum('expected_amount'), 2) }}</td>
                                <td class="text-success">{{ number_format($credit->repayments->sum('paid_amount'), 2) }}
                                </td>
                                <td class="text-danger">{{ number_format($credit->repayments->sum('penalty'), 2) }}</td>
                                <td>{{ $credit->interest_rate }}%</td>
                                <td>
                                    {{ $credit->installments }}
                                    <small class="text-muted text-lowercase">
                                        @if($credit->repayment_type == 'monthly')
                                            {{ $credit->installments > 1 ? 'mois' : 'mois' }}
                                        @elseif($credit->repayment_type == 'weekly')
                                            {{ $credit->installments > 1 ? 'semaines' : 'semaine' }}
                                        @elseif($credit->repayment_type == 'daily')
                                            {{ $credit->installments > 1 ? 'jours' : 'jour' }}
                                        @else
                                            {{ $credit->repayment_type }}
                                        @endif
                                    </small>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($credit->start_date)->format('d/m/Y') }}</td>
                                <td>
                                    <a href="{{ route('schedule.generate', ['creditId' => $credit->id]) }}" target="_blank"
                                        class="btn btn-sm btn-secondary">
                                        Imprimer le plan
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted">Aucun crédit trouvé.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $credits->links() }}
            </div>
        </div>
    </div> --}}
</div>
