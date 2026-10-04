    <div class="row">@foreach($statuses as $key=>$label)<div class="col-md-4 mb-3"><div class="card p-3">{{ $label }} <strong>{{ $counts[$key] ?? 0 }}</strong></div></div>@endforeach</div>
    <p class="text-muted">Synthèse selon les filtres de l’inventaire. <a href="{{ route('card-stock.index', array_merge(request()->except(['page', 'batches_page', 'section']), ['section'=>'stock'])) }}">Ajuster les filtres</a></p>
    <p>Total des exemplaires correspondant aux filtres : <strong>{{ $counts->sum() }}</strong>. Les ventes historiques sans suivi de stock sont exclues.</p>
    @foreach($salesTotals as $total)<p>Ventes non annulées : {{ $total->total }} carnets — {{ number_format($total->revenue, 2, ',', ' ') }} {{ $total->currency }}</p>@endforeach
    <div class="card p-3 mb-3"><h5>Carnets actuellement détenus par agent (tous lots)</h5>
        @forelse($collectorCounts as $count)<div>{{ $count->collector?->name ?? 'Collecteur inconnu' }} {{ $count->collector?->postnom ?? 'Collecteur inconnu' }} : <strong>{{ $count->total }}</strong></div>@empty<p>Aucun carnet attribué.</p>@endforelse
    </div>
