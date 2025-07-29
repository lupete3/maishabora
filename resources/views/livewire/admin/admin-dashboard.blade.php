<div class=" flex-grow-1 ">
    
    <div class="row">
        <div class="col-lg-12 order-1">
            <div class="row flex-1">
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <img src="../assets/img/icons/unicons/chart-success.png" alt="chart success"
                                        class="rounded" />
                                </div>
                                <div class="dropdown">
                                    <button class="btn p-0" type="button" id="cardOpt3" data-bs-toggle="dropdown"
                                        aria-haspopup="true" aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                                        <a class="dropdown-item" href="javascript:void(0);">Voir plus</a>
                                    </div>
                                </div>
                            </div>
                            <span class="fw-semibold d-block mb-1">Profit</span>
                            <h3 class="card-title mb-2">{{ number_format($totalBalance, 0, ',', '') }}</h3>
                            <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> +72.80%</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <img src="../assets/img/icons/unicons/wallet-info.png" alt="Credit Card"
                                        class="rounded" />
                                </div>
                                <div class="dropdown">
                                    <button class="btn p-0" type="button" id="cardOpt6" data-bs-toggle="dropdown"
                                        aria-haspopup="true" aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt6">
                                        <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                        <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                    </div>
                                </div>
                            </div>
                            <span>Adhésions</span>
                            <h3 class="card-title text-nowrap mb-1">{{ number_format($totalAdhesion, 0, ',', '') }}
                            </h3>
                            <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> +28.42%</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <img src="../assets/img/icons/unicons/paypal.png" alt="Credit Card" class="rounded" />
                                </div>
                                <div class="dropdown">
                                    <button class="btn p-0" type="button" id="cardOpt4" data-bs-toggle="dropdown"
                                        aria-haspopup="true" aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt4">
                                        <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                        <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                    </div>
                                </div>
                            </div>
                            <span class="d-block mb-1">Retraits</span>
                            <h3 class="card-title text-nowrap mb-2">{{ number_format($totalWithdrawals, 0, ',', ' ') }}
                            </h3>
                            <small class="text-danger fw-semibold"><i class="bx bx-down-arrow-alt"></i> -14.82%</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <img src="../assets/img/icons/unicons/cc-primary.png" alt="Credit Card"
                                        class="rounded" />
                                </div>
                                <div class="dropdown">
                                    <button class="btn p-0" type="button" id="cardOpt1" data-bs-toggle="dropdown"
                                        aria-haspopup="true" aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu" aria-labelledby="cardOpt1">
                                        <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                        <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                    </div>
                                </div>
                            </div>
                            <span class="fw-semibold d-block mb-1">Transactions</span>
                            <h3 class="card-title mb-2">{{ number_format($totalBalance + $totalWithdrawals, 0, ',', ' ') }}</h3>
                            <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i> +28.14%</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            window.reports = @json($monthlyReport);
        </script>
        <!-- Total Revenue -->
        <div class="col-12 col-md-12 order-2 order-md-3 order-lg-2 mb-4">
            <div class="card">
                <div class="row row-bordered g-0">
                    <div class="col-md-9">
                        <h5 class="card-header m-0 me-2 pb-3">Total Revenue</h5>
                        <div id="totalRevenueChartNew" class="px-2"></div>
                    </div>
                    <div class="col-md-3">
                        <div class="card-body">
                            <div class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                                        id="growthReportId" data-bs-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">
                                        Télécharger Rapports
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="growthReportId">
                                        <a class="dropdown-item" href="{{ route('admin.reports.monthly.pdf') }}">Mensuel</a>
                                        <a class="dropdown-item" href="{{ route('admin.reports.annual.pdf') }}">Annuel</a>
                                        <a class="dropdown-item" href="javascript:void(0);">2019</a>
                                    </div>

                                </div>
                            </div>
                        </div>
                        <div id="growthChart"></div>
                        <div class="text-center fw-semibold pt-3 mb-2">
                            {{ $growthRate }}% Croissance
                        </div>

                        <div class="d-flex px-xxl-4 px-lg-2 p-4 gap-xxl-3 gap-lg-1 gap-3 justify-content-between">
                            <div class="d-flex">
                                <div class="me-2">
                                    <span class="badge bg-label-primary p-2"><i
                                            class="bx bx-dollar text-primary"></i></span>
                                </div>
                                @php
                                $dernier = $monthlyReport->last();
                                @endphp

                                <div class="d-flex flex-column">
                                    <small>{{ $dernier['mois'] ?? '' }}</small>
                                    <h6 class="mb-0">{{ number_format($dernier['solde'], 0, ',', ' ') }} CDF</h6>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--/ Total Revenue -->

    </div>
    <div class="row">
        <script>
            window.monthlyPieChart = @json($monthlyPieChart);
        </script>
        <!-- Order Statistics -->
        <div class="col-md-6 order-0 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between pb-0">
                    <div class="card-title mb-0">
                        <h5 class="m-0 me-2">Statistiques globales</h5>
                        <small class="text-muted">{{ number_format($totalBalance + $totalWithdrawals, 0, ',', ' ') }} Total Transactions</small>
                    </div>
                    <div class="dropdown">
                        <button class="btn p-0" type="button" id="orederStatistics" data-bs-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="orederStatistics">
                            <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
                            <a class="dropdown-item" href="javascript:void(0);">Share</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row justify-content-between align-items-center mb-3">
                        <div class="col-md-6 d-flex flex-column align-items-start gap-1">
                            <h2 class="mb-2">{{ number_format($totalBalance + $totalWithdrawals, 0, ',', ' ') }}</h2>
                            <span>Total Transactions</span>
                        </div>
                        <div class="col-md-6">
                            <div id="orderStatisticsChartNew"></div>
                        </div>
                    </div>
                    <ul class="p-0 m-0">
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-primary"><i
                                        class="bx bx-mobile-alt"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <h6 class="mb-0">Profit</h6>
                                    <small class="text-muted">Adhésion + Coût retrait</small>
                                </div>
                                <div class="user-progress">
                                    <small class="fw-semibold">{{ number_format($totalBalance, 0, ',', ' ') }}</small>
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-danger"><i class="bx bx-home-alt"></i></span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <h6 class="mb-0">Retraits</h6>
                                    <small class="text-muted">Total retrais journaliers</small>
                                </div>
                                <div class="user-progress">
                                    <small class="fw-semibold">{{ number_format($totalWithdrawals, 0, ',', ' ') }}</small>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!--/ Order Statistics -->

        <!-- Transactions -->
        <div class="col-md-6 order-2 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title m-0 me-2">Dernières opérations</h5>
                    <small class="text-muted">Dernières 5 entrées/sorties</small>
                </div>
                <div class="card-body">
                    <ul class="p-0 m-0">
                        @forelse ($latestTransactions as $transaction)
                            <li class="d-flex mb-4 pb-1">
                                <div class="avatar flex-shrink-0 me-3">
                                    <!-- Icônes selon le type -->
                                    @switch($transaction['type'])
                                        @case('Adhésion')
                                            <img src="{{ asset('assets/img/icons/unicons/wallet-info.png') }}" alt="Adhésion"
                                                class="rounded" />
                                        @break
                                        @case('Contribution')
                                            <img src="{{ asset('assets/img/icons/unicons/chart-success.png') }}" alt="Contribution"
                                                class="rounded" />
                                        @break
                                        @case('Retrait')
                                            <img src="{{ asset('assets/img/icons/unicons/paypal.png') }}" alt="Retrait"
                                                class="rounded" />
                                        @break
                                        @default
                                            <img src="{{ asset('assets/img/icons/unicons/cc-warning.png') }}" alt="Autres"
                                                class="rounded" />
                                    @endswitch
                                </div>

                                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                    <div class="me-2">
                                        <small class="text-muted d-block mb-1">{{ $transaction['reference_type'] }}</small>
                                        <h6 class="mb-0">{{ $transaction['type'] }}</h6>
                                    </div>

                                    <div class="user-progress d-flex align-items-center gap-1">
                                        <h6 class="mb-0">
                                            @if ($transaction['direction'] === 'in')
                                                +{{ number_format($transaction['amount'], 0, ',', '.') }} FC
                                            @else
                                                -{{ number_format($transaction['amount'], 0, ',', '.') }} FC
                                            @endif
                                        </h6>
                                        <span class="text-muted">FC</span>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted">Aucune transaction récente</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <!--/ Transactions -->
    </div>
