    <div class="card p-3 mb-3"><h5>Lots de carnets — modification</h5>
    @cannot('modifier-lot-carnets')<p class="text-muted">Pour modifier un lot, la permission <strong>modifier-lot-carnets</strong> doit être attribuée à votre rôle.</p>@endcannot<div class="table-responsive"><table class="table"><thead><tr><th>Lot</th><th>Fournisseur</th><th>Réception</th><th>Quantité</th><th>Action</th></tr></thead><tbody>
    @forelse($batches as $batch)<tr><td>{{ $batch->reference }}</td><td>{{ $batch->supplier }}</td><td>{{ $batch->received_at }}</td><td>{{ $batch->quantity }}</td><td>@can('modifier-lot-carnets')<a class="btn btn-sm btn-outline-primary" href="{{ route('card-stock.edit-batch', $batch) }}">Modifier le lot</a>@else<button type="button" class="btn btn-sm btn-outline-secondary" disabled>Modifier le lot</button>@endcan</td></tr>@empty<tr><td colspan="5">Aucun lot enregistré.</td></tr>@endforelse
    </tbody></table></div></div>
{{ $batches->links() }}
