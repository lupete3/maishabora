<!-- resources/views/livewire/agent-dashboard.blade.php -->
<div class=" mt-4">
    <div class="row g-4">

        <!-- Soldes -->
        @foreach($agentAccounts as $agent)
            <div class="col-md-4 order-2">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title m-0 me-2">Agent : {{ $agent->name.' '.$agent->postnom }}</h5>
                        <div class="dropdown">
                            <button class="btn p-0" type="button" id="transactionID" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="transactionID">
                                <a class="dropdown-item" href="javascript:void(0);" wire:click='showTransactions({{ $agent->id }}, "day")'>
                                    Voir les opérations (Aujourd'hui)
                                </a>
                                <a class="dropdown-item" href="javascript:void(0);" wire:click='showTransactions({{ $agent->id }}, "month")'>
                                    Ce Mois
                                </a>
                                <a class="dropdown-item" href="javascript:void(0);" wire:click='showTransactions({{ $agent->id }}, "year")'>
                                    Cette Année
                                </a>
                            </div>

                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="p-0 m-0">
                            @foreach($agent->agentAccounts as $index => $acc)
                            <li class="d-flex mb-4 pb-1">
                                <div class="avatar flex-shrink-0 me-3">
                                    <img src="../assets/img/icons/unicons/{{ $index == 0 ? 'wallet' : 'cc-warning' }}.png" alt="User" class="rounded">
                                </div>
                                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
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
        <div class="col-md-6 mt-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title m-0 me-2">{{ __('Historique des opérations du compte du jour') }}</h5>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @forelse($transactions as $t)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ ucfirst($t->type) }}</strong><br>
                                        <small>{{ $t->currency }} - {{ number_format($t->amount, 2) }}</small>
                                    </div>
                                    <span class="badge bg-secondary">
                                        {{ \Carbon\Carbon::parse($t->created_at)->format('d-m-Y H:i') }}
                                    </span>
                                </li>
                            @empty
                                <div class="alert alert-info">Aucune opération aujourd'hui.</div>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
