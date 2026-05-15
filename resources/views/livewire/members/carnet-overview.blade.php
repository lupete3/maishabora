<div>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <h4 class="mb-0">Overview des Carnets (Anomalies)</h4>
        <div class="d-flex flex-wrap align-items-center gap-2 w-100 w-md-auto">
            <div class="input-group input-group-merge w-100 w-sm-auto" style="min-width: 250px;">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input wire:model.live.debounce.300ms="search" type="text" class="form-control"
                    placeholder="Nom, Postnom ou Code membre...">
            </div>
            <a href="{{ route('members.overview-carnets.pdf', ['search' => $search]) }}" target="_blank"
                class="btn btn-outline-danger ms-auto ms-md-0">
                <i class="bx bxs-file-pdf me-1"></i> PDF
            </a>
        </div>
    </div>

    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <span class="badge badge-center rounded-pill bg-warning me-2"><i class="bx bx-error"></i></span>
        <div>
            Cette liste affiche les carnets actifs dont le <strong>montant total épargné</strong> dépasse le
            <strong>solde disponible</strong> dans le compte membre (Épargne ou Courant).
        </div>
    </div>

    {{-- ============ KPI CARDS ============ --}}
    <div class="row g-4 mb-4">

        {{-- Total carnets en anomalie --}}
        <div class="col-sm-6 col-xl-2">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge rounded-pill bg-label-danger p-2 me-2">
                            <i class="bx bx-error-circle fs-4"></i>
                        </span>
                        <small class="text-muted">Carnets en anomalie</small>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $totalCount }}</h3>
                </div>
            </div>
        </div>

        {{-- Total déposé USD --}}
        <div class="col-sm-6 col-xl-2">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge rounded-pill bg-label-primary p-2 me-2">
                            <i class="bx bx-dollar fs-4"></i>
                        </span>
                        <small class="text-muted">Déposé USD</small>
                    </div>
                    <h5 class="mb-0 fw-bold text-primary">{{ number_format($totalSavedUSD, 2) }} <small
                            class="fs-6">USD</small></h5>
                </div>
            </div>
        </div>

        {{-- Total déposé CDF --}}
        <div class="col-sm-6 col-xl-2">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge rounded-pill bg-label-info p-2 me-2">
                            <i class="bx bx-money fs-4"></i>
                        </span>
                        <small class="text-muted">Déposé CDF</small>
                    </div>
                    <h5 class="mb-0 fw-bold text-info">{{ number_format($totalSavedCDF, 2) }} <small
                            class="fs-6">CDF</small></h5>
                </div>
            </div>
        </div>

        {{-- Solde comptes USD --}}
        <div class="col-sm-6 col-xl-2">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge rounded-pill bg-label-success p-2 me-2">
                            <i class="bx bx-wallet fs-4"></i>
                        </span>
                        <small class="text-muted">Solde comptes USD</small>
                    </div>
                    <h5 class="mb-0 fw-bold text-success">{{ number_format($totalBalanceUSD, 2) }} <small
                            class="fs-6">USD</small></h5>
                </div>
            </div>
        </div>

        {{-- Solde comptes CDF --}}
        <div class="col-sm-6 col-xl-2">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge rounded-pill bg-label-secondary p-2 me-2">
                            <i class="bx bx-wallet-alt fs-4"></i>
                        </span>
                        <small class="text-muted">Solde comptes CDF</small>
                    </div>
                    <h5 class="mb-0 fw-bold">{{ number_format($totalBalanceCDF, 2) }} <small class="fs-6">CDF</small>
                    </h5>
                </div>
            </div>
        </div>

        {{-- Écarts par devise --}}
        <div class="col-sm-6 col-xl-2">
            <div class="card h-100 border border-danger">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge rounded-pill bg-label-danger p-2 me-2">
                            <i class="bx bx-trending-up fs-4"></i>
                        </span>
                        <small class="text-muted">Écarts totaux</small>
                    </div>
                    <div>
                        <div class="fw-bold text-danger">{{ number_format($ecartUSD, 2) }} <small>USD</small></div>
                        <div class="fw-bold text-danger">{{ number_format($ecartCDF, 2) }} <small>CDF</small></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    {{-- ============ FIN KPI CARDS ============ --}}

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Carnets en anomalie</h5>
            <span class="badge bg-label-danger">{{ $anomalies->total() }} détectés</span>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Membre</th>
                            <th>Code Carnet</th>
                            <th>Dépôt Carnet</th>
                            <th>Solde Compte</th>
                            <th>Écart</th>
                            <th>Devise</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($anomalies as $card)
                            @php
                                $totalSaved = $card->contributions->sum('amount');
                                $account = $card->member->accounts
                                    ->where('currency', $card->currency)
                                    ->where('type', 'savings')
                                    ->first() ?? $card->member->accounts
                                        ->where('currency', $card->currency)
                                        ->where('type', 'current')
                                        ->first();
                                $balance = $account ? $account->balance : 0;
                                $diff = $totalSaved - $balance;
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('member.details', $card->member->id) }}"
                                        class="d-flex justify-content-start align-items-center text-decoration-none">
                                        <div class="avatar-wrapper">
                                            <div class="avatar avatar-sm me-2">
                                                <span
                                                    class="avatar-initial rounded-circle bg-label-primary">{{ substr($card->member->name, 0, 1) }}</span>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium text-heading">{{ $card->member->name }}
                                                {{ $card->member->postnom }}</span>
                                            <small class="text-muted">{{ $card->member->code }}</small>
                                        </div>
                                    </a>
                                </td>
                                <td><span class="badge bg-label-secondary">{{ $card->code }}</span></td>
                                <td><span class="fw-bold text-danger">{{ number_format($totalSaved, 2) }}</span></td>
                                <td><span class="fw-bold text-primary">{{ number_format($balance, 2) }}</span></td>
                                <td>
                                    <span class="badge bg-danger">
                                        + {{ number_format($diff, 2) }}
                                    </span>
                                </td>
                                <td><span class="badge bg-label-info">{{ $card->currency }}</span></td>
                                <td>
                                    @can('supprimer-carnet', App\Models\User::class)
                                        <button wire:click="deactivateCard({{ $card->id }})"
                                            wire:confirm="Êtes-vous sûr de vouloir désactiver ce carnet pour anomalie ?"
                                            class="btn btn-sm btn-outline-danger">
                                            <i class="bx bx-power-off me-1"></i> Désactiver
                                        </button>
                                    @else
                                        <span class="text-muted small"><i class="bx bx-lock-alt me-1"></i>Non autorisé</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="bx bx-check-circle text-success mb-2" style="font-size: 3rem;"></i>
                                        <p class="mb-0">Aucune anomalie détectée. Tous les carnets sont en règle.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $anomalies->links() }}
            </div>
        </div>
    </div>
</div>
