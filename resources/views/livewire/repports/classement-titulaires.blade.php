<div>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1">Classement des titulaires de comptes</h4>
            <small class="text-muted">
                Seuls les comptes « Client » comptent dans « ce que nous devons aux clients ».
                Les comptes internes (retenues, associés…) et de test en sont exclus.
            </small>
        </div>
        <a href="{{ route('member.accounts') }}" class="btn btn-outline-primary btn-sm">
            <i class="bx bx-arrow-back me-1"></i> Retour aux comptes membres
        </a>
    </div>

    <div class="row g-3 mb-3">
        @foreach ($natures as $key => $label)
            <div class="col-md-4">
                <div class="card text-center shadow-sm">
                    <div class="card-body py-2">
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="fs-5 fw-bold">{{ $compteurs[$key] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Recherche</label>
                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Nom, code…">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Nature actuelle</label>
                    <select wire:model.live="filtreNature" class="form-control">
                        <option value="tous">Toutes</option>
                        @foreach ($natures as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="suggestions" wire:model.live="suggestionsSeulement">
                        <label class="form-check-label" for="suggestions">
                            Suggestions seulement : clients dont le nom contient COMPTE, RETENU, COMMISSION, TEST…
                        </label>
                    </div>
                </div>
            </div>
            <div wire:loading.delay class="small text-muted mt-2">
                <span class="spinner-border spinner-border-sm"></span> Mise à jour…
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Titulaire</th>
                        <th class="text-end">Solde USD</th>
                        <th class="text-end">Solde CDF</th>
                        <th style="width: 220px">Nature</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        <tr wire:key="titulaire-{{ $member->id }}">
                            <td><small class="fw-bold">{{ $member->code }}</small></td>
                            <td>{{ trim($member->name . ' ' . $member->postnom . ' ' . $member->prenom) }}</td>
                            <td class="text-end">{{ number_format((float) $member->solde_usd, 2, ',', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) $member->solde_cdf, 2, ',', ' ') }}</td>
                            <td>
                                <select class="form-select form-select-sm
                                        @if ($member->client_category === 'interne') border-warning @elseif ($member->client_category === 'test') border-secondary @endif"
                                    wire:change="changerNature({{ $member->id }}, $event.target.value)">
                                    @foreach ($natures as $key => $label)
                                        <option value="{{ $key }}" @selected($member->client_category === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Aucun titulaire ne correspond.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($members->hasPages())
            <div class="card-body py-2">{{ $members->links() }}</div>
        @endif
    </div>
</div>
