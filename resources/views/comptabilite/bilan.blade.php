@extends('layouts.backend')

@section('title', 'Bilan')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <livewire:comptabilite.balance-sheet />
    </div>
@endsection
