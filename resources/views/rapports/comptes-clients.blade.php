@extends('layouts.backend')

@section('title', 'Rapports Clients')

@section('content')

<div class="container-xxl flex-grow-1 container-p-y">

    {{-- lazy : la page s'affiche tout de suite, les soldes se chargent ensuite --}}
    <livewire:repports.rapport-compte-clients lazy />

</div>

@endsection
