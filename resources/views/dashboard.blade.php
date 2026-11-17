@extends('layouts.backend')

@section('title', 'Tableaude bord')

@section('content')

@can('afficher-tableaudebord-client', App\Models\User::class)
    <livewire:members.member-dashboard />
@endcan

@can('afficher-tableaudebord-admin', App\Models\User::class)
    <livewire:admin.global-credit-dashboard />

@endcan

@can('afficher-tableaudebord-receptionist', App\Models\User::class)
    <livewire:receptionist.receptionist-dashboard />
@endcan

@can('afficher-tableaudebord-recouvreur', App\Models\User::class)
    <div class="container">
        <h3 class="mt-4 mb-0">Caisse des agents</h3>

        <livewire:agent.agent-dashboard />

        {{-- Situation des crédits de l'agent : placée à côté de la caisse et non à
             l'intérieur du composant (imbriquée, elle était détruite au premier clic et
             bloquait les fenêtres de la caisse). --}}
        <div class="mb-4 mt-4">
            <livewire:credit.credit-overview />
        </div>
    </div>
@endcan

@endsection
