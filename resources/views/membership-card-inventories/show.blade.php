@extends('layouts.backend')

@section('title', 'Détail de l’inventaire')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Inventaire du {{ $inventory->inventory_date->format('d/m/Y') }}</h4>
                <div class="text-muted">Agent concerné : {{ $inventory->agent_name }}</div>
                <span class="badge {{ $inventory->status === 'recorded' ? 'bg-label-success' : 'bg-label-warning' }}">
                    {{ $inventory->status === 'recorded' ? 'Enregistré' : 'Brouillon' }}
                </span>
            </div>
            <div class="d-flex gap-2">
                @if ($inventory->status === 'recorded')
                    <a href="{{ route('card-inventories.export', $inventory) }}" class="btn btn-primary">Exporter le PDF</a>
                @endif
                <a href="{{ route('card-inventories.index') }}" class="btn btn-outline-secondary">Retour à la liste</a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @if ($inventory->status === 'draft')
            <div class="alert alert-info">
                Brouillon sauvegardé. Pour chaque ligne, saisissez un montant puis cliquez sur <strong>Enregistrer la ligne</strong>.
                Si le carnet est absent, laissez le montant vide et cliquez sur <strong>Marquer non inventorié</strong>.
                Vous pouvez quitter cette page et reprendre le brouillon plus tard.
                {{ $pendingCount }} ligne(s) restent à traiter.
            </div>
        @endif

        @php($totalsByCurrency = $inventory->items->groupBy('currency'))
        <div class="row g-3 mb-4">
            @foreach ($totalsByCurrency as $currency => $items)
                <div class="col-md-4">
                    <div class="card h-100"><div class="card-body">
                        <div class="text-muted">{{ $currency }} — total théorique</div>
                        <div class="fs-5 fw-semibold">{{ number_format($items->sum('theoretical_total'), 2, ',', ' ') }}</div>
                        <div class="small text-muted mt-2">Total constaté : {{ number_format($items->sum('physical_total'), 2, ',', ' ') }} {{ $currency }}</div>
                        <div class="small fw-semibold {{ $items->sum('difference') < 0 ? 'text-danger' : ($items->sum('difference') > 0 ? 'text-success' : 'text-muted') }}">
                            Écart : {{ number_format($items->sum('difference'), 2, ',', ' ') }} {{ $currency }}
                        </div>
                    </div></div>
                </div>
            @endforeach
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Carnet</th>
                            <th>Code membre</th>
                            <th>Membre</th>
                            <th>Mise du carnet</th>
                            <th>Mises payées</th>
                            <th>Total théorique</th>
                            <th>Total constaté</th>
                            <th>Écart</th>
                            @if ($inventory->status === 'draft')
                                <th>Statut / action</th>
                            @else
                                <th>Statut</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($inventory->items as $item)
                            <tr>
                                <td>{{ $item->card_code }}</td>
                                <td>{{ $item->member_code ?? '—' }}</td>
                                <td>{{ $item->member_name }}</td>
                                <td>{{ number_format((float) $item->subscription_amount, 2, ',', ' ') }} {{ $item->currency }}</td>
                                <td>{{ $item->paid_contributions_count }}</td>
                                <td>{{ number_format((float) $item->theoretical_total, 2, ',', ' ') }} {{ $item->currency }}</td>
                                <td>
                                    @if ($item->physical_total !== null)
                                        {{ number_format((float) $item->physical_total, 2, ',', ' ') }} {{ $item->currency }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="fw-semibold {{ $item->difference < 0 ? 'text-danger' : ($item->difference > 0 ? 'text-success' : 'text-muted') }}">
                                    @if ($item->difference !== null)
                                        {{ number_format((float) $item->difference, 2, ',', ' ') }} {{ $item->currency }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($inventory->status === 'draft')
                                        <form method="POST" action="{{ route('card-inventories.items.update', [$inventory, $item]) }}"
                                            class="d-flex flex-wrap gap-2 align-items-center">
                                            @csrf
                                            @method('PATCH')
                                            <div class="input-group input-group-sm" style="min-width: 160px; max-width: 220px">
                                                <input name="physical_total" type="number" min="0" step="0.01"
                                                    class="form-control" value="{{ $item->physical_total }}"
                                                    aria-label="Montant trouvé pour le carnet {{ $item->card_code }}">
                                                <span class="input-group-text">{{ $item->currency }}</span>
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-primary">Enregistrer la ligne</button>
                                            <div class="w-100 small">
                                                @if ($item->status === 'inventoried')
                                                    <span class="text-success">Montant enregistré</span>
                                                @elseif ($item->status === 'not_inventoried')
                                                    <span class="text-warning-emphasis">Non inventorié</span>
                                                @else
                                                    <span class="text-muted">À traiter</span>
                                                @endif
                                                <span class="text-muted">— montant vide = non inventorié</span>
                                            </div>
                                        </form>
                                    @elseif ($item->status === 'not_inventoried')
                                        <span class="badge bg-label-warning">Non inventorié</span>
                                    @else
                                        <span class="badge bg-label-success">Montant enregistré</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($inventory->status === 'draft')
                <div class="card-body text-end">
                    <form method="POST" action="{{ route('card-inventories.finalize', $inventory) }}">
                        @csrf
                        <button type="submit" class="btn btn-success" @disabled($pendingCount > 0)>
                            Finaliser et enregistrer l’inventaire
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
