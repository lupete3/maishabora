@extends('layouts.backend')

@section('title', 'Tableau de bord Agent')

@section('content')

<div class="container-xxl flex-grow-1 container-p-y">
    <h3 class="mb-4 text-center">🧠 Résumé automatique des opérations du {{ $today }}</h3>

    <div class="card mb-3">
        <div class="card-header bg-success text-white">Dépôts</div>
        <div class="card-body">
            <p>{{ $summaryDeposits }}</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-danger text-white">Retraits</div>
        <div class="card-body">
            <p>{{ $summaryWithdrawals }}</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-primary text-white">Crédits</div>
        <div class="card-body">
            <p>{{ $summaryCredits }}</p>
        </div>
    </div>
</div>

@endsection
