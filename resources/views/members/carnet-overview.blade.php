@extends('layouts.backend')

@section('title', 'Overview Carnets - ' . ($company->name ?? config('app.name')))

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        @livewire('members.carnet-overview')
    </div>
@endsection
