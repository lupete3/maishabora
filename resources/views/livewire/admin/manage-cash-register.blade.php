<!-- resources/views/livewire/manage-cash-register.blade.php -->
<div>
    <div class="page-header d-print-none">
        <div class="">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a class="mb-0 d-inline-block fs-6 lh-1"
                                        href="{{ route('dashboard') }}">{{ __('Tableau de bord') }}</a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    <h1 class="mb-0 d-inline-block fs-6 lh-1">
                                        {{ __('Situation de la caisse centrale') }}
                                    </h1>
                                </li>
                            </ol>
                        </nav>

                    </div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-2">
        @foreach ($registers as $index => $reg)
            <div class="col-md-6 mb-4">
                <div class="card bg-{{ $reg->currency == 'USD' ? 'primary' : 'info' }} text-white overflow-hidden"
                    style="height: 100%">
                    <div class="card-body position-relative">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <span class="avatar-initial rounded">
                                    <i class="tf-icons bx {{ $reg->currency == 'USD' ? 'bx-dollar' : 'bx-money' }} bx-md text-white"></i>
                                </span>
                            </div>
                            <span class="badge text-white">Solde Actuel</span>
                        </div>
                        <h5 class="card-title text-white opacity-75 mb-1">Caisse {{ $reg->currency }}</h5>
                        <h2 class="text-white mb-0">{{ number_format($reg->balance, 2) }} {{ $reg->currency }}</h2>

                        <!-- Background Pattern Deco -->
                        <div class="position-absolute end-0 bottom-0 opacity-10" style="pointer-events: none;">
                            <i class="tf-icons bx bx-wallet text-white" style="font-size: 80px; transform: translate(15px, 15px)"></i>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- START OF MONTHLY OVERVIEW -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm ">
                <div class="card-header border-0 py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0 fw-bold text-primary"><i
                                class="bx bx-calendar-event me-2"></i>Performance Mensuelle</h5>
                        <small class="text-muted">Résumé cumulé du mois en cours</small>
                    </div>
                    <ul class="nav nav-pills card-header-pills" id="pills-tab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active btn-sm" id="pills-usd-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-usd" type="button" role="tab">USD</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link btn-sm" id="pills-cdf-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-cdf" type="button" role="tab">CDF</button>
                        </li>
                    </ul>
                </div>
                <div class="card-body pt-0">
                    <hr class="mt-0 mb-4 opacity-25">
                    <div class="tab-content" id="pills-tabContent">
                        <!-- TAB USD -->
                        <div class="tab-pane fade show active" id="pills-usd" role="tabpanel">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card bg-label-success border-0 shadow-none h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="avatar bg-success p-2 rounded me-3">
                                                    <i class="tf-icons bx bx-up-arrow-alt text-white fs-4"></i>
                                                </div>
                                                <span class="fw-semibold text-success">Total Entrées</span>
                                            </div>
                                            <h3 class="mb-0 fw-bold">
                                                {{ number_format($this->monthlyStats['USD']['in'] ?? 0, 2) }} USD</h3>
                                            <small class="text-muted">Dépôts & Virements entrants</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-label-danger border-0 shadow-none h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="avatar bg-danger p-2 rounded me-3">
                                                    <i class="tf-icons bx bx-down-arrow-alt text-white fs-4"></i>
                                                </div>
                                                <span class="fw-semibold text-danger">Total Sorties</span>
                                            </div>
                                            <h3 class="mb-0 fw-bold">
                                                {{ number_format($this->monthlyStats['USD']['out'] ?? 0, 2) }} USD</h3>
                                            <small class="text-muted">Crédits, Paies & Sorties</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-label-primary border-0 shadow-none h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="avatar bg-primary p-2 rounded me-3">
                                                    <i class="tf-icons bx bx-wallet text-white fs-4"></i>
                                                </div>
                                                <span class="fw-semibold text-primary">Solde Net</span>
                                            </div>
                                            <h3
                                                class="mb-0 fw-bold {{ ($this->monthlyStats['USD']['net'] ?? 0) >= 0 ? 'text-primary' : 'text-danger' }}">
                                                {{ number_format($this->monthlyStats['USD']['net'] ?? 0, 2) }} USD
                                            </h3>
                                            <small class="text-muted">Flux net du mois</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB CDF -->
                        <div class="tab-pane fade" id="pills-cdf" role="tabpanel">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="card bg-label-success border-0 shadow-none h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="avatar bg-success p-2 rounded me-3">
                                                    <i class="tf-icons bx bx-up-arrow-alt text-white fs-4"></i>
                                                </div>
                                                <span class="fw-semibold text-success">Total Entrées</span>
                                            </div>
                                            <h3 class="mb-0 fw-bold">
                                                {{ number_format($this->monthlyStats['CDF']['in'] ?? 0, 0) }} CDF</h3>
                                            <small class="text-muted">Dépôts & Virements entrants</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-label-danger border-0 shadow-none h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="avatar bg-danger p-2 rounded me-3">
                                                    <i class="tf-icons bx bx-down-arrow-alt text-white fs-4"></i>
                                                </div>
                                                <span class="fw-semibold text-danger">Total Sorties</span>
                                            </div>
                                            <h3 class="mb-0 fw-bold">
                                                {{ number_format($this->monthlyStats['CDF']['out'] ?? 0, 0) }} CDF</h3>
                                            <small class="text-muted">Crédits, Paies & Sorties</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-label-primary border-0 shadow-none h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="avatar bg-primary p-2 rounded me-3">
                                                    <i class="tf-icons bx bx-wallet text-white fs-4"></i>
                                                </div>
                                                <span class="fw-semibold text-primary">Solde Net</span>
                                            </div>
                                            <h3
                                                class="mb-0 fw-bold {{ ($this->monthlyStats['CDF']['net'] ?? 0) >= 0 ? 'text-primary' : 'text-danger' }}">
                                                {{ number_format($this->monthlyStats['CDF']['net'] ?? 0, 1) }} CDF
                                            </h3>
                                            <small class="text-muted">Flux net du mois</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END OF MONTHLY OVERVIEW -->


    <div class="table-wrapper">
        <div class="card has-actions has-filter">

            <div class="card-header">
                <div class="w-100 justify-content-between d-flex flex-wrap align-items-center gap-1">

                    <div class="d-flex flex-wrap flex-md-nowrap align-items-center gap-2">
                        <div class="table-search-input">
                            <div class="input-group input-group-merge">
                                <span class="input-group-text" id="basic-addon-search31">
                                    <i class="icon-base bx bx-search"></i></span>
                                <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                                    placeholder="Rechercher......." autocomplete="off" aria-label="Rechercher......."
                                    aria-describedby="basic-addon-search31">
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2 flex-grow-1 flex-sm-grow-0">
                            <input type="date" wire:model.live="startDate"
                                class="form-control form-control-sm flex-fill" style="min-width: 130px;">
                            <span class="text-muted small">au</span>
                            <input type="date" wire:model.live="endDate" class="form-control form-control-sm flex-fill"
                                style="min-width: 130px;">
                        </div>
                    </div>
                    @can('ajouter-sortie-caisse')
                        <div class="d-flex align-items-center gap-1">
                            <a href="{{ route('cash.register.export.pdf', ['startDate' => $startDate, 'endDate' => $endDate, 'search' => $search, 'format' => 'pdf']) }}"
                                class="btn btn-outline-danger btn-sm">
                                <i class="bx bxs-file-pdf me-1"></i> PDF
                            </a>
                            <a href="{{ route('cash.register.export.pdf', ['startDate' => $startDate, 'endDate' => $endDate, 'search' => $search, 'format' => 'excel']) }}"
                                class="btn btn-outline-success btn-sm">
                                <i class="bx bxs-file-export me-1"></i> Excel
                            </a>
                        </div>
                    @endcan
                </div>
            </div>

            <div class="card-table">
                <div class="table-responsive table-has-actions table-has-filter">
                    <table
                        class="table card-table table-vcenter table-striped table-hover dataTable no-footer dtr-inline collapsed">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Devise</th>
                                <th>Montant</th>
                                <th>Solde après</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if ($transaction->type === 'Entrée de fonds')
                                            <span class="badge bg-label-success me-1">{{ ucfirst($transaction->type) }}</span>
                                        @elseif ($transaction->type === 'virement vers caisse centrale')
                                            <span class="badge bg-label-info me-1">{{ ucfirst($transaction->type) }}</span>
                                        @else
                                            <span class="badge bg-label-danger me-1">{{ ucfirst($transaction->type) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $transaction->currency }}</td>
                                    <td>{{ number_format($transaction->amount, 2) }}</td>
                                    <td>{{ number_format($transaction->balance_after, 2) }}</td>
                                    <td>{{ $transaction->description }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="alert alert-danger" role="alert">
                                            Aucune opération trouvée.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <div class="d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <label>
                            <select wire:model.lazy="perPage" class="form-select form-select-sm">
                                <option value="10">10</option>
                                <option value="30">30</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="999999">Tous</option>
                            </select>
                        </label>
                    </div>
                    <div class="text-muted">
                        Affichage de {{ $transactions->firstItem() }} à {{ $transactions->lastItem() }} sur
                        <span class="badge bg-primary">{{ $transactions->total() }}</span> opérations
                    </div>
                </div>

                <div class="d-flex justify-content-center">
                    {{ $transactions->links() }}
                </div>
            </div>

        </div>
    </div>

    @include('livewire.admin.add-cash-register')


    <div class="row mt-3">
        <div class="col-md-12">
            <livewire:currency-conversion />
        </div>
        <div class="col-md-12 mt-4">
            <livewire:admin.exchange-rate-manager />
        </div>
    </div>
</div>
