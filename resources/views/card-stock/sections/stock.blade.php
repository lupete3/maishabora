    <form method="GET" id="card-stock-filters" class="card p-3 mb-3"><input type="hidden" name="section" value="{{ $section }}"><div class="row">
        <div class="col-md-2"><label for="stock.blade-type" class="form-label">Type</label><select name="type" class="form-select" id="stock.blade-type"><option value="">Tous</option><option value="epargne" @selected(request('type')==='epargne')>Épargne</option><option value="simple" @selected(request('type')==='simple')>Compte courant</option></select></div>
        <div class="col-md-2"><label for="stock.blade-status" class="form-label">Statut</label><select name="status" class="form-select" id="stock.blade-status"><option value="">Tous</option>@foreach($statuses as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3"><label for="stock.blade-collector" class="form-label">Agent (collecteur ou admin)</label><select name="collector" class="form-select" id="stock.blade-collector"><option value="">Tous</option>@foreach($collectors as $collector)<option value="{{ $collector->id }}" @selected(request('collector')==$collector->id)>{{ $collector->name }} {{ $collector->postnom }} ({{ $collector->role === 'admin' ? 'Admin' : 'Collecteur' }})</option>@endforeach</select></div>
        <div class="col-md-3"><label for="stock.blade-batch" class="form-label">Lot</label><select name="batch" class="form-select" id="stock.blade-batch"><option value="">Tous</option>@foreach($batches as $batch)<option value="{{ $batch->id }}" @selected(request('batch')==$batch->id)>{{ $batch->reference }} — {{ $batch->supplier }} ({{ $batch->quantity }})</option>@endforeach</select></div>
        <div class="col-12 mt-3"><label for="card-stock-search" class="form-label">Rechercher un carnet</label>
            <div class="input-group"><span class="input-group-text"><i class="bx bx-search" aria-hidden="true"></i></span>
            <input type="search" id="card-stock-search" name="search" value="{{ request('search') }}" maxlength="100" class="form-control" placeholder="Numéro, ID, code de vente, membre, agent, lot ou fournisseur">
            <button type="submit" class="btn btn-primary">Rechercher</button></div>
            <small class="text-muted">Recherche dans tout le stock, en tenant compte des filtres sélectionnés.</small>
        </div>
        <div class="col-md-3 mt-2"><label for="stock.blade-from" class="form-label">Réception depuis</label><input type="date" name="from" value="{{ request('from') }}" class="form-control" id="stock.blade-from"></div>
        <div class="col-md-3 mt-2"><label for="stock.blade-to" class="form-label">Réception jusqu'au</label><input type="date" name="to" value="{{ request('to') }}" class="form-control" id="stock.blade-to"></div>
    </div><div class="mt-2"><button class="btn btn-primary">Filtrer</button> <a href="{{ url()->current().'?section='.$section }}" class="btn btn-outline-secondary">Réinitialiser</a>
    @can('afficher-rapport-stock-carnets')<a href="{{ route('card-stock.export', request()->query()) }}" class="btn btn-outline-primary">Exporter CSV</a>@endcan</div></form>
    <form method="POST" action="{{ route('card-stock.move') }}" data-stock-confirm="Confirmer ce mouvement de carnets ?">@csrf
    <div class="card mb-3"><div class="table-responsive"><table class="table"><thead><tr><th>Sélection</th><th>Carnet</th><th>Type / lot</th><th>Statut</th><th>Agent</th><th>Membre / code</th><th>Prix</th><th>Mise quotidienne</th><th>Vente</th></tr></thead><tbody>
        @forelse($items as $item)<tr>
            <td>@if(!$report && in_array($item->status, ['in_stock','with_collector']))<input type="checkbox" name="items[]" value="{{ $item->id }}" aria-label="Sélectionner {{ $item->reference }}">@endif</td>
            <td><a href="{{ route('card-stock.show', $item) }}">{{ $item->reference }}</a>
            @if(!$report && $item->card_type === 'epargne')
            @can('modifier-numero-carnet')<a href="{{ route('card-stock.edit-number', $item) }}" class="d-block small mt-1">Modifier le numéro</a>@endcan
            @endif</td>
            <td>{{ $item->card_type==='epargne' ? 'Épargne' : 'Courant' }} / {{ $item->batch->reference }}</td>
            <td><x-card-stock-status :status="$item->status" /></td><td>{{ $item->collector?->name ?? '—' }} {{ $item->collector?->postnom ?? '—' }}</td>
            <td>{{ $item->sale?->member?->name ?? '—' }} {{ $item->sale?->member?->postnom ?? '—' }} {{ $item->sale?->member?->code ?? '—' }}<br>{{ $item->sale?->code }}</td>
            <td>{{ $item->sale?->price }} {{ $item->sale?->currency }}</td><td>{{ $item->sale?->subscription_amount }} {{ $item->sale?->currency }}</td>
            <td>{{ $item->sale?->created_at?->format('d/m/Y H:i') }}</td>
        </tr>@empty<tr><td colspan="9">Aucun carnet ne correspond à votre recherche. Ajustez les filtres ou enregistrez une réception.</td></tr>@endforelse
    </tbody></table></div></div>
    @unless($report)
    @canany(['distribuer-stock-carnets','corriger-stock-carnets'])
    <div class="card p-3 mb-3"><h5>Mouvement des carnets sélectionnés</h5><div class="row">
        <div class="col-md-3"><label for="stock.blade-action" class="form-label">Action</label><select name="action" class="form-select" id="stock.blade-action">
            @can('distribuer-stock-carnets')<option value="assign">Attribuer à l'agent</option>@endcan
            @can('corriger-stock-carnets')<option value="return">Retour au stock</option><option value="transfer">Transférer à l'agent</option><option value="lost">Déclarer perdus</option><option value="damaged">Déclarer détériorés</option>@endcan
        </select></div>
        <div class="col-md-3"><label for="stock.blade-collector_id" class="form-label">Agent destinataire (admin pour pertes et détériorations)</label><select name="collector_id" class="form-select" id="stock.blade-collector_id"><option value="">Sélectionner</option>@foreach($collectors as $collector)<option value="{{ $collector->id }}">{{ $collector->name }} {{ $collector->postnom }} ({{ $collector->role === 'admin' ? 'Admin' : 'Collecteur' }})</option>@endforeach</select></div>
        <div class="col-md-6"><label for="stock.blade-reason" class="form-label">Motif</label><input name="reason" required maxlength="1000" class="form-control" id="stock.blade-reason"></div>
    </div><div class="mt-3"><button class="btn btn-primary">Enregistrer le mouvement</button></div></div>
    @endcanany
    @endunless
    </form>
    {{ $items->links() }}
