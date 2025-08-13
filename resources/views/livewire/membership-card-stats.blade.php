<!-- resources/views/livewire/membership-card-stats.blade.php -->
<div class="row g-4 mb-4">
    <!-- Statistique USD -->
    <div class="col-md-6 col-lg-6">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <h5 class="card-title">Carnets : USD</h5>
                <ul class="list-group list-group-flush text-white bg-dark">
                    <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">
                        Total Carnets
                        <span class="badge bg-light text-dark">{{ $totalCardsUsd }}</span>
                    </li>
                    <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">
                        En cours
                        <span class="badge bg-success">{{ $activeCardsUsd }}</span>
                    </li>
                    <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">
                        Fermés
                        <span class="badge bg-secondary">{{ $closedCardsUsd }}</span>
                    </li>
                    <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">
                        Tot
                        <span class="badge bg-warning text-dark">{{ number_format($totalContributionsUsd, 2) }} USD</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Statistique CDF -->
    <div class="col-md-6 col-lg-6">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <h5 class="card-title ">Carnets : CDF</h5>
                <ul class="list-group list-group-flush text-white bg-dark">
                    <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">
                        Total Carnets
                        <span class="badge bg-light text-dark">{{ $totalCardsCdf }}</span>
                    </li>
                    <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">
                        En cours
                        <span class="badge bg-success">{{ $activeCardsCdf }}</span>
                    </li>
                    <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">
                        Fermés
                        <span class="badge bg-secondary">{{ $closedCardsCdf }}</span>
                    </li>
                    <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">
                        Tot
                        <span class="badge bg-warning text-dark">{{ number_format($totalContributionsCdf, 2) }} CDF</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
