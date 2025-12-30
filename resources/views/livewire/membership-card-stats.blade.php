<!-- resources/views/livewire/membership-card-stats.blade.php -->
<div class="row g-2">
    <!-- Statistique USD -->
    <div class="col-md-6">
        <div class="card h-100 shadow-sm border-0 border-start border-4 border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0 text-success fw-bold"><i class="fas fa-book me-2"></i> Carnets USD</h5>
                    <div class="p-2 rounded bg-success-subtle text-success">
                        <i class="fas fa-dollar-sign fs-4"></i>
                    </div>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center border-bottom-dashed px-0 py-2">
                        <span class="text-muted">Total Carnets</span>
                        <span class="fw-bold text-dark">{{ $totalCardsUsd }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center border-bottom-dashed px-0 py-2">
                        <span class="text-muted">En cours</span>
                        <div class="text-end">
                            <span class="badge bg-success-subtle text-success rounded-pill">{{ $activeCardsUsd }}</span>
                            <div class="text-success small fw-bold">{{ number_format($activeCardsValueUsd, 2) }} $</div>
                        </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center border-bottom-dashed px-0 py-2">
                        <span class="text-muted">Fermés</span>
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill">{{ $closedCardsUsd }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 pt-3">
                        <span class="fw-bold text-dark">Total</span>
                        <span class="fw-bold text-success">{{ number_format($totalContributionsUsd, 2) }} $</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Statistique CDF -->
    <div class="col-md-6">
        <div class="card h-100 shadow-sm border-0 border-start border-4 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0 text-primary fw-bold"><i class="fas fa-book me-2"></i> Carnets CDF</h5>
                    <div class="p-2 rounded bg-primary-subtle text-primary">
                         <i class="fas fa-money-bill-wave fs-4"></i>
                    </div>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center border-bottom-dashed px-0 py-2">
                        <span class="text-muted">Total Carnets</span>
                        <span class="fw-bold text-dark">{{ $totalCardsCdf }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center border-bottom-dashed px-0 py-2">
                         <span class="text-muted">En cours</span>
                         <div class="text-end">
                            <span class="badge bg-primary-subtle text-primary rounded-pill">{{ $activeCardsCdf }}</span>
                            <div class="text-primary small fw-bold">{{ number_format($activeCardsValueCdf, 2) }} FC</div>
                         </div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center border-bottom-dashed px-0 py-2">
                         <span class="text-muted">Fermés</span>
                         <span class="badge bg-secondary-subtle text-secondary rounded-pill">{{ $closedCardsCdf }}</span>
                    </li>
                     <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 pt-3">
                        <span class="fw-bold text-dark">Total</span>
                        <span class="fw-bold text-primary">{{ number_format($totalContributionsCdf, 2) }} FC</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
