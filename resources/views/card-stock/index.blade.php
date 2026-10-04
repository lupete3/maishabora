@extends('layouts.backend')
@section('title', 'Stock des carnets')
@section('content')
<div class="container-xxl container-p-y">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h3 class="mb-0">{{ $report ? 'Rapport du stock des carnets' : 'Gestion du stock des carnets' }}</h3>
        @can('afficher-rapport-stock-carnets')
        <a href="{{ route('card-stock.export', request()->query()) }}" class="btn btn-success"><i class="bx bx-download"></i> Exporter CSV selon les filtres</a>
        @else
        <div><button type="button" class="btn btn-success" disabled>Exporter CSV selon les filtres</button>
        <small class="d-block text-muted">Permission requise : afficher-rapport-stock-carnets.</small></div>
        @endcan
    </div>

    @if($errors->any()) <div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
<style>
.stock-navigation{display:flex;gap:.35rem;overflow-x:auto;border-bottom:1px solid #dce3eb;padding:.25rem 0 0}
.stock-navigation-link{display:flex;align-items:center;gap:.5rem;white-space:nowrap;padding:.85rem 1rem;border-bottom:3px solid transparent;color:#526176;font-weight:600}
.stock-navigation-link:hover{background:#f0f4fa;color:#855024}.stock-navigation-link.is-active{color:#855024;border-bottom-color:#ad682f;background:#edf3fb}
.stock-navigation-link:focus-visible{outline:2px solid #855024;outline-offset:-2px}
@media(max-width:575px){.stock-navigation-link{padding:.75rem .8rem}}
</style>
    @php($statuses = \App\Support\CardStockLabels::STATUSES)
    @unless($report)
    <nav class="stock-navigation mb-4" aria-label="Sections du stock des carnets">
        @foreach(['overview' => ['bx-pie-chart-alt-2', 'Vue d’ensemble'], 'stock' => ['bx-book-content', 'Inventaire'], 'receive' => ['bx-package', 'Réception'], 'assign' => ['bx-user-plus', 'Attribution'], 'batches' => ['bx-layer', 'Lots & corrections']] as $key => [$icon, $label])
        <a href="{{ route('card-stock.index', array_merge(request()->except(['page', 'batches_page', 'section']), ['section' => $key])) }}" class="stock-navigation-link {{ $section === $key ? 'is-active' : '' }}" @if($section === $key) aria-current="page" @endif><i class="bx {{ $icon }}" aria-hidden="true"></i>{{ $label }}</a>
        @endforeach
    </nav>
    @endunless
    @if($report)
        @include('card-stock.sections.overview')
        @include('card-stock.sections.stock')
    @else
        <div class="mb-3"><h4 class="mb-1">{{ ['overview'=>'Vue d’ensemble', 'stock'=>'Inventaire des carnets', 'receive'=>'Réceptionner une livraison', 'assign'=>'Attribuer des carnets', 'batches'=>'Lots et corrections'][$section] }}</h4><p class="text-muted mb-0">{{ ['overview'=>'Suivez la disponibilité, les ventes et les carnets détenus par les agents.', 'stock'=>'Recherchez vos carnets et effectuez les mouvements sur votre sélection.', 'receive'=>'Enregistrez un nouveau lot avant sa distribution aux agents.', 'assign'=>'Distribuez une plage de numéros ou une quantité disponible à un agent.', 'batches'=>'Consultez les livraisons et corrigez leurs informations avec un motif.'][$section] }}</p></div>
        @include('card-stock.sections.'.$section)
    @endif
</div>
@unless($report)
<script>
document.addEventListener('DOMContentLoaded', () => {
    const type = document.getElementById('batch-type');
    if (!type) return;
    const refresh = () => {
        const savings = type.value === 'epargne';
        document.querySelectorAll('[data-range]').forEach(el => { el.hidden = !savings; el.querySelector('input').disabled = !savings; el.querySelector('input').required = savings; });
        const quantity = document.getElementById('quantity-field'); quantity.hidden = savings; quantity.querySelector('input').disabled = savings; quantity.querySelector('input').required = !savings;
        const first = Number(document.getElementById('number-start').value), last = Number(document.getElementById('number-end').value);
        document.getElementById('range-count').textContent = savings && first > 0 && last >= first ? `Quantité calculée : ${last-first+1} carnets (bornes incluses).` : '';
    };
    type.addEventListener('change', refresh);
    document.getElementById('number-start').addEventListener('input', refresh);
    document.getElementById('number-end').addEventListener('input', refresh);
    refresh();
});
</script>
@endunless
@include('card-stock.confirmations')
@endsection
