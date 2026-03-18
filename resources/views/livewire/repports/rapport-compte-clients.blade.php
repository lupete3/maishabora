<div>
    <h4 class="card-title">Soldes des Membres</h4>
    <div class="row mb-4">
        <!-- Comptes Courants -->
        <div class="col-md-3 mb-3">
            <div class="card text-center bg-light shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted mb-1">Total Courant USD</h6>
                    <h4 class="text-success mb-0">{{ number_format($globalCurrentUsd, 2) }} $</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center bg-light shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted mb-1">Total Courant CDF</h6>
                    <h4 class="text-primary mb-0">{{ number_format($globalCurrentCdf, 2) }} <small>FC</small></h4>
                </div>
            </div>
        </div>
        <!-- Comptes Épargnes -->
        <div class="col-md-3 mb-3">
            <div class="card text-center bg-light shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted mb-1">Total Épargne USD</h6>
                    <h4 class="text-success mb-0">{{ number_format($globalSavingsUsd, 2) }} $</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center bg-light shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted mb-1">Total Épargne CDF</h6>
                    <h4 class="text-primary mb-0">{{ number_format($globalSavingsCdf, 2) }} <small>FC</small></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Recherche</label>
                    <input type="text" wire:model.live.debounce.300ms="search" id="search" class="form-control"
                        placeholder="Nom, code, prénom...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Type Compte (Vue)</label>
                    <select wire:model.live="accountType" class="form-control">
                        <option value="all">Tous</option>
                        <option value="current">Courant Uniquement</option>
                        <option value="savings">Épargne Uniquement</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Devise</label>
                    <select wire:model.live="currencyFilter" class="form-control">
                        <option value="all">Toutes</option>
                        <option value="USD">USD</option>
                        <option value="CDF">CDF</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Tranche Alphabétique</label>
                    <select wire:model.live="alphabetRange" class="form-control">
                        <option value="all">Toute la liste</option>
                        <option value="A-D">De A à D</option>
                        <option value="E-H">De E à H</option>
                        <option value="I-L">De I à L</option>
                        <option value="M-P">De M à P</option>
                        <option value="Q-T">De Q à T</option>
                        <option value="U-X">De U à X</option>
                        <option value="Y-Z">De Y à Z</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Solde Min</label>
                    <input type="number" wire:model.live.debounce.500ms="minBalance" class="form-control"
                        placeholder="0.00">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <a href="{{ route('rapports.compte-clients.pdf', ['search' => $search, 'accountType' => $accountType, 'currencyFilter' => $currencyFilter, 'minBalance' => $minBalance, 'alphabetRange' => $alphabetRange]) }}"
                        target="_blank" class="btn btn-danger w-100 p-2">
                        <i class="bx bx-download"></i> PDF
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2" class="align-middle">Code</th>
                            <th rowspan="2" class="align-middle">Membre</th>
                            @if ($currencyFilter === 'all' || $currencyFilter === 'USD')
                                <th colspan="2" class="text-center">Solde USD</th>
                            @endif
                            @if ($currencyFilter === 'all' || $currencyFilter === 'CDF')
                                <th colspan="2" class="text-center">Solde CDF</th>
                            @endif
                        </tr>
                        <tr>
                            @if ($currencyFilter === 'all' || $currencyFilter === 'USD')
                                <th class="text-center small">Courant</th>
                                <th class="text-center small">Épargne</th>
                            @endif
                            @if ($currencyFilter === 'all' || $currencyFilter === 'CDF')
                                <th class="text-center small">Courant</th>
                                <th class="text-center small">Épargne</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($balances as $balance)
                            <tr>
                                <td><small class="fw-bold">{{ $balance['member']->code }}</small></td>
                                <td>{{ $balance['member']->name . ' ' . $balance['member']->postnom . ' ' . $balance['member']->prenom }}
                                </td>

                                @if ($currencyFilter === 'all' || $currencyFilter === 'USD')
                                    <td class="text-end">{{ number_format($balance['current_usd'], 2) }}</td>
                                    <td class="text-end">{{ number_format($balance['savings_usd'], 2) }}</td>
                                @endif

                                @if ($currencyFilter === 'all' || $currencyFilter === 'CDF')
                                    <td class="text-end">{{ number_format($balance['current_cdf'], 2) }}</td>
                                    <td class="text-end">{{ number_format($balance['savings_cdf'], 2) }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-3">
                    {{ $members->links() }}
                </div>

            </div>
        </div>

    </div>