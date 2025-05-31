@extends('layouts.backend')

@section('title', 'Historique des depots et retraits')

@section('content')

<div class="container-xxl flex-grow-1 container-p-y">

    <livewire:members.member-financial-history />

</div>


@endsection
