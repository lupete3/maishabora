@extends('layouts.backend')

@section('title', 'Gestion des Décaissements')

@section('content')

    <div class="container-xxl flex-grow-1 container-p-y">

        <livewire:disbursement.disbursement-management lazy />

    </div>

@endsection