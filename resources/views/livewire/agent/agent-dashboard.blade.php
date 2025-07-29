<!-- resources/views/livewire/agent-dashboard.blade.php -->
<div class=" mt-0">
    <h3> Caisse des agents</h3>
    <div class="row g-4">
        <!-- Soldes -->
        @foreach ($agentAccounts as $agent)
            <div class="col-md-4 order-2">
                <div class="card ">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h6 class="card-title m-0 me-2">Agent : {{ $agent->name . ' ' . $agent->postnom }}</h6>
                        <div class="dropdown">
                            <button class="btn p-0" type="button" id="transactionID" data-bs-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            {{-- <div class="dropdown-menu dropdown-menu-end" aria-labelledby="transactionID">
                                <a class="dropdown-item" href="javascript:void(0);" wire:click='showTransactions({{ $agent->id }}, "day")'>
                                    Voir les opérations (Aujourd'hui)
                                </a>
                                <a class="dropdown-item" href="javascript:void(0);" wire:click='showTransactions({{ $agent->id }}, "month")'>
                                    Ce Mois
                                </a>
                                <a class="dropdown-item" href="javascript:void(0);" wire:click='showTransactions({{ $agent->id }}, "year")'>
                                    Cette Année
                                </a>
                            </div> --}}
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="transactionID">
                                <a class="dropdown-item" href="javascript:void(0);"
                                    wire:click='showTransactions({{ $agent->id }}, "day")'>
                                    Voir les opérations (Aujourd'hui)
                                </a>
                                <a class="dropdown-item" href="javascript:void(0);"
                                    wire:click='showTransactions({{ $agent->id }}, "month")'>
                                    Ce Mois
                                </a>
                                <a class="dropdown-item" href="javascript:void(0);"
                                    wire:click='showTransactions({{ $agent->id }}, "year")'>
                                    Cette Année
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item"
                                    href="{{ route('agent.transactions.export', [$agent->id, 'day']) }}"
                                    target="_blank">
                                    📄 Export PDF (Jour)
                                </a>
                                <a class="dropdown-item"
                                    href="{{ route('agent.transactions.export', [$agent->id, 'month']) }}"
                                    target="_blank">
                                    📄 Export PDF (Mois)
                                </a>
                                <a class="dropdown-item"
                                    href="{{ route('agent.transactions.export', [$agent->id, 'year']) }}"
                                    target="_blank">
                                    📄 Export PDF (Année)
                                </a>
                            </div>

                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="p-0 m-0">
                            @foreach ($agent->agentAccounts as $index => $acc)
                                <li class="d-flex mb-1 pb-1">
                                    <div class="avatar flex-shrink-0 me-3">
                                        <img src="../assets/img/icons/unicons/{{ $index == 0 ? 'wallet' : 'cc-warning' }}.png"
                                            alt="User" class="rounded">
                                    </div>
                                    <div
                                        class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="me-2">
                                            <small class="text-muted d-block mb-1">Solde</small>
                                            <h6 class="mb-0">{{ $acc->currency }}</h6>
                                        </div>
                                        <div class="user-progress d-flex align-items-center gap-1">
                                            <h6 class="mb-0">{{ number_format($acc->balance, 2) }}</h6>

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

    <div class="row">

        <!-- Opérations du jour -->
        @if ($transactions)
            <div class="col-md-12 mt-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title m-0 me-2">{{ __('Historique des opérations du compte du jour') }}</h5>
                        <div class="row">
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
                                                            <div class="user-status">
                                                                <span class="badge badge-dot bg-success"></span>
                                                                <small>{{ $t->description }}</small>
                                                            </div>
                                                        </div>
                                                        <div class="add-btn">
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
                                <div class="alert alert-info">Aucune opération aujourd'hui.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
