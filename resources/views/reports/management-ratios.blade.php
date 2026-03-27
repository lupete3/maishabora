@extends('layouts.backend')

@section('title', 'Ratios de Gestion')

@section('content')
    <div class="container-fluid flex-grow-1">
        @livewire('reports.management-ratios')
    </div>
@endsection
