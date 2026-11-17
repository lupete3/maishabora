@extends('layouts.backend')

@section('title', 'Tableau de bord Agent')

@section('content')

<div class="container-xxl flex-grow-1 container-p-y">

    {{--
        Les composants « Performance agents » et « Tableau de bord crédit » sont placés ICI,
        à côté de la caisse et non à l'intérieur : quand ils étaient imbriqués dans
        agent-dashboard, chaque rafraîchissement de la caisse (ouverture d'une fenêtre)
        les détruisait, puis le clic suivant plantait (« Component not found ») et le
        bouton de validation restait bloqué sur son sablier.
    --}}
    <div class="d-flex align-items-center justify-content-between mt-4">
        <h3 class="m-0">Caisse des agents</h3>
        @can('afficher-caisse-agent')
            <livewire:agent.agent-performance />
        @endcan
    </div>

    <livewire:agent.agent-dashboard lazy />

    @can('afficher-tableaudebord-recouvreur')
        <div class="mb-4 mt-4">
            <livewire:credit.credit-overview />
        </div>
    @endcan

</div>


@endsection
