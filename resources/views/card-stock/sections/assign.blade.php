    @can('distribuer-stock-carnets')
    @if($batches->isEmpty())<div class="alert alert-info">Aucun lot disponible pour une attribution. Enregistrez une réception ou effectuez un retour au stock.</div>@endif
    <form method="POST" action="{{ route('card-stock.assign-batch') }}" data-stock-confirm="Confirmer cette attribution ?" class="card p-3 mb-3">@csrf
    <input type="hidden" name="_section" value="assign">
    <h5>Attribuer une plage ou une quantité depuis un lot</h5>
    <div class="row">
        <div class="col-md-4 mb-2"><label for="assign.blade-batch_id" class="form-label">Lot</label><select name="batch_id" required class="form-select" id="assign.blade-batch_id"><option value="">Choisir</option>@foreach($batches as $batch)<option value="{{ $batch->id }}" @selected(old('batch_id')==$batch->id)>{{ $batch->reference }} ({{ $batch->card_type==='epargne' ? 'Épargne' : 'Courant' }})</option>@endforeach</select></div>
        <div class="col-md-4 mb-2"><label for="assign.blade-collector_id" class="form-label">Agent (collecteur ou admin)</label><select name="collector_id" required class="form-select" id="assign.blade-collector_id"><option value="">Choisir</option>@foreach($collectors as $collector)<option value="{{ $collector->id }}" @selected(old('collector_id')==$collector->id)>{{ $collector->name }} {{ $collector->postnom }} ({{ $collector->role === 'admin' ? 'Admin' : 'Collecteur' }})</option>@endforeach</select></div>
        <div class="col-md-4 mb-2"><label for="assign.blade-assign_quantity" class="form-label">Quantité (compte courant)</label><input type="number" min="1" max="10000" name="assign_quantity" value="{{ old('assign_quantity') }}" class="form-control" id="assign.blade-assign_quantity"></div>
        <div class="col-md-4 mb-2"><label for="assign.blade-range_start" class="form-label">Premier numéro (épargne)</label><input type="number" min="1" name="range_start" value="{{ old('range_start') }}" class="form-control" id="assign.blade-range_start"></div>
        <div class="col-md-4 mb-2"><label for="assign.blade-range_end" class="form-label">Dernier numéro inclus (épargne)</label><input type="number" min="1" name="range_end" value="{{ old('range_end') }}" class="form-control" id="assign.blade-range_end"></div>
        <div class="col-md-4 mb-2"><label for="assign.blade-reason" class="form-label">Motif</label><input name="reason" value="{{ old('reason') }}" required maxlength="1000" class="form-control" id="assign.blade-reason"></div>
    </div><div><button class="btn btn-primary" @disabled($batches->isEmpty())>Attribuer les carnets</button></div></form>
    @endcan
@cannot('distribuer-stock-carnets')<div class="alert alert-info">Permission requise : distribuer-stock-carnets.</div>@endcannot
