@extends('layouts.receipt')

@section('receipt-title', 'RECU DE DECAISSEMENT')

@section('content')
    <div class="row">
        <span>Date:</span>
        <span>{{ $transaction->created_at->format('d/m/Y H:i') }}</span>
    </div>
    <div class="row">
        <span>Ref:</span>
        <span>#{{ $transaction->id }}</span>
    </div>
    <div class="row">
        <span>Agent:</span>
        <span>{{ $transaction->user->name . ' ' . $transaction->user->postnom }}</span>
    </div>

    <div class="divider"></div>

    <div style="margin-bottom: 5px;">
        <span class="bold">Motif:</span><br>
        {{ $transaction->disbursementType->name ?? 'Autre' }}
    </div>

    <div style="margin-bottom: 5px;">
        <span class="bold">Description:</span><br>
        {{ $transaction->description }}
    </div>

    <div class="divider"></div>

    <div class="row bold" style="font-size: 12px;">
        <span>MONTANT:</span>
        <span>{{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}</span>
    </div>
@endsection

@section('footer-extra')
    <br>Signature Agent
    <br><br><br>
    .....................
@endsection
