<div class="">
    <h4 class="mb-3">📊 Rapport Dépôts & Retraits</h4>

    <div class="row g-3 align-items-center mb-4">
        <div class="col-md-3">
            <label class="form-label">Filtrer par</label>
            <select wire:model.lazy="filterType" class="form-select">
                <option value="today">Aujourd’hui</option>
                <option value="week">Cette semaine</option>
                <option value="month">Ce mois</option>
                <option value="year">Cette année</option>
                <option value="custom">Intervalle</option>
            </select>
        </div>
        @if ($filterType === 'custom')
            <div class="col-md-3">
                <label class="form-label">Date de début</label>
                <input wire:model.lazy="startDate" type="date" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date de fin</label>
                <input wire:model.lazy="endDate" type="date" class="form-control">
            </div>
        @endif
        <div class="col-md-3">
            <label for="currency">Devise</label>
            <select wire:model.lazy="currency" class="form-control">
                <option value="">Toutes</option>
                <option value="USD">USD</option>
                <option value="CDF">CDF</option>
            </select>
        </div>

        <div class="col-md-4">
            <label for="user_id">Membre</label>
            <div class="table-search-input">
                <div class="input-group input-group-merge">
                    <span class="input-group-text" id="basic-addon-search31"><i
                            class="icon-base bx bx-search"></i></span>
                    <input type="search" wire:model.live="search" class="form-control" placeholder="Rechercher..."
                        aria-label="Rechercher..." aria-describedby="basic-addon-search31">
                </div>
            </div>
            @if (!empty($results))
                <ul class="list-group w-100" style="z-index: 1000;">
                    @foreach ($results as $user)
                        <li class="list-group-item list-group-item-action"
                            wire:click="selectResult({{ $user['id'] }})">
                            {{ "{$user['code']} {$user['name']} {$user['postnom']}" }}
                        </li>
                    @endforeach
                </ul>
            @endif
            @error('member_id')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white">Total Dépôts</h6>
                        <h4 class="mb-0">{{ number_format($deposits, 2) }}</h4>
                    </div>
                    <i class="bx bx-down-arrow-circle bx-lg"></i>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-danger text-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white">Total Retraits</h6>
                        <h4 class="mb-0">{{ number_format($withdrawals, 2) }}</h4>
                    </div>
                    <i class="bx bx-up-arrow-circle bx-lg"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Nom du membre</th>
                        <th>Type</th>
                        <th>Montant</th>
                        <th>Devise</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $transaction->user->name . ' ' . $transaction->user->postnom . ' ' . $transaction->user->prenom ?? '-' }}
                            </td>
                            <td>
                                @if (in_array($transaction->type, [
                                        'dépôt',
                                        'depot',
                                        'mise_quotidienne',
                                        'virement_caisse_entrant',
                                        'remboursement_de_credit',
                                        'ocroit_de_credit',
                                    ]))
                                    <span class="badge bg-success">
                                        <i class="bi bi-arrow-down-circle-fill me-1"></i>
                                        {{ ucfirst($transaction->type) }}
                                    </span>
                                @elseif(in_array($transaction->type, ['retrait', 'retrait_carte_adhesion']))
                                    <span class="badge bg-danger">
                                        <i class="bi bi-arrow-up-circle-fill me-1"></i>
                                        {{ ucfirst($transaction->type) }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-info-circle me-1"></i> {{ ucfirst($transaction->type) }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if (in_array($transaction->type, ['retrait', 'retrait_carte_adhesion']))
                                    <span
                                        class="text-danger">-{{ number_format($transaction->amount, 2, ',', ' ') }}</span>
                                @else
                                    <span
                                        class="text-success">+{{ number_format($transaction->amount, 2, ',', ' ') }}</span>
                                @endif
                            </td>
                            <td>{{ $transaction->currency }}</td>
                            <td>{{ $transaction->description ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">Aucune transaction trouvée</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="m-3">
            {{ $transactions->links() }}
        </div>
    </div>
</div>
