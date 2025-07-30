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
                                        {{ __('Situation de la caisse centrale') }}</h1>
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
    <div class="row">
        @foreach ($registers as $index => $reg)
            @if ($index == 0)
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <img src="../assets/img/icons/unicons/chart-success.png" alt="chart success"
                                        class="rounded" />
                                </div>

                            </div>
                            <span>Compte USD</span>
                            <h3 class="card-title mb-2">{{ $reg->currency }} : {{ number_format($reg->balance, 2) }}
                            </h3>
                            <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i>
                                +72.80%</small>
                        </div>
                    </div>
                </div>
            @endif
            @if ($index == 1)
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar flex-shrink-0">
                                    <img src="../assets/img/icons/unicons/wallet-info.png" alt="Credit Card"
                                        class="rounded" />
                                </div>

                            </div>
                            <span>Compte CDF</span>
                            <h3 class="card-title text-nowrap mb-1">{{ $reg->currency }} :
                                {{ number_format($reg->balance, 2) }}</h3>
                            <small class="text-success fw-semibold"><i class="bx bx-up-arrow-alt"></i>
                                +28.42%</small>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>


    <div class="table-wrapper">
        <div class="card has-actions has-filter">

            <div class="card-header">
                <div class="w-100 justify-content-between d-flex flex-wrap align-items-center gap-1">

                    <div class="d-flex flex-wrap flex-md-nowrap align-items-center gap-1">

                        <div class="table-search-input">
                            <div class="input-group input-group-merge">
                                <span class="input-group-text" id="basic-addon-search31">
                                    <i class="icon-base bx bx-search"></i></span>
                                <input type="search" wire:model.live="search" class="form-control"
                                    placeholder="Rechercher......." autocomplete="off" aria-label="Rechercher......."
                                    aria-describedby="basic-addon-search31">
                            </div>
                        </div>
                    </div>
                    @can('ajouter-sortie-caisse')
                        <div class="d-flex align-items-center gap-1">
                            <button wire:click="openModal" class="btn btn-primary">
                                + Ajouter
                            </button>
                            <a href="{{ route('cash.register.export.pdf') }}"
                                class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-medium
                                ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2
                                focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none
                                disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground
                                h-9 rounded-md px-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-download mr-2 h-4 w-4">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" x2="12" y1="15" y2="3"></line>
                                </svg>
                                Télécharger PDF
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
                                            <span
                                                class="badge bg-label-success me-1">{{ ucfirst($transaction->type) }}</span>
                                        @elseif ($transaction->type === 'virement vers caisse centrale')
                                            <span
                                                class="badge bg-label-info me-1">{{ ucfirst($transaction->type) }}</span>
                                        @else
                                            <span
                                                class="badge bg-label-danger me-1">{{ ucfirst($transaction->type) }}</span>
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