</div>

<!-- Graphique -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const reports = window.reports;

        const categories = reports.map(item => item.mois); // Exemple: '2024-02', '2024-03', etc.
        const adhesions = reports.map(item => item.adhesions);
        const contributions = reports.map(item => item.contributions);
        const retraits = reports.map(item => item.retraits);

        const options = {
            series: [
                {
                    name: 'Adhésions',
                    data: adhesions
                },
                {
                    name: 'Contributions',
                    data: contributions
                },
                {
                    name: 'Retraits',
                    data: retraits
                }
            ],
            chart: {
                type: 'bar',
                height: 350
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    borderRadius: 5,
                    borderRadiusApplication: 'end'
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: categories, // Les mois issus de la base
                title: {
                    text: 'Mois'
                }
            },
            yaxis: {
                title: {
                    text: 'Montant (CDF)'
                }
            },
            fill: {
                opacity: 1
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val.toLocaleString('fr-CD') + ' CDF';
                    }
                }
            },
            colors: ['#00cfe8', '#7367f0', '#ff9f43'],
            legend: {
                position: 'top'
            }
        };

        const chart = new ApexCharts(document.querySelector("#totalRevenueChartNew"), options);
        chart.render();


    });
</script>


<script>
document.addEventListener("DOMContentLoaded", function () {
    const chartData = window.monthlyPieChart;

    var options = {
        series: chartData.data,
        labels: chartData.labels,
        chart: {
            type: 'pie',
            height: 600
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 300
                },
                legend: {
                    position: 'bottom'
                }
            }
        }],
        legend: {
            position: 'right',
            offsetY: 0,
            height: 230,
        }
    };

    const chart = new ApexCharts(document.querySelector("#orderStatisticsChartNew"), options);
    chart.render();
});
</script>




<script>
    document.addEventListener("DOMContentLoaded", function () {
        const reports = window.reports;
        const soldes = reports.map(item => item.solde);
        const categories = reports.map(item => item.mois);

        const optionsGrowth = {
            chart: {
                type: 'area',
                height: 100,
                sparkline: { enabled: true }
            },
            series: [{
                name: 'Solde',
                data: soldes
            }],
            stroke: {
                curve: 'smooth',
                width: 2
            },
            colors: ['#28C76F'],
            tooltip: {
                x: {
                    formatter: function(val, opts) {
                        return categories[opts.dataPointIndex];
                    }
                },
                y: {
                    formatter: function(val) {
                        return val.toLocaleString('fr-CD') + ' CDF';
                    }
                }
            }
        };

        const growthChart = new ApexCharts(document.querySelector("#growthChart"), optionsGrowth);
        growthChart.render();
    });
</script>
