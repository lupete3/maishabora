<!-- resources/views/livewire/global-credit-dashboard.blade.php -->

<div class="container mt-4">
    <!-- Statistiques des crédits -->
    <div class="row g-2 mb-4">
        <div class="col-md-2 ">
            <div class="card card-border-shadow border-start-primary">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Crédits totaux</h6>
                        <h5 class="mb-0">{{ $totalCredits }}</h5>
                    </div>
                    <div class="avatar bg-primary text-white rounded-circle shadow">
                        <i class="bx bx-money fs-4 m-2"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-2 ">
            <div class="card card-border-shadow border-start-success">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">En cours</h6>
                        <h5 class="mb-0">{{ $creditsInProgress }}</h5>
                    </div>
                    <div class="avatar bg-success text-white rounded-circle shadow">
                        <i class="bx bx-hourglass fs-4 m-2"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-2 ">
            <div class="card card-border-shadow border-start-danger">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">En retard</h6>
                        <h5 class="mb-0">{{ $overdueCreditsCount }}</h5>
                    </div>
                    <div class="avatar bg-danger text-white rounded-circle shadow">
                        <i class="bx bx-error fs-4 m-2"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 ">
            <div class="card card-border-shadow border-start-warning">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Pénalités cumulées USD</h6>
                        <h5 class="mb-0">{{ number_format($totalPenalties['USD'], 2) }}</h5>
                    </div>
                    <div class="avatar bg-warning text-white rounded-circle shadow">
                        <i class="bx bx-dollar fs-4 m-2"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 ">
            <div class="card card-border-shadow border-start-info">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Pénalités cumulées CDF</h6>
                        <h5 class="mb-0">{{ number_format($totalPenalties['CDF'], 2) }}</h5>
                    </div>
                    <div class="avatar bg-info text-white rounded-circle shadow">
                        <i class="bx bx-wallet fs-4 m-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Caisse Centrale & Échéances en retard -->
    <div class="row">
        <div class="col-md-7 mb-4">
            <div class="row">
                <div class="col-md-12">
                    <div class="card h-100">
                        <div class="card-header bg-label-primary fw-bold">
                            Soldes Caisse Centrale
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush" style="font-size: 24px">
                                @foreach($cashRegisters as $cr)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $cr->currency }}
                                        <span class="badge bg-primary">
                                            {{ number_format($cr->balance, 2) }} {{ $cr->currency }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 mt-4">
                    <livewire:membership-card-stats>
                </div>
            </div>
        </div>

        <div class="col-md-5 mb-4">
            <div class="card h-100">
                <div class="card-header bg-label-danger fw-bold">
                    Échéances en retard
                </div>
                <div class="card-body p-0">
                    @if($overdueCredits->isEmpty())
                        <div class="p-3">Aucune échéance en retard.</div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($overdueCredits as $r)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $r->credit->user->code. ' '. $r->credit->user->name }}</strong><br>
                                        <small class="text-muted">Montant : {{ $r->total_due.' '.$r->credit->currency }}</small>
                                    </div>
                                    <span class="badge bg-danger">
                                        {{ \Carbon\Carbon::parse($r->due_date)->format('d/m/Y') }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="mt-3">
                        {{ $overdueCredits->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Liste des crédits -->
    <div class="card">
        <div class="card-header bg-label-secondary fw-bold">
            Liste des crédits en cours
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Membre</th>
                            <th>Devise</th>
                            <th>Montant</th>
                            <th>Taux</th>
                            <th>Échéances</th>
                            <th>Date de début</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($credits as $credit)
                            <tr>
                                <td>{{ $credit->user->name.' '.$credit->user->postnom }}</td>
                                <td>{{ $credit->currency }}</td>
                                <td>{{ number_format($credit->amount, 2) }}</td>
                                <td>{{ $credit->interest_rate }}%</td>
                                <td>{{ $credit->installments }}</td>
                                <td>{{ \Carbon\Carbon::parse($credit->start_date)->format('d/m/Y') }}</td>
                                <td>
                                    @if ($credit->is_paid)
                                        <span class="badge bg-success">Remboursé</span>
                                    @else
                                        <span class="badge bg-warning">En cours</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('schedule.generate', ['creditId' => $credit->id]) }}" target="_blank" class="btn btn-sm btn-secondary">
                                        Imprimer le plan
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Aucun crédit trouvé.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $credits->links() }}
            </div>
        </div>
    </div>

    {{-- <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-dark text-white">Crédits par mois</div>
            <div class="card-body">
                <div id="creditsByMonthChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-dark text-white">Répartition par devise</div>
            <div class="card-body">
                <div id="creditsByCurrencyChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-dark text-white">Montant remboursé par mois</div>
            <div class="card-body">
                <div id="repaymentsByMonthChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>


    <div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">Crédits par mois</div>
            <div class="card-body">
                <div id="creditsByMonthChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">Crédits par devise</div>
            <div class="card-body">
                <div id="creditsByCurrencyChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-warning text-dark">Remboursements mensuels</div>
            <div class="card-body">
                <div id="repaymentsByMonthChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>
</div>--}}

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    document.addEventListener('livewire:load', function () {


        // Crédits par mois
        new ApexCharts(document.querySelector("#creditsByMonthChart"), {
            chart: {
                type: 'bar',
                height: 300
            },
            series: [{
                name: 'Crédits',
                data: @json($creditsCounts)
            }],
            xaxis: {
                categories: @json($creditsMonths)
            },
            colors: ['#0d6efd']
        }).render();

        // Crédits par devise
        new ApexCharts(document.querySelector("#creditsByCurrencyChart"), {
            chart: {
                type: 'pie',
                height: 300
            },
            series: @json($currencyCounts),
            labels: @json($currencyLabels),
            colors: ['#198754', '#ffc107', '#dc3545', '#0dcaf0']
        }).render();

        // Remboursements par mois
        new ApexCharts(document.querySelector("#repaymentsByMonthChart"), {
            chart: {
                type: 'line',
                height: 300
            },
            series: [{
                name: 'Montant remboursé',
                data: @json($repaymentAmounts)
            }],
            xaxis: {
                categories: @json($repaymentMonths)
            },
            colors: ['#fd7e14']
        }).render();
    });
</script>
</div>
