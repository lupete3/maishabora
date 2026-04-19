@extends('layouts.backend')

@section('title', 'Expert IA - Clients')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">IA /</span> Insights Clients</h4>
        @livewire('reports.ai-client-report')
    </div>
@endsection
