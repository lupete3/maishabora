<!-- resources/views/livewire/agent/agent-dashboard.blade.php -->
<div class="mt-4">
    @include('livewire.agent.partials.modals-management')

    <h3>Caisse des agents</h3>
    <div class="row g-4 mt-2">
        <!-- Soldes -->
        @foreach ($agentAccounts as $agent)
            <div class="col-md-4 order-2">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h6 class="card-title m-0 me-2">
                            Agent : {{ $agent->name . ' ' . $agent->postnom }}
                        </h6>

                        <div class="dropdown">
                            <button class="btn p-0" type="button" id="dropdown-{{ $agent->id }}" data-bs-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown-{{ $agent->id }}">
                                <a class="dropdown-item" href="javascript:void(0);"
                                    wire:click='showTransactions({{ $agent->id }}, "day")'>Aujourd'hui</a>
                                <a class="dropdown-item" href="javascript:void(0);"
                                    wire:click='showTransactions({{ $agent->id }}, "week")'>Cette semaine</a>
                                <a class="dropdown-item" href="javascript:void(0);"
                                    wire:click='showTransactions({{ $agent->id }}, "month")'>Ce mois</a>
                                <a class="dropdown-item" href="javascript:void(0);"
                                    wire:click='showTransactions({{ $agent->id }}, "year")'>Cette année</a>
                                <a class="dropdown-item" href="javascript:void(0);"
                                    wire:click='showIntervalModal({{ $agent->id }})'>Intervalle</a>
                            </div>

                        </div>
                    </div>

                    <!-- Affichage soldes -->
                    <div class="card-body">
                        <ul class="p-0 m-0">
                            @foreach ($agent->agentAccounts as $index => $acc)
                                <li class="d-flex mb-1 pb-1">
                                    <div class="avatar flex-shrink-0 me-3">
                                        <img src="../assets/img/icons/unicons/{{ $index == 0 ? 'wallet' : 'cc-warning' }}.png"
                                            alt="User" class="rounded">
                                    </div>

                                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="me-2">
                                            <small class="text-muted d-block mb-1">Solde</small>
                                            <h6 class="mb-0">{{ $acc->currency }}</h6>
                                        </div>

                                        <div class="user-progress d-flex align-items-center gap-1">
                                            <h6 class="mb-0">{{ number_format($acc->balance, 2) }}</h6>

                                            @can('modifier-solde-compte')
                                                <button type="button" wire:click="confirmUpdateBalance({{ $acc->id }})"
                                                    class="btn btn-xs btn-outline-primary ms-1" title="Modifier le solde">
                                                    <i class="bx bx-edit-alt"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- HISTORIQUE DES TRANSACTIONS -->
    @if ($isShowTransaction)
        @if ($filter === 'custom')
            <div class="card p-3 mt-4">
                <h5>Filtrer par intervalle personnalisé</h5>
                <div class="mt-4 row g-3">
                    <div class="col-md-4 col-lg-2">
                        <label for="">Date de début</label>
                        <input type="date" class="form-control" wire:model.lazy="startDate">
                    </div>
                    <div class="col-md-4 col-lg-2">
                        <label for="">Date de fin</label>
                        <input type="date" class="form-control" wire:model.lazy="endDate">
                    </div>
                    <div class="col-md-3 col-lg-2 align-self-end">
                        <button class="btn btn-primary w-100" wire:click="applyCustomFilter" wire:loading.attr="disabled">
                            <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Filtrer</button>
                    </div>
                </div>
            </div>
        @endif
        <div class="row mt-4">
            @if ($transactions)
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="m-0">Historique des transactions</h5>
                                    <small class="text-muted">{{ $periodLabel }}</small>
                                    <p>
                                        Nombre total de transactions :
                                        <strong>{{ $transactionCount }}</strong>
                                    </p>
                                    @foreach ($totalByCurrency as $currency => $total)
                                        <div>
                                            <strong>{{ $currency }} :</strong>
                                            {{ number_format($total, 2) }}
                                        </div>
                                    @endforeach
                                </div>

                                <button wire:click="exportPDF" class="btn btn-sm @if ($filter === 'year')
                                    btn-success
                                @else btn-danger @endif" wire:loading.attr="disabled">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" wire:loading></span>
                                    <i class="bx bx-file me-1"></i> Exporter
                                    @if ($filter === 'year')
                                        EXCEL
                                    @else PDF @endif
                                </button>
                            </div>
                            <div class="row mt-3">
                                @forelse($transactions as $t)
                                    <div class="col-12 mb-6 mb-xl-0">
                                        <div class="demo-inline-spacing mt-2">
                                            <div class="list-group">
                                                <div
                                                    class="list-group-item list-group-item-action d-flex align-items-center cursor-pointer">
                                                    <div class="w-100">
                                                        <div class="d-flex justify-content-between">
                                                            <div class="user-info">
                                                                <h6 class="mb-1">{{ ucfirst($t->type) }}</h6>

                                                                <small>{{ $t->currency }} -
                                                                    {{ number_format($t->amount, 2) }}</small>
                                                                <br>
                                                                <small>Solde Après :
                                                                    {{ $t->currency }} -
                                                                    {{ number_format($t->balance_after, 2) }}</small>

                                                                <div class="user-status mt-1">
                                                                    <span class="badge badge-dot bg-success"></span>
                                                                    <small>{{ $t->description }}</small>
                                                                </div>
                                                            </div>

                                                            <div class="add-btn d-flex align-items-center gap-2">
                                                                @can('modifier-solde-compte')
                                                                    <button type="button"
                                                                        wire:click="confirmEditTransaction({{ $t->id }})"
                                                                        class="btn btn-sm btn-icon btn-outline-primary"
                                                                        title="Modifier la transaction">
                                                                        <i class="bx bx-edit"></i>
                                                                    </button>
                                                                    <button type="button"
                                                                        wire:click="confirmDeleteTransaction({{ $t->id }})"
                                                                        class="btn btn-sm btn-icon btn-outline-danger"
                                                                        title="Supprimer la transaction">
                                                                        <i class="bx bx-trash"></i>
                                                                    </button>
                                                                @endcan
                                                                <span class="badge bg-secondary">
                                                                    {{ \Carbon\Carbon::parse($t->created_at)->format('d-m-Y') }}
                                                                    <br><br>
                                                                    {{ \Carbon\Carbon::parse($t->created_at)->format('H:i') }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="alert alert-info">Aucune opération trouvée.</div>
                                @endforelse
                            </div>
                        </div>

                    </div>
                </div>
            @endif
        </div>
    @endif

    @can('afficher-tableaudebord-recouvreur')
        <div class="mb-4 mt-4" wire:ignore>
            <livewire:credit.credit-overview wire:key="credit-overview" />
        </div>
    @endcan
</div>