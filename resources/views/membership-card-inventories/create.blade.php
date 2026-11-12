@extends('layouts.backend')

@section('title', 'Nouvel inventaire des carnets')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <h4 class="fw-bold mb-0">Nouvel inventaire des carnets d’épargne</h4>
            <a href="{{ route('card-inventories.index') }}" class="btn btn-outline-secondary">Retour à la liste</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">Vérifiez les informations de l’inventaire.</div>
                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('card-inventories.create') }}" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="agent_id" class="form-label">Agent dont les carnets seront vérifiés</label>
                        <select id="agent_id" name="agent_id" class="form-select" required>
                            <option value="">Sélectionner un agent</option>
                            @foreach ($agents as $agentOption)
                                <option value="{{ $agentOption->id }}" @selected($agent?->id === $agentOption->id)>
                                    {{ trim($agentOption->name . ' ' . $agentOption->postnom . ' ' . $agentOption->prenom) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="search" class="form-label">Rechercher un carnet ou un membre</label>
                        <input id="search" name="search" type="search" class="form-control" value="{{ $search }}"
                            placeholder="Code carnet, code client ou nom">
                    </div>
                    <div class="col-md-2 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-outline-primary">Rechercher</button>
                        @if ($agent && $search !== '')
                            <a href="{{ route('card-inventories.create', ['agent_id' => $agent->id]) }}"
                                class="btn btn-outline-secondary">Effacer</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        @if ($agent)
            @if ($search !== '')
                <div class="alert alert-info py-2">
                    Résultats correspondant à « {{ $search }} ». Le brouillon contiendra uniquement les carnets affichés.
                    <a href="{{ route('card-inventories.create', ['agent_id' => $agent->id]) }}" class="alert-link ms-1">Afficher tous les carnets</a>
                </div>
            @endif
            <form method="POST" action="{{ route('card-inventories.store') }}">
                @csrf
                <input type="hidden" name="agent_id" value="{{ $agent->id }}">
                @foreach ($cards as $card)
                    <input type="hidden" name="card_ids[]" value="{{ $card->id }}">
                @endforeach

                <div class="card">
                    <div class="card-body border-bottom d-flex flex-wrap justify-content-between align-items-end gap-3">
                        <div>
                            <div class="fw-semibold">{{ trim($agent->name . ' ' . $agent->postnom . ' ' . $agent->prenom) }}</div>
                            <div class="small text-muted">Carnets actifs de type épargne affectés à cet agent</div>
                        </div>
                        <div>
                            <label for="inventory_date" class="form-label">Date de l’inventaire</label>
                            <input id="inventory_date" name="inventory_date" type="date" class="form-control"
                                value="{{ old('inventory_date', now()->toDateString()) }}" required>
                        </div>
                    </div>

                    @if ($cards->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Carnet</th>
                                        <th>Code membre</th>
                                        <th>Nom du membre</th>
                                        <th>Mise du carnet</th>
                                        <th>Mises payées</th>
                                        <th>Total théorique</th>
                                        <th>Étape suivante</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cards as $card)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $card->code }}</div>
                                                <div class="small text-muted">{{ $card->currency }}</div>
                                            </td>
                                            <td>{{ $card->member?->code ?? '—' }}</td>
                                            <td>{{ $card->member ? trim($card->member->name . ' ' . $card->member->postnom . ' ' . $card->member->prenom) : 'Membre supprimé' }}</td>
                                            <td>{{ number_format((float) $card->subscription_amount, 2, ',', ' ') }} {{ $card->currency }}</td>
                                            <td>{{ $card->paid_contributions_count }}</td>
                                            <td>{{ number_format((float) ($card->theoretical_total ?? 0), 2, ',', ' ') }} {{ $card->currency }}</td>
                                            <td><span class="badge bg-label-secondary">À compléter dans le brouillon</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-body text-end">
                            <button type="submit" class="btn btn-primary">Créer le brouillon</button>
                        </div>
                    @else
                        <div class="card-body text-center text-muted">
                            {{ $search !== '' ? 'Aucun carnet ou membre ne correspond à cette recherche.' : 'Aucun carnet actif d’épargne n’est affecté à cet agent.' }}
                        </div>
                    @endif
                </div>
            </form>
        @elseif ($agents->isEmpty())
            <div class="alert alert-info">Aucun agent actif ne possède de carnet actif d’épargne affecté.</div>
        @endif
    </div>

@endsection
