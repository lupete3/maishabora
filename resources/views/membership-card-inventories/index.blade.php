@extends('layouts.backend')

@section('title', 'Inventaire des carnets')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <h4 class="fw-bold mb-0">Inventaire des carnets d’épargne</h4>
            <a href="{{ route('card-inventories.create') }}" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i> Nouvel inventaire
            </a>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('card-inventories.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="from" class="form-label">Du</label>
                        <input id="from" name="from" type="date" class="form-control" value="{{ $filters['from'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label for="to" class="form-label">Au</label>
                        <input id="to" name="to" type="date" class="form-control" value="{{ $filters['to'] ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label for="agent_id" class="form-label">Agent concerné</label>
                        <select id="agent_id" name="agent_id" class="form-select">
                            <option value="">Tous les agents</option>
                            @foreach ($agents as $agentOption)
                                <option value="{{ $agentOption->id }}" @selected(($filters['agent_id'] ?? '') == $agentOption->id)>
                                    {{ trim($agentOption->name . ' ' . $agentOption->postnom . ' ' . $agentOption->prenom) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary">Filtrer</button>
                        <a href="{{ route('card-inventories.index') }}" class="btn btn-outline-secondary">Effacer</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Agent concerné</th>
                            <th>Lignes traitées</th>
                            <th>Statut</th>
                            <th>Enregistré par</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inventories as $inventory)
                            <tr>
                                <td>{{ $inventory->inventory_date->format('d/m/Y') }}</td>
                                <td>{{ $inventory->agent_name }}</td>
                                <td>{{ $inventory->processed_items_count }} / {{ $inventory->items_count }}</td>
                                <td>
                                    <span class="badge {{ $inventory->status === 'recorded' ? 'bg-label-success' : 'bg-label-warning' }}">
                                        {{ $inventory->status === 'recorded' ? 'Enregistré' : 'Brouillon' }}
                                    </span>
                                </td>
                                <td>{{ $inventory->creator?->name ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('card-inventories.show', $inventory) }}" class="btn btn-sm btn-outline-primary">
                                        {{ $inventory->status === 'recorded' ? 'Détail' : 'Continuer' }}
                                    </a>
                                    @if ($inventory->status === 'recorded')
                                        <a href="{{ route('card-inventories.export', $inventory) }}" class="btn btn-sm btn-outline-secondary">PDF</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Aucun inventaire ne correspond aux filtres.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($inventories->hasPages())
                <div class="card-body">{{ $inventories->links() }}</div>
            @endif
        </div>
    </div>
@endsection
