@extends('layouts.pdf')

@section('title', 'Rapport des Anomalies de Carnets')
@section('report-title', 'RAPPORT DES ANOMALIES DE CARNETS')

@section('extra-style')
    .kpi-table {
        width: 100%;
        margin-bottom: 20px;
    }
    .kpi-box {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: center;
    }
    .kpi-value {
        font-size: 14px;
        font-weight: bold;
        display: block;
    }
    .kpi-label {
        font-size: 9px;
        color: #666;
    }
    .text-danger { color: #dc3545; }
    .text-primary { color: #007bff; }
    .text-success { color: #28a745; }
@endsection

@section('content')
    <div style="margin-bottom: 15px;">
        @if($search)
            <strong>Filtre de recherche :</strong> "{{ $search }}"<br>
        @endif
        <strong>Nombre d'anomalies détectées :</strong> {{ $totalCount }}
    </div>

    {{-- KPI Cards equivalent for PDF --}}
    <table class="kpi-table">
        <tr>
            <td class="kpi-box">
                <span class="kpi-label">Déposé USD</span>
                <span class="kpi-value text-primary">{{ number_format($totalSavedUSD, 2) }}</span>
            </td>
            <td class="kpi-box">
                <span class="kpi-label">Déposé CDF</span>
                <span class="kpi-value text-primary">{{ number_format($totalSavedCDF, 2) }}</span>
            </td>
            <td class="kpi-box">
                <span class="kpi-label">Solde Comptes USD</span>
                <span class="kpi-value text-success">{{ number_format($totalBalanceUSD, 2) }}</span>
            </td>
            <td class="kpi-box">
                <span class="kpi-label">Solde Comptes CDF</span>
                <span class="kpi-value text-success">{{ number_format($totalBalanceCDF, 2) }}</span>
            </td>
            <td class="kpi-box" style="border: 2px solid #dc3545;">
                <span class="kpi-label text-danger">Écart Total USD</span>
                <span class="kpi-value text-danger">{{ number_format($ecartUSD, 2) }}</span>
            </td>
            <td class="kpi-box" style="border: 2px solid #dc3545;">
                <span class="kpi-label text-danger">Écart Total CDF</span>
                <span class="kpi-value text-danger">{{ number_format($ecartCDF, 2) }}</span>
            </td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th>Membre</th>
                <th>Code Carnet</th>
                <th class="text-end">Dépôt Carnet</th>
                <th class="text-end">Solde Compte</th>
                <th class="text-end">Écart</th>
                <th class="text-center">Devise</th>
            </tr>
        </thead>
        <tbody>
            @foreach($anomalies as $card)
                @php
                    $totalSaved = $card->contributions->sum('amount');
                    $firstContribution = $card->contributions->sortBy('created_at')->first();
                    if ($firstContribution) {
                        $totalSaved -= $firstContribution->amount;
                    }
                    $account = $card->member->accounts
                        ->where('currency', $card->currency)
                        ->where('type', 'savings')
                        ->first() 
                        ?? $card->member->accounts
                            ->where('currency', $card->currency)
                            ->where('type', 'current')
                            ->first();

                    $balance = $account ? $account->balance : 0;
                    $diff = $totalSaved - $balance;
                @endphp
                <tr>
                    <td>
                        {{ $card->member->name }} {{ $card->member->postnom }}<br>
                        <small style="color: #666;">{{ $card->member->code }}</small>
                    </td>
                    <td class="text-center">{{ $card->code }}</td>
                    <td class="text-end bold text-danger">{{ number_format($totalSaved, 2) }}</td>
                    <td class="text-end bold text-primary">{{ number_format($balance, 2) }}</td>
                    <td class="text-end bold text-danger">+ {{ number_format($diff, 2) }}</td>
                    <td class="text-center">{{ $card->currency }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
