@extends('layouts.backend')

@section('title', 'Expert IA - Crédits')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
    <div class="flex-grow-1">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">IA /</span> Performance Crédit</h4>
        @livewire('reports.ai-credit-report')
    </div>
@endsection
