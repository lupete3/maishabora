@extends('layouts.backend')

@section('title', 'Statistiques des Collecteurs')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <livewire:comptabilite.collector-indicators />
    </div>
@endsection
