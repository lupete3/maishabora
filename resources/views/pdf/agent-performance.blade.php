<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Performance des Agents</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            color: #000;
            margin: 10px;
        }

        .header {
            padding-bottom: 5px;
        }

        .logo {
            width: 80px;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        th {
            background-color: #f1c206;
            color: #000;
            text-align: left;
            padding: 6px;
            font-size: 8pt;
            font-weight: bold;
            border: 1px solid #000;
        }

        td {
            padding: 6px;
            border: 1px solid #000;
            font-size: 8pt;
        }

        .fw-bold {
            font-weight: bold;
        }

        .text-success {
            color: #27ae60;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 7pt;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        .totals-row {
            background-color: #f1f2f6;
            font-weight: bold;
        }

        .currency-label {
            font-size: 7pt;
            color: #555;
            font-weight: normal;
        }
    </style>
</head>

<body>
    <div class="header">
        <table style="width:100%;">
            <tr>
                <td style="width: 15%;">
                    @if(file_exists(public_path('logo.jpg')))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.jpg'))) }}"
                            class="logo" style="width: 80px;">
                    @elseif(file_exists(public_path('assets/img/logo.jpg')))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/img/logo.jpg'))) }}"
                            class="logo" style="width: 80px;">
                    @endif
                </td>
                <td style="width: 60%; text-align:center;">
                    <h2 style="margin: 0; font-size: 16px;">{{ strtoupper(config('app.name')) }}</h2>
                    <p style="margin: 0; font-size: 10px;">Adresse :
                        {{ env('APP_ADRESS', 'Avenue Industrielle, Bukavu') }}
                    </p>
                    <p style="margin: 0; font-size: 10px;">Tel : {{ env('APP_PHONE', '+243 ...') }} – Email : {{
                        env('APP_EMAIL', 'info@maishabora.com') }}</p>
                </td>
                <td style="width: 25%; text-align:right; font-size: 9px;">
                    <strong>Date :</strong> {{ now()->format('d/m/Y') }}<br>
                    <strong>Heure :</strong> {{ now()->format('H:i') }}<br>
                    <strong>Généré par :</strong> {{ auth()->user()->name }}
                </td>
            </tr>
        </table>
        <hr style="margin: 10px 0; border-bottom: 2px solid #ed8d0f;">
        <h3 class="text-center" style="text-decoration: underline; margin-bottom: 5px; text-transform: uppercase;">
            Rapport de Performance des Agents
        </h3>
    </div>

    <div class="filters-info"
        style="font-size: 8pt; background: #f8f9fa; padding: 5px; border: 1px solid #ddd; margin-bottom: 10px;">
        <strong>Période :</strong> du {{ \Carbon\Carbon::parse($filterDateFrom)->format('d/m/Y') }} au
        {{ \Carbon\Carbon::parse($filterDateTo)->format('d/m/Y') }} |
        <strong>Devise :</strong> {{ $filterCurrency == 'all' ? 'Toutes' : $filterCurrency }} |
        <strong>Simulation Marge :</strong> {{ $marginPercent }}%
    </div>

    <table>
        <thead>
            <tr>
                <th>Agent</th>
                <th class="text-center">Carnets</th>
                <th class="text-end">Vente Carnets</th>
                <th class="text-end">Retenues (Profit)</th>
                <th class="text-end">Collectes (Mises)</th>
                <th class="text-end">Gains Simulés</th>
            </tr>
        </thead>
        <tbody>
            @foreach($agents as $agent)
                @php
                    $margin = (float) ($marginPercent ?: 0);
                    $earningsUsd = ($agent->metrics['retained_usd'] * $margin) / 100;
                    $earningsCdf = ($agent->metrics['retained_cdf'] * $margin) / 100;
                @endphp
                <tr>
                    <td>{{ $agent->name }} {{ $agent->postnom }}</td>
                    <td class="text-center">{{ $agent->metrics['card_count'] }}</td>
                    <td class="text-end">
                        {{ number_format($agent->metrics['card_revenue_usd'], 2) }} USD<br>
                        <span class="currency-label">{{ number_format($agent->metrics['card_revenue_cdf'], 0, ',', ' ') }}
                            CDF</span>
                    </td>
                    <td class="text-end">
                        {{ number_format($agent->metrics['retained_usd'], 2) }} USD<br>
                        <span class="currency-label">{{ number_format($agent->metrics['retained_cdf'], 0, ',', ' ') }}
                            CDF</span>
                    </td>
                    <td class="text-end">
                        {{ number_format($agent->metrics['collection_usd'], 2) }} USD<br>
                        <span class="currency-label">{{ number_format($agent->metrics['collection_cdf'], 0, ',', ' ') }}
                            CDF</span>
                    </td>
                    <td class="text-end text-success fw-bold">
                        {{ number_format($earningsUsd, 2) }} USD<br>
                        <span class="currency-label">{{ number_format($earningsCdf, 0, ',', ' ') }} CDF</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td>TOTAL GENERAL</td>
                <td class="text-center">{{ $totals['cards'] }}</td>
                <td class="text-end">
                    {{ number_format($totals['card_revenue_usd'], 2) }} USD<br>
                    <span class="currency-label">{{ number_format($totals['card_revenue_cdf'], 0, ',', ' ') }}
                        CDF</span>
                </td>
                <td class="text-end">
                    {{ number_format($totals['retained_usd'], 2) }} USD<br>
                    <span class="currency-label">{{ number_format($totals['retained_cdf'], 0, ',', ' ') }} CDF</span>
                </td>
                <td class="text-end">
                    {{ number_format($totals['collection_usd'], 2) }} USD<br>
                    <span class="currency-label">{{ number_format($totals['collection_cdf'], 0, ',', ' ') }} CDF</span>
                </td>
                <td class="text-end text-success">
                    @php
                        $margin = (float) ($marginPercent ?: 0);
                    @endphp
                    {{ number_format(($totals['retained_usd'] * $margin) / 100, 2) }} USD<br>
                    <span
                        class="currency-label">{{ number_format(($totals['retained_cdf'] * $margin) / 100, 0, ',', ' ') }}
                        CDF</span>
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        {{ config('app.name') }} - Système de Gestion Intégré - Page 1
    </div>
</body>

</html>