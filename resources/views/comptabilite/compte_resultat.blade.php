@extends('layouts.backend')

@section('title', 'Compte de Résultat')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <livewire:comptabilite.income-statement />
    </div>
@endsection
