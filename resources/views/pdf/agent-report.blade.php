@extends('layouts.pdf')

@section('title', 'Rapport des Transactions')
@section('report-title', 'RAPPORT DES TRANSACTIONS')

@section('content')
    <div style="margin-bottom: 10px;">
        <strong>Agent concerné :</strong> {{ $agent->name ?? 'Tous les agents' }} {{ $agent->postnom ?? '' }}
        {{ $agent->prenom ?? '' }}<br>
        <strong>Devise :</strong> {{ $currency ?? 'Toutes' }}<br>
        <strong>Période :</strong>
        @if ($dateStart && $dateEnd)
            Du {{ \Carbon\Carbon::parse($dateStart)->format('d/m/Y') }} au
            {{ \Carbon\Carbon::parse($dateEnd)->format('d/m/Y') }}
        @else
            {{ ucfirst($period) }}
        @endif
    </div>

    <div class="totals" style="margin-bottom: 15px; background: #f8f9fa; padding: 10px; border-radius: 5px;">
        <h4 style="margin-top: 0; border-bottom: 1px solid #ccc; padding-bottom: 5px;">RÉSUMÉ DES TOTAUX</h4>
        @foreach($totalByCurrency as $curr => $amount)
            <p><strong>Total {{ $curr }} :</strong> {{ number_format($amount, 2, ',', ' ') }} {{ $curr }}</p>
        @endforeach
    </div>

    <table class="table" border="1" cellspacing="0" cellpadding="4">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th>Montant</th>
                <th>Devise</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $transaction)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($transaction->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ ucfirst($transaction->type) }}</td>
                    <td>{{ $transaction->description }}</td>
                    <td>{{ number_format($transaction->amount, 2, ',', ' ') }}</td>
                    <td>{{ strtoupper($transaction->currency) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Aucune transaction trouvée</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
