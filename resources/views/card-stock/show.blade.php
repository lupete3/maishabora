@extends('layouts.backend')
@section('title', 'Traçabilité du carnet')
@section('content')
<div class="container-xxl container-p-y">
<h3>Carnet {{ $item->reference }}</h3>

@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="card p-3 mb-3">
<p>Lot : {{ $item->batch->reference }} → Fournisseur : {{ $item->batch->supplier }} → Réception : {{ $item->batch->received_at }}</p>
<p>Type : {{ $item->card_type==='epargne' ? 'Épargne' : 'Compte courant' }} → Statut : <x-card-stock-status :status="$item->status" /> → Agent : {{ $item->collector?->name ?? '—' }}</p>
@if($item->sale)<p>Membre : {{ $item->sale->member?->name }} → Code : {{ $item->sale->code }} → Prix : {{ $item->sale->price }} {{ $item->sale->currency }} → Mise : {{ $item->sale->subscription_amount }} {{ $item->sale->currency }}</p>@endif
</div>
@if($item->cancelledSales->isNotEmpty())
<div class="card p-3 mb-3"><h5>Historique des ventes annulées</h5>
@foreach($item->cancelledSales as $sale)
<p>Code : {{ $sale->code }} — Membre : {{ $sale->member?->name }} — Prix : {{ $sale->price }} {{ $sale->currency }} — Annulation : {{ $sale->cancelled_at }}</p>
@endforeach
</div>
@endif
<div class="card table-responsive"><table class="table"><thead><tr><th>Date</th><th>Action</th><th>Statuts</th><th>Agents</th><th>Opérateur</th><th>Motif</th></tr></thead><tbody>
@foreach($item->movements->sortByDesc('id') as $movement)<tr><td>{{ $movement->created_at->format('d/m/Y H:i') }}</td><td>{{ \App\Support\CardStockLabels::action($movement->action) }}</td><td><x-card-stock-status :status="$movement->from_status" /> <span aria-label="vers">→</span> <x-card-stock-status :status="$movement->to_status" /></td><td>{{ $movement->fromCollector?->name ?? '—' }} → {{ $movement->toCollector?->name ?? '—' }}</td><td>{{ $movement->actor?->name }}</td><td>{{ $movement->reason }}</td></tr>@endforeach
</tbody></table></div>
@can('annuler-vente-carnet')
@if($item->status==='sold')
<form method="POST" action="{{ route('card-stock.cancel', $item) }}" data-stock-confirm="Confirmer l'annulation de cette vente et la compensation des montants ?" class="card p-3 mt-3">@csrf
<h5>Annulation de la vente</h5><p>Les écritures sont compensées et l'historique conservé. Un carnet récupéré revient au stock pour être réaffecté.</p>
<label>Motif</label><input name="reason" required maxlength="1000" class="form-control mb-2">
<label><input type="checkbox" name="recovered" value="1"> Carnet physique récupéré (sinon déclaré perdu)</label>
<button class="btn btn-danger mt-3">Annuler la vente</button></form>
@endif
@endcan
</div>
@include('card-stock.confirmations')
@endsection
