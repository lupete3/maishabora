<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Bilan Financier</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 20px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo {
            width: 80px;
            height: auto;
        }

        .company-info {
            text-align: center;
        }

        .report-info {
            text-align: right;
        }

        .report-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
        }

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
            display: table;
            table-layout: fixed;
        }

        .column {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            padding: 0 1%;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-bold {
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }
    </style>
</head>

<body>

    <table class="header-table">
        <tr>
            <td width="20%">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.jpg'))) }}"
                    class="logo">
            </td>
            <td width="60%" class="company-info">
                <h2>{{ config('app.name') }}</h2>
                <div>{{ env('APP_ADRESS') }}</div>
                <div>Tél: {{ env('APP_PHONE') }} | Email: {{ env('APP_EMAIL') }}</div>
            </td>
            <td width="20%" class="report-info">
                Date: {{ now()->format('d/m/Y') }}<br>
                Généré par: {{ $user->name }}
            </td>
        </tr>
    </table>

    <div class="report-title">BILAN AU {{ \Carbon\Carbon::parse($dateReference)->format('d/m/Y') }} ({{ $devise }})
    </div>

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
                            <td class="text-right">{{ number_format($compte['montant'], 2, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                @endforeach
                <tr class="total-row">
                    <td>TOTAL ACTIF</td>
                    <td class="text-right">{{ number_format($totalActifs, 2, ',', ' ') }}</td>
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
                            <td class="text-right">{{ number_format($compte['montant'], 2, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                @endforeach
                <tr class="total-row">
                    <td>TOTAL PASSIF</td>
                    <td class="text-right">{{ number_format($totalPassifs, 2, ',', ' ') }}</td>
                </tr>
            </table>
        </div>
    </div>

    @if(!$isBalanced)
        <div
            style="margin-top: 20px; color: red; font-weight: bold; text-align: center; border: 1px solid red; padding: 10px;">
            ⚠️ BILAN NON ÉQUILIBRÉ (Écart: {{ number_format($totalActifs - $totalPassifs, 2, ',', ' ') }})
        </div>
    @endif

    <div class="footer">
        Arrêté à la date du {{ \Carbon\Carbon::parse($dateReference)->format('d/m/Y') }} en {{ $devise }}.
        Document généré automatiquement par le système Maïsha Bora.
    </div>

</body>

</html>