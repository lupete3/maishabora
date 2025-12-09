@extends('layouts.backend')

@section('title', 'Tableau de bord Agent')

@section('content')

<div class="container-xxl flex-grow-1 container-p-y">
    @livewire('repayment-report')
</div>  

@endsection