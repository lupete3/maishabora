@extends('layouts.backend')

@section('title', 'Expert IA - Ventes')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">IA /</span> Performance Ventes</h4>
        @livewire('reports.ai-sales-report')
    </div>
@endsection