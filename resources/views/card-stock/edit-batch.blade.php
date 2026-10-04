@extends('layouts.backend')
@section('title', 'Modifier un lot de carnets')
@section('content')
<div class="container-xxl container-p-y">
<h3>Modifier le lot {{ $batch->reference }}</h3>
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('card-stock.update-batch', $batch) }}" class="card p-3" data-stock-confirm="Confirmer la modification de ce lot ?">@csrf @method('PUT')
<p>{{ $batch->quantity }} carnets — {{ $batch->card_type==='epargne' ? 'Épargne' : 'Compte courant' }}. Les numéros, le type et la quantité sont conservés.</p>
<div class="row">
<div class="col-md-4 mb-3"><label for="edit-reference" class="form-label">Référence</label><input name="reference" id="edit-reference" required maxlength="100" value="{{ old('reference', $batch->reference) }}" class="form-control"></div>
<div class="col-md-4 mb-3"><label for="edit-supplier" class="form-label">Fournisseur</label><input name="supplier" id="edit-supplier" required maxlength="255" value="{{ old('supplier', $batch->supplier) }}" class="form-control"></div>
<div class="col-md-4 mb-3"><label for="edit-received_at" class="form-label">Date de réception</label><input type="date" name="received_at" id="edit-received_at" required value="{{ old('received_at', $batch->received_at) }}" class="form-control"></div>
<div class="col-12 mb-3"><label for="edit-reason" class="form-label">Motif de correction</label><input name="reason" id="edit-reason" required maxlength="1000" value="{{ old('reason') }}" class="form-control"></div>
</div><div><button class="btn btn-primary">Enregistrer la modification</button> <a class="btn btn-outline-secondary" href="{{ route('card-stock.index', ['section'=>'batches']) }}">Annuler</a></div>
</form>
</div>
@include('card-stock.confirmations')
@endsection
