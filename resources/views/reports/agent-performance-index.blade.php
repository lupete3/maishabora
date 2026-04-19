@extends('layouts.backend')

@section('title', 'Performance des Agents')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            <span class="text-muted fw-light">Rapports /</span> Performance & Analyse des Collecteurs
        </h4>

        <livewire:reports.agent-performance-dashboard />
    </div>
@endsection
