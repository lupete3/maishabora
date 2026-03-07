<div>
    <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Rapports /</span> Dépôts & Retraits</h4>

    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Filtrer par période</label>
                    <select wire:model.live="filterType" class="form-select">
                        <option value="today">Aujourd’hui</option>
                        <option value="week">Cette semaine</option>
                        <option value="month">Ce mois</option>
                        <option value="year">Cette année</option>
                        <option value="custom">Intervalle personnalisé</option>
                    </select>
                </div>

                @if ($filterType === 'custom')
                    <div class="col-md-2">
                        <label class="form-label">Date Début</label>
                        <input wire:model.live="dateStart" type="date" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date Fin</label>
                        <input wire:model.live="dateEnd" type="date" class="form-control">
                    </div>
                @endif

                <div class="col-md-2">
                    <label class="form-label">Devise</label>
                    <select wire:model.live="currency" class="form-select">
                        <option value="">Toutes</option>
                        <option value="USD">USD</option>
                        <option value="CDF">CDF</option>
                    </select>
                </div>

                <div class="col-md-4 position-relative">
                    <label class="form-label">Rechercher Membre</label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                            placeholder="Nom, code ou téléphone...">
                    </div>
                    @if (!empty($results))
                        <div class="position-absolute w-100 shadow-lg bg-white rounded-bottom"
                            style="z-index: 1050; top: 100%;">
                            <ul class="list-group list-group-flush">
                                @foreach ($results as $user)
                                    <li class="list-group-item list-group-item-action cursor-pointer"
                                        wire:click="selectResult({{ $user['id'] }})">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-xs me-2">
                                                <span class="avatar-initial rounded-circle bg-label-primary"><i
                                                        class="bx bx-user"></i></span>
                                            </div>
                                            <div>
                                                <small class="fw-bold d-block text-primary">{{ $user['code'] }}</small>
                                                <span
                                                    class="small">{{ "{$user['name']} {$user['postnom']} {$user['prenom']}" }}</span>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Totaux -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-success border-1">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Dépôts</p>
                            <h3 class="mb-0 text-success fw-bold">{{ number_format($deposits, 2) }} <small
                                    class="text-muted h6 mb-0">{{ $currency ?: 'Devise mix' }}</small></h3>
                        </div>
                        <div class="avatar avatar-md">
                            <span class="avatar-initial rounded bg-label-success shadow-sm">
                                <i class="bx bx-down-arrow-alt bx-sm"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button wire:click="exportDepositsPdf" class="btn btn-xs btn-outline-success">
                            <i class="bx bxs-file-pdf me-1"></i> PDF
                        </button>
                        <button wire:click="exportDepositsExcel" class="btn btn-xs btn-outline-success">
                            <i class="bx bxs-spreadsheet me-1"></i> Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-danger border-1">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Retraits</p>
                            <h3 class="mb-0 text-danger fw-bold">{{ number_format($withdrawals, 2) }} <small
                                    class="text-muted h6 mb-0">{{ $currency ?: 'Devise mix' }}</small></h3>
                        </div>
                        <div class="avatar avatar-md">
                            <span class="avatar-initial rounded bg-label-danger shadow-sm">
                                <i class="bx bx-up-arrow-alt bx-sm"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button wire:click="exportWithdrawalsPdf" class="btn btn-xs btn-outline-danger">
                            <i class="bx bxs-file-pdf me-1"></i> PDF
                        </button>
                        <button wire:click="exportWithdrawalsExcel" class="btn btn-xs btn-outline-danger">
                            <i class="bx bxs-spreadsheet me-1"></i> Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Historique -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Détails des Mouvements</h5>
            <span class="badge bg-label-primary">{{ $transactions->total() }} transactions</span>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Date & Heure</th>
                        <th>Membre</th>
                        <th>Type</th>
                        <th class="text-end">Montant</th>
                        <th>Devise</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($transactions as $t)
                        <tr>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold">{{ $t->created_at->format('d/m/Y') }}</span>
                                    <small class="text-muted">{{ $t->created_at->format('H:i') }}</small>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-xs me-2">
                                        <span class="avatar-initial rounded-circle bg-label-secondary"><i
                                                class="bx bx-user bx-xs"></i></span>
                                    </div>
                                    <span>{{ $t->user->name . ' ' . $t->user->postnom }}</span>
                                </div>
                            </td>
                            <td>
                                @php
                                    $isIncome = in_array($t->type, ['dépôt', 'depot', 'mise_quotidienne', 'virement_caisse_entrant', 'remboursement_de_credit']);
                                    $isOutcome = in_array($t->type, ['retrait', 'retrait_carte_adhesion']);
                                @endphp
                                <span
                                    class="badge bg-label-{{ $isIncome ? 'success' : ($isOutcome ? 'danger' : 'secondary') }} py-1">
                                    <i
                                        class="bx {{ $isIncome ? 'bx-trending-up' : ($isOutcome ? 'bx-trending-down' : 'bx-info-circle') }} bx-xs me-1"></i>
                                    {{ ucfirst(str_replace('_', ' ', $t->type)) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold {{ $isIncome ? 'text-success' : ($isOutcome ? 'text-danger' : '') }}">
                                    {{ $isOutcome ? '-' : '+' }} {{ number_format($t->amount, 2) }}
                                </span>
                            </td>
                            <td><span class="badge bg-label-primary px-2">{{ $t->currency }}</span></td>
                            <td>
                                <small class="text-muted text-wrap" style="max-width: 200px; display: block;">
                                    {{ $t->description ?? '-' }}
                                </small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted mt-2">Aucune transaction trouvée pour ces critères</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div class="card-footer p-2">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>