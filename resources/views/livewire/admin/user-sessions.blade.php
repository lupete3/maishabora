<div class="container mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="mb-1">Sessions utilisateurs</h4>
            <small class="text-muted">Supervision des connexions actives et récentes du système.</small>
        </div>

        <button
            type="button"
            class="btn btn-outline-danger"
            wire:click="cleanExpiredSessions"
            wire:confirm="Supprimer toutes les sessions expirées ?">
            <i class="bx bx-trash me-1"></i>
            Nettoyer les expirées
        </button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-2 col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">En ligne</small>
                    <h4 class="mb-0 text-success">{{ $stats['online_sessions'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">Sessions actives</small>
                    <h4 class="mb-0 text-primary">{{ $stats['active_sessions'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">Utilisateurs</small>
                    <h4 class="mb-0">{{ $stats['connected_users'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">Invités</small>
                    <h4 class="mb-0">{{ $stats['guest_sessions'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">Expirées</small>
                    <h4 class="mb-0 text-warning">{{ $stats['expired_sessions'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">Total</small>
                    <h4 class="mb-0">{{ $stats['total_sessions'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-7">
                    <label class="form-label">Recherche</label>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Nom, email, rôle ou adresse IP..."
                        wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Statut</label>
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="active">Actives</option>
                        <option value="online">En ligne maintenant</option>
                        <option value="expired">Expirées</option>
                        <option value="all">Toutes</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Par page</label>
                    <select class="form-select" wire:model.live="perPage">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Utilisateur</th>
                        <th>Rôle</th>
                        <th>Adresse IP</th>
                        <th>Appareil</th>
                        <th>Dernière activité</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $session->full_name }}</div>
                                <small class="text-muted">
                                    {{ $session->email ?? 'Sans compte utilisateur' }}
                                    @if($session->is_current)
                                        <span class="badge bg-label-info ms-1">vous</span>
                                    @endif
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-label-secondary">{{ $session->role ?? 'invité' }}</span>
                            </td>
                            <td>{{ $session->ip_address ?? '-' }}</td>
                            <td>
                                <div>{{ $session->device }}</div>
                                <small class="text-muted">ID: {{ $session->short_id }}</small>
                            </td>
                            <td>
                                <div>{{ $session->last_seen }}</div>
                                <small class="text-muted">{{ $session->idle_for }}</small>
                            </td>
                            <td>
                                @if($session->is_online)
                                    <span class="badge bg-label-success">En ligne</span>
                                @elseif($session->is_active)
                                    <span class="badge bg-label-primary">Active</span>
                                @else
                                    <span class="badge bg-label-warning">Expirée</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        wire:click="terminateSession('{{ $session->id }}')"
                                        wire:confirm="Déconnecter cette session ?"
                                        @disabled($session->is_current)>
                                        <i class="bx bx-log-out"></i>
                                    </button>

                                    @if($session->user_id)
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary"
                                            wire:click="terminateUserSessions({{ $session->user_id }})"
                                            wire:confirm="Déconnecter toutes les sessions de cet utilisateur ?"
                                            @disabled($session->user_id === auth()->id())>
                                            Tout fermer
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Aucune session trouvée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white">
            {{ $sessions->links() }}
        </div>
    </div>
</div>
