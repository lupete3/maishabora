@extends('layouts.backend')

@section('title', 'Gestion Epargne Membres')

@section('content')

<div class="container-xxl flex-grow-1 container-p-y">

    <livewire:members.member-details :id="$id" lazy />

</div>


@endsection
