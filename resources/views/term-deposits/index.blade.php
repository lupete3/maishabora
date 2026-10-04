@extends('layouts.backend')

@section('title', 'Dépôts à terme')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h4 class="mb-0">Dépôts à terme</h4>
            @can('ouvrir-depot-terme')
                <a href="{{ route('term-deposits.create') }}" class="btn btn-primary">
                    <i class="bx bx-lock-open-alt me-1"></i> Ouvrir un placement
                </a>
            @endcan
        </div>

        <section class="card mb-4">
            <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Produits</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produit</th>
                            <th>Durée</th>
                            <th>Taux annuel</th>
                            <th>Pénalité anticipée</th>
                            <th>Intérêts anticipés</th>
                            <th>Plafonds</th>
                            <th>État</th>
                            @can('gerer-produits-depot-terme')<th class="text-end">Action</th>@endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $product->name }}</div>
                                </td>
                                <td>{{ $product->term_months ? $product->term_months . ' mois' : $product->term_days . ' jours' }}</td>
                                <td>{{ number_format((float) $product->annual_interest_rate, 4, ',', ' ') }} %</td>
                                <td>{{ number_format((float) $product->early_withdrawal_penalty_rate, 4, ',', ' ') }} % du principal</td>
                                <td>{{ $product->early_withdrawal_interest_policy === 'pro_rata' ? 'Prorata' : 'Perdus' }}</td>
                                <td>{{ number_format((float) $product->minimum_amount, 2, ',', ' ') }} – {{ $product->maximum_amount ? number_format((float) $product->maximum_amount, 2, ',', ' ') : 'sans maximum' }}</td>
                                <td><span class="badge {{ $product->is_active ? 'bg-label-success' : 'bg-label-secondary' }}">{{ $product->is_active ? 'Actif' : 'Inactif' }}</span></td>
                                @can('gerer-produits-depot-terme')
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('term-deposit-products.toggle', $product) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm {{ $product->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" type="submit">
                                                {{ $product->is_active ? 'Désactiver' : 'Activer' }}
                                            </button>
                                        </form>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Aucun produit configuré.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </div>
        </section>

        @can('gerer-produits-depot-terme')
            <section class="card mb-4">
                <div class="card-body">
                <h5 class="mb-3">Créer un produit</h5>
                <form method="POST" action="{{ route('term-deposit-products.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="name">Nom</label>
                            <input class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="term_months">Durée (mois)</label>
                            <input class="form-control" id="term_months" name="term_months" type="number" min="1" max="1200" step="1" value="{{ old('term_months') }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="annual_interest_rate">Taux annuel (%)</label>
                            <input class="form-control" id="annual_interest_rate" name="annual_interest_rate" type="number" step="0.0001" min="0" value="{{ old('annual_interest_rate') }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="early_withdrawal_penalty_rate">Pénalité principal (%)</label>
                            <input class="form-control" id="early_withdrawal_penalty_rate" name="early_withdrawal_penalty_rate" type="number" step="0.0001" min="0" max="100" value="{{ old('early_withdrawal_penalty_rate', 0) }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="early_withdrawal_interest_policy">Intérêts anticipés</label>
                            <select class="form-select" id="early_withdrawal_interest_policy" name="early_withdrawal_interest_policy" required>
                                <option value="forfeit">Perdus</option>
                                <option value="pro_rata">Payés au prorata</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="minimum_amount">Montant minimum</label>
                            <input class="form-control" id="minimum_amount" name="minimum_amount" type="number" step="0.01" min="0.01" value="{{ old('minimum_amount', 0.01) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="maximum_amount">Montant maximum (facultatif)</label>
                            <input class="form-control" id="maximum_amount" name="maximum_amount" type="number" step="0.01" min="0.01" value="{{ old('maximum_amount') }}">
                        </div>
                        <div class="col-12 text-end">
                            <button class="btn btn-primary" type="submit">Créer le produit</button>
                        </div>
                    </div>
                </form>
                </div>
            </section>
        @endcan

        <section class="card">
            <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
            <h5 class="mb-3">Contrats</h5>
            <form method="GET" action="{{ route('term-deposits.index') }}" class="d-flex gap-2 mb-3" role="search">
                <label class="visually-hidden" for="term_deposit_search">Rechercher un contrat</label>
                <input class="form-control" id="term_deposit_search" name="search" type="search" value="{{ $search }}" placeholder="Membre, code, téléphone, produit…" aria-label="Rechercher les dépôts à terme">
                <button class="btn btn-primary" type="submit" aria-label="Rechercher"><i class="bx bx-search"></i></button>
                @if ($search !== '')
                    <a class="btn btn-outline-secondary" href="{{ route('term-deposits.index') }}" aria-label="Effacer la recherche"><i class="bx bx-x"></i></a>
                @endif
            </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Membre</th><th>Produit</th><th>Ouverture</th><th>Échéance</th><th>Capital</th><th>Intérêts capitalisés</th><th>Taux annuel</th><th>État</th><th class="text-end">Action</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($termDeposits as $deposit)
                            <tr>
                                <td><a href="{{ route('member.details', $deposit->user_id) }}">{{ trim($deposit->user->name . ' ' . $deposit->user->postnom . ' ' . $deposit->user->prenom) }}</a></td>
                                <td>{{ $deposit->product->name }}</td>
                                <td>{{ $deposit->opened_at->format('d/m/Y') }}</td>
                                <td>{{ $deposit->matures_at->format('d/m/Y') }}</td>
                                <td>{{ number_format((float) $deposit->principal_amount, 2, ',', ' ') }} {{ $deposit->currency }}</td>
                                <td>{{ number_format((float) $deposit->accrued_interest_amount, 2, ',', ' ') }} {{ $deposit->currency }}</td>
                                <td>{{ number_format((float) $deposit->annual_interest_rate, 4, ',', ' ') }} %</td>
                                <td><span class="badge bg-label-{{ $deposit->status === 'active' ? 'success' : ($deposit->status === 'closed' ? 'primary' : 'secondary') }}">{{ ucfirst(str_replace('_', ' ', $deposit->status)) }}</span></td>
                                <td class="text-end">
                                    @can('cloturer-depot-terme')
                                        @if ($deposit->status === 'active' && $deposit->matures_at->isFuture())
                                            <form method="POST" action="{{ route('term-deposits.withdraw-early', $deposit) }}" onsubmit="return confirm('Confirmer le retrait anticipé selon la pénalité du contrat ?')">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Retrait anticipé</button>
                                            </form>
                                        @endif
                                        @if (in_array($deposit->status, ['active', 'matured'], true) && !$deposit->matures_at->isFuture())
                                            <form method="POST" action="{{ route('term-deposits.settle-maturity', $deposit) }}" onsubmit="return confirm('Confirmer le versement du capital et des intérêts capitalisés ?')">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-outline-primary" type="submit">Verser à l’échéance</button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">Aucun contrat à terme.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                <small class="text-muted">{{ $termDeposits->firstItem() ?? 0 }}–{{ $termDeposits->lastItem() ?? 0 }} sur {{ $termDeposits->total() }} contrats</small>
                {{ $termDeposits->links() }}
            </div>
            </div>
        </section>
    </div>
@endsection