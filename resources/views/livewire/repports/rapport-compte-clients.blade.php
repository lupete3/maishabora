<div>
    <h4 class="card-title">Soldes des Membres</h4>
    <div class="row mb-4">
        @if ($currencyFilter === 'all' || $currencyFilter === 'USD')
            <div class="{{ $currencyFilter === 'all' ? 'col-md-6' : 'col-md-12' }}">
                <div class="card text-center bg-light shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Total USD {{ $accountType !== 'all' ? '('.($accountType === 'current' ? 'Courant' : 'Épargne').')' : '' }}</h5>
                        <h3 class="text-success">{{ number_format($globalUsd, 2) }} $</h3>
                    </div>
                </div>
            </div>
        @endif
        @if ($currencyFilter === 'all' || $currencyFilter === 'CDF')
            <div class="{{ $currencyFilter === 'all' ? 'col-md-6' : 'col-md-12' }}">
                <div class="card text-center bg-light shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Total CDF {{ $accountType !== 'all' ? '('.($accountType === 'current' ? 'Courant' : 'Épargne').')' : '' }}</h5>
                        <h3 class="text-primary">{{ number_format($globalCdf, 2) }} CDF</h3>
                    </div>
                </div>
            </div>
        @endif
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
                    <label class="form-label small fw-bold">Type Compte</label>
                    <select wire:model.live="accountType" class="form-control">
                        <option value="all">Tous</option>
                        <option value="current">Courant</option>
                        <option value="savings">Épargne</option>
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
                    <input type="number" wire:model.live.debounce.500ms="minBalance" class="form-control" placeholder="0.00">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <a href="{{ route('rapports.compte-clients.pdf', ['search' => $search, 'accountType' => $accountType, 'currencyFilter' => $currencyFilter, 'minBalance' => $minBalance, 'alphabetRange' => $alphabetRange]) }}"
                        target="_blank" class="btn btn-danger w-100">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                        <th>Code</th>
                        <th>Membre</th>
                        @if ($currencyFilter === 'all' || $currencyFilter === 'USD')
                            <th>Solde USD</th>
                        @endif
                        @if ($currencyFilter === 'all' || $currencyFilter === 'CDF')
                            <th>Solde CDF</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($balances as $balance)
                        <tr>
                            <td>{{ $balance['member']->code }}</td>
                            <td>{{ $balance['member']->name . ' ' . $balance['member']->postnom . ' ' . $balance['member']->prenom }}
                            </td>
                            @if ($currencyFilter === 'all' || $currencyFilter === 'USD')
                                <td>{{ number_format($balance['usd_balance'], 2) }}</td>
                            @endif
                            @if ($currencyFilter === 'all' || $currencyFilter === 'CDF')
                                <td>{{ number_format($balance['cdf_balance'], 2) }}</td>
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
