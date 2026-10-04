    @can('recevoir-stock-carnets')
    <div class="card p-3 mb-3"><h5>Réception d'un lot</h5>
        <form method="POST" action="{{ route('card-stock.receive') }}" data-stock-confirm="Confirmer la reception de ce lot ?">@csrf
        <input type="hidden" name="_section" value="receive">
        <div class="row">
            <div class="col-md-4 mb-2"><label for="receive.blade-reference" class="form-label">Référence de livraison</label><input name="reference" value="{{ old('reference') }}" required maxlength="100" class="form-control" id="receive.blade-reference"></div>
            <div class="col-md-4 mb-2"><label for="receive.blade-supplier" class="form-label">Fournisseur</label><input name="supplier" value="{{ old('supplier') }}" required maxlength="255" class="form-control" id="receive.blade-supplier"></div>
            <div class="col-md-4 mb-2"><label for="receive.blade-received_at" class="form-label">Date de réception</label><input type="date" name="received_at" id="receive.blade-received_at" value="{{ old('received_at', now()->toDateString()) }}" required class="form-control"></div>
            <div class="col-md-4 mb-2"><label for="batch-type" class="form-label">Type</label><select name="card_type" id="batch-type" class="form-select"><option value="epargne" @selected(old('card_type')==='epargne')>Épargne</option><option value="simple" @selected(old('card_type')==='simple')>Compte courant</option></select></div>
            <div class="col-md-4 mb-2" data-range><label for="number-start" class="form-label">Premier numéro</label><input type="number" min="1" max="999999999" name="number_start" id="number-start" value="{{ old('number_start') }}" class="form-control"></div>
            <div class="col-md-4 mb-2" data-range><label for="number-end" class="form-label">Dernier numéro (inclus)</label><input type="number" min="1" max="999999999" name="number_end" id="number-end" value="{{ old('number_end') }}" class="form-control"></div>
            <div class="col-md-4 mb-2" id="quantity-field"><label for="receive.blade-quantity" class="form-label">Quantité de carnets courants</label><input type="number" min="1" max="10000" name="quantity" value="{{ old('quantity') }}" class="form-control" id="receive.blade-quantity"></div>
        </div><p id="range-count"></p><button class="btn btn-primary">Enregistrer la livraison</button></form>
    </div>
    @endcan
@cannot('recevoir-stock-carnets')<div class="alert alert-info">Permission requise : recevoir-stock-carnets.</div>@endcannot
