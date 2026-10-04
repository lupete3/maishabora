@extends('layouts.backend')
@section('title', 'Corriger le numéro du carnet')
@section('content')
<div class="container-xxl container-p-y">
<h3>Corriger le numéro du carnet {{ $item->reference }}</h3>
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('card-stock.update-number', $item) }}" class="card p-3" data-stock-confirm="Confirmer la correction du numéro de ce carnet ?">
@csrf @method('PUT')
<input type="hidden" name="original_number" value="{{ old('original_number', $item->printed_number) }}">
<p>Lot : {{ $item->batch->reference }}. Statut : <x-card-stock-status :status="$item->status" />.</p>
<p class="text-muted">Indiquez le numéro réel imprimé sur le carnet. Le code des ventes existantes est conservé. Le nouveau numéro servira aux ventes futures ; cette correction est enregistrée dans l’historique.</p>
<div class="row">
<div class="col-md-4 mb-3"><label for="correct-number" class="form-label">Numéro du carnet</label><input id="correct-number" name="printed_number" type="number" min="1" max="999999999" required value="{{ old('printed_number', $item->printed_number) }}" class="form-control"></div>
<div class="col-md-8 mb-3"><label for="correct-reason" class="form-label">Motif de correction</label><input id="correct-reason" name="reason" required maxlength="1000" value="{{ old('reason') }}" class="form-control"></div>
</div>
<div><button class="btn btn-primary">Enregistrer la correction</button> <a class="btn btn-outline-secondary" href="{{ route('card-stock.index', ['batch'=>$item->card_batch_id, 'section'=>'stock']) }}">Annuler</a></div>
</form></div>
@include('card-stock.confirmations')
@endsection
