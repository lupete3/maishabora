@extends('layouts.backend')

@section('title', 'Ratios de Gestion')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Comptabilité /</span> Ratios de Gestion</h4>
        @livewire('reports.management-ratios')
    </div>
@endsection
