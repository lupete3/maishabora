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
    @include('partials.pdf-header', ['reportTitle' => 'Rapport de Performance des Agents'])

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
        {{ $company->name ?? config('app.name') }} - Système de Gestion Intégré - Page 1
    </div>
</body>

</html>
