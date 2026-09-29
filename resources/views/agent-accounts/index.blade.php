@extends('layouts.backend')

@section('title', 'Gestion des comptes agents')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Gestion des comptes agents</h4>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted">Tous les agents</div>
                    <div class="fs-4 fw-semibold">{{ $counts['all'] }}</div>
                </div></div>
            </div>
            <div class="col-sm-4">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted">Actifs</div>
                    <div class="fs-4 fw-semibold text-success">{{ $counts['active'] }}</div>
                </div></div>
            </div>
            <div class="col-sm-4">
                <div class="card h-100"><div class="card-body">
                    <div class="text-muted">Inactifs</div>
                    <div class="fs-4 fw-semibold text-secondary">{{ $counts['inactive'] }}</div>
                </div></div>
            </div>
        </div>

        <div class="card">
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('agent-accounts.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Rechercher un agent</label>
                        <input id="search" name="search" type="search" class="form-control"
                            value="{{ $search }}" placeholder="Nom, téléphone ou e-mail">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Statut</label>
                        <select id="status" name="status" class="form-select">
                            <option value="all" @selected($filter === 'all')>Tous ({{ $counts['all'] }})</option>
                            <option value="active" @selected($filter === 'active')>Actifs ({{ $counts['active'] }})</option>
                            <option value="inactive" @selected($filter === 'inactive')>Inactifs ({{ $counts['inactive'] }})</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Filtrer</button>
                        <a href="{{ route('agent-accounts.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Agent</th>
                            <th>Comptes</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($agents as $agent)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ trim($agent->name . ' ' . $agent->postnom . ' ' . $agent->prenom) }}</div>
                                    <div class="small text-muted">{{ $agent->telephone }}{{ $agent->email ? ' · ' . $agent->email : '' }}</div>
                                </td>
                                <td>
                                    @foreach ($agent->agentAccounts as $account)
                                        <div class="d-flex flex-wrap gap-2 align-items-center {{ !$loop->last ? 'mb-1' : '' }}">
                                            <span class="fw-semibold">{{ $account->currency }} {{ number_format((float) $account->balance, 2, ',', ' ') }}</span>
                                            <span class="badge {{ $account->is_visible_dashboard ? 'bg-label-success' : 'bg-label-secondary' }}">
                                                {{ $account->is_visible_dashboard ? 'Visible au tableau de bord' : 'Masqué du tableau de bord' }}
                                            </span>
                                            <form method="POST" action="{{ route('agent-accounts.visibility.update', $account) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="is_visible_dashboard" value="{{ $account->is_visible_dashboard ? 0 : 1 }}">
                                                <button type="submit" class="btn btn-sm {{ $account->is_visible_dashboard ? 'btn-outline-secondary' : 'btn-outline-primary' }}">
                                                    {{ $account->is_visible_dashboard ? 'Masquer' : 'Afficher' }}
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </td>
                                <td>{{ ucfirst($agent->role) }}</td>
                                <td>
                                    <span class="badge {{ $agent->status ? 'bg-label-success' : 'bg-label-secondary' }}">
                                        {{ $agent->status ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('agent-accounts.status.update', $agent) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $agent->status ? 0 : 1 }}">
                                        <button type="submit" class="btn btn-sm {{ $agent->status ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                            {{ $agent->status ? 'Désactiver' : 'Activer' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Aucun compte agent trouvé.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($agents->hasPages())
                <div class="card-body">{{ $agents->links() }}</div>
            @endif
        </div>
    </div>
@endsection