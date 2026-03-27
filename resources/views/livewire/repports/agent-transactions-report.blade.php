<div>
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Rapports /</span> Centralisation des Transactions
        Agents</h4>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Filtrer par Agent</label>
                    <select wire:model.live="agentId" class="form-select select2">
                        <option value="">-- Tous les agents --</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}">
                                {{ $agent->name . ' ' . $agent->postnom . ' ' . $agent->prenom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Devise</label>
                    <select wire:model.live="currency" class="form-select">
                        <option value="">-- Toutes --</option>
                        <option value="USD">USD</option>
                        <option value="CDF">CDF</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Période</label>
                    <select wire:model.live="period" class="form-select">
                        <option value="day">Aujourd'hui</option>
                        <option value="week">Cette semaine</option>
                        <option value="month">Ce mois</option>
                        <option value="year">Cette année</option>
                        <option value="interval">Intervalle personnalisé</option>
                    </select>
                </div>
                @if($period === 'interval')
                    <div class="col-md-2">
                        <label class="form-label">Date Début</label>
                        <input type="date" wire:model.live="dateStart" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date Fin</label>
                        <input type="date" wire:model.live="dateEnd" class="form-control">
                    </div>
                @endif
                <div class="col-md-2 d-flex align-items-end">
                    <button wire:click="exportPdf" class="btn btn-danger w-100" wire:loading.attr="disabled">
                        <span wire:loading wire:target="exportPdf" class="spinner-border spinner-border-sm me-1"
                            role="status"></span>
                        <i class="bx bxs-file-pdf me-1"></i> PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Totaux Groupés -->
    <div class="row mb-4">
        @foreach($totals as $curr => $amount)
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0">Total {{ $curr }}</p>
                            <h4 class="text-white mb-0">{{ number_format($amount, 2) }} {{ $curr }}</h4>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-wallet"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Historique des Transactions -->
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">Historique Centralisé des Transactions</h5>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                @forelse($transactions as $t)
                    <div class="list-group-item d-flex align-items-center p-3">
                        <div class="avatar flex-shrink-0 me-3">
                            <span
                                class="avatar-initial rounded bg-label-{{ in_array($t->type, ['deposit', 'rectification_solde_agent']) ? 'success' : 'danger' }}">
                                <i class="bx bx-transfer-alt"></i>
                            </span>
                        </div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2" style="max-width: 60%;">
                                <h6 class="mb-0 fw-bold">{{ $t->user->name ?? 'N/A' }} - {{ strtoupper($t->type) }}</h6>
                                <small class="text-muted d-block">{{ $t->description }}</small>
                                <small class="text-primary mt-1 d-block">
                                    <i class="bx bx-time-five me-1"></i>{{ $t->created_at->format('d/m/Y H:i') }}
                                </small>
                            </div>
                            <div class="user-progress text-end">
                                <h6
                                    class="mb-0 {{ in_array($t->type, ['deposit', 'rectification_solde_agent']) ? 'text-success' : 'text-danger' }}">
                                    {{ in_array($t->type, ['deposit', 'rectification_solde_agent']) ? '+' : '-' }}
                                    {{ number_format($t->amount, 2) }} {{ $t->currency }}
                                </h6>
                                <small class="text-muted">Solde après: {{ number_format($t->balance_after, 2) }}
                                    {{ $t->currency }}</small>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center">
                        <img src="{{ asset('assets/img/illustrations/empty-state.svg') }}" alt="Aucune transaction"
                            width="120" class="mb-3">
                        <p class="text-muted">Aucune transaction trouvée pour les critères sélectionnés.</p>
                    </div>
                @endforelse
            </div>
        </div>
        @if($transactions->hasPages())
            <div class="card-footer">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>