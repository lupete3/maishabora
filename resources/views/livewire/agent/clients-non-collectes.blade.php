<div>
    <div class="row mb-3">
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-users text-primary fa-2x mb-2"></i>
                    <h3 class="fw-bold mb-0">{{ number_format($totalClients) }}</h3>
                    <small class="text-muted">Clients affectés</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                    <h3 class="fw-bold text-success mb-0">{{ number_format($collectesAujourdHui) }}</h3>
                    <small class="text-muted">Collectés aujourd'hui</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-hourglass-half text-warning fa-2x mb-2"></i>
                    <h3 class="fw-bold text-warning mb-0">{{ number_format($restants) }}</h3>
                    <small class="text-muted">Restants</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle text-danger fa-2x mb-2"></i>
                    <h3 class="fw-bold text-danger mb-0">{{ number_format($plus7jours) }}</h3>
                    <small class="text-muted">+7 jours</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="fas fa-user-slash text-dark fa-2x mb-2"></i>
                    <h3 class="fw-bold text-dark mb-0">{{ number_format($jamaisCollectes) }}</h3>
                    <small class="text-muted">Jamais collectés</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-12 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <small>Progression</small>
                        <strong>{{ $progression }}%</strong>
                    </div>
                    <div class="progress mt-2" style="height:12px;">
                        <div class="progress-bar bg-success"
                             style="width: {{ $progression }}%">
                        </div>
                    </div>
                    <small class="text-muted">
                        {{ $collectesAujourdHui }} / {{ $totalClients }} membres
                    </small>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <div class="row align-items-center">
                <div class="col-lg-4">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-route text-primary"></i>
                        Suivi des collectes du jour
                    </h5>
                    <small class="text-muted">
                        Les clients ci-dessous n'ont pas encore effectué
                        d'opération aujourd'hui.
                    </small>
                </div>
                <div class="col-lg-8">
                    <div class="row mb-3">
                        @if(auth()->user()->hasAnyRole(['Admin','Caissier','SUPER IT','Comptable']))

                            <div class="col-md-4">

                                <select class="form-select"
                                        wire:model.live="agentId">

                                    <option value="">

                                        Tous les collecteurs

                                    </option>

                                    @foreach($agents as $agent)

                                        <option value="{{ $agent->id }}">

                                            {{ $agent->name }}
                                            {{ $agent->postnom }}
                                            {{ $agent->prenom }}

                                        </option>

                                    @endforeach

                                </select>

                            </div>

                            <div class="col-md-8">

                                <input
                                    type="text"
                                    class="form-control"
                                    placeholder="Recherche..."
                                    wire:model.live.debounce.300ms="search">

                            </div>

                        @else

                            <div class="col-12">

                                <input
                                    type="text"
                                    class="form-control"
                                    placeholder="Recherche..."
                                    wire:model.live.debounce.300ms="search">

                            </div>

                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Code</th>
                    <th>Client</th>
                    <th>Téléphone</th>
                    <th>Adresse</th>
                    <th>Priorité</th>
                    <th>Dernière opération</th>
                </tr>
                </thead>
                <tbody>
                @forelse($clients as $client)
                    @php
                        $last = $client->last_transaction_at
                            ? \Carbon\Carbon::parse($client->last_transaction_at)
                            : null;
                    @endphp
                    <tr style="cursor:pointer"
                        @canany(['afficher-compte-membre','depot-compte-membre','retrait-compte-membre'])
                        onclick="window.location='{{ route('member.details',$client->id) }}'"
                        @endcanany
                    >
                        <td>
                            <span class="fw-bold">
                                {{ $client->code }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold">
                                {{ $client->name }}
                                {{ $client->postnom }}
                                {{ $client->prenom }}
                            </div>
                        </td>
                        <td>
                            {{ $client->telephone }}
                        </td>
                        <td>
                            {{ $client->adresse_physique }}
                        </td>
                        <td>
                            @if(!$last)
                                <span class="badge bg-danger">
                                    🔴 Jamais collecté
                                </span>
                            @elseif($last->diffInDays(now())>=7)
                                <span class="badge bg-warning text-dark">
                                    🟠 +7 jours
                                </span>
                            @elseif($last->diffInDays(now())>=3)
                                <span class="badge bg-info">
                                    🟡 +3 jours
                                </span>
                            @else
                                <span class="badge bg-primary">
                                    🔵 Aujourd'hui à visiter
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($last)
                                <div>
                                    {{ $last->diffForHumans() }}
                                </div>
                                <small class="text-muted">
                                    {{ $last->format('d/m/Y H:i') }}
                                </small>
                            @else
                                <span class="text-danger fw-bold">
                                    Aucune opération enregistrée
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                            <h5 class="text-success">
                                Félicitations !
                            </h5>
                            <p class="text-muted mb-0">
                                Tous vos membres ont déjà effectué une opération aujourd'hui.
                            </p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $clients->links() }}
        </div>
    </div>
</div>
