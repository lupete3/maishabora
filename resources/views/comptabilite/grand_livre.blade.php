@extends('layouts.backend')

@section('title', 'Grand Livre')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <livewire:comptabilite.general-ledger />
    </div>
@endsection