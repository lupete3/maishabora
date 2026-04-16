@extends('layouts.pdf')

@section('title', 'Bilan Financier')
@section('report-title')
    BILAN AU {{ \Carbon\Carbon::parse($dateReference)->format('d/m/Y') }} ({{ $devise }})
@endsection

@section('extra-style')
    .content-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .section-header {
        background-color: #f0f0f0;
        font-weight: bold;
        text-align: left;
        padding: 5px;
        border-bottom: 1px solid #ccc;
        font-size: 11px;
    }

    .item-row td {
        padding: 4px;
        border-bottom: 1px solid #eee;
    }

    .total-row td {
        padding: 5px;
        border-top: 1px solid #000;
        border-bottom: 2px solid #000;
        font-weight: bold;
        background-color: #f9f9f9;
    }

    .main-section-title {
        font-size: 12px;
        font-weight: bold;
        background-color: #333;
        color: white;
        padding: 5px;
        margin-top: 15px;
    }

    .two-column {
        width: 100%;
    }

    .column {
        display: inline-block;
        width: 48%;
        vertical-align: top;
        padding: 0 1%;
    }
@endsection

@section('metadata')
    <strong>Date :</strong> {{ now()->format('d/m/Y') }}<br>
    <strong>Généré par :</strong> {{ $user->name }}
@endsection

@section('content')
    <div class="two-column">
        <!-- ACTIF -->
        <div class="column">
            <div class="main-section-title">ACTIF</div>
            <table class="content-table">
                @foreach($actifs as $classe => $comptesClasse)
                    <tr>
                        <td colspan="2" class="section-header">
                            @if($classe == 2) CLASSE 2: ACTIFS IMMOBILISÉS
                            @elseif($classe == 3) CLASSE 3: STOCKS
                            @elseif($classe == 4) CLASSE 4: TIERS (CRÉANCES)
                            @elseif($classe == 5) CLASSE 5: TRÉSORERIE EPARGNE
                            @else CLASSE {{ $classe }}
                            @endif
                        </td>
                    </tr>
                    @foreach($comptesClasse as $compte)
                        <tr class="item-row">
                            <td>{{ $compte['code'] }} - {{ $compte['intitule'] }}</td>
                            <td class="text-end">{{ number_format($compte['montant'], 2, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                @endforeach
                <tr class="total-row">
                    <td>TOTAL ACTIF</td>
                    <td class="text-end">{{ number_format($totalActifs, 2, ',', ' ') }}</td>
                </tr>
            </table>
        </div>

        <!-- PASSIF -->
        <div class="column">
            <div class="main-section-title">PASSIF & CAPITAUX PROPRES</div>
            <table class="content-table">
                @foreach($passifs as $classe => $comptesClasse)
                    <tr>
                        <td colspan="2" class="section-header">
                            @if($classe == 1) CLASSE 1: RESSOURCES DURABLES
                            @elseif($classe == 4) CLASSE 4: TIERS (DETTES)
                            @elseif($classe == 5) CLASSE 5: TRÉSORERIE PASSIF
                            @else CLASSE {{ $classe }}
                            @endif
                        </td>
                    </tr>
                    @foreach($comptesClasse as $compte)
                        <tr class="item-row">
                            <td>{{ $compte['code'] }} - {{ $compte['intitule'] }}</td>
                            <td class="text-end">{{ number_format($compte['montant'], 2, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                @endforeach
                <tr class="total-row">
                    <td>TOTAL PASSIF</td>
                    <td class="text-end">{{ number_format($totalPassifs, 2, ',', ' ') }}</td>
                </tr>
            </table>
        </div>
    </div>

    @if(!$isBalanced)
        <div style="margin-top: 20px; color: red; font-weight: bold; text-align: center; border: 1px solid red; padding: 10px;">
            ⚠️ BILAN NON ÉQUILIBRÉ (Écart: {{ number_format($totalActifs - $totalPassifs, 2, ',', ' ') }})
        </div>
    @endif
@endsection

@section('footer')
    <div class="footer">
        Arrêté à la date du {{ \Carbon\Carbon::parse($dateReference)->format('d/m/Y') }} en {{ $devise }}.<br>
        Document généré automatiquement par le système Maïsha Bora.
    </div>
@endsection