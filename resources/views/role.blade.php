@extends('layouts.backend')

@section('title', 'Gestion Rôles Utilisateurs')

@section('content')

<div class="container-xxl flex-grow-1 container-p-y">

    <livewire:roles.role-permission-component />

</div>


@endsection
