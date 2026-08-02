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
        <livewire:agent.agent-dashboard />
    </div>
@endcan

@endsection
