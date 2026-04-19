<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Rapport des Provisions - {{ $date }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            color: #0056b3;
            font-size: 18px;
        }

        .summary-box {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .summary-box td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .summary-label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
            color: #666;
        }

        .summary-value {
            font-size: 14px;
            font-weight: bold;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: #f2f2f2;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        td {
            border: 1px solid #ddd;
            padding: 6px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        .section-title {
            background-color: #0056b3;
            color: white;
            padding: 5px 10px;
            margin-top: 20px;
            font-size: 12px;
        }

        .badge {
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 9px;
            color: white;
        }

        .bg-success {
            background-color: #28a745;
        }

        .bg-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .bg-primary {
            background-color: #007bff;
        }

        .bg-danger {
            background-color: #dc3545;
        }

        .bg-dark {
            background-color: #343a40;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: right;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>

<body>
    @include('partials.pdf-header', ['reportTitle' => 'RAPPORT PORTREFEUILLE À RISQUE & PROVISIONS (' . ($currency == 'all' ? 'Toutes Devises' : $currency) . ')'])

    <table class="summary-box">
        <tr>
            <td>
                <span class="summary-label">Encours Total</span>
                <span class="summary-value">{{ number_format($parIndicators['total_outstanding'], 2) }}</span>
            </td>
            <td>
                <span class="summary-label">PAR > 30 jours</span>
                <span class="summary-value">{{ number_format($parIndicators['par30'], 2) }}
                    ({{ number_format($parIndicators['par30_rate'], 2) }}%)</span>
            </td>
            <td>
                <span class="summary-label">PAR > 60 jours</span>
                <span class="summary-value">{{ number_format($parIndicators['par60'], 2) }}
                    ({{ number_format($parIndicators['par60_rate'], 2) }}%)</span>
            </td>
            <td>
                <span class="summary-label">Total Provisionné</span>
                <span class="summary-value" style="color: #dc3545;">{{ number_format($totaux['provision'], 2) }}</span>
            </td>
        </tr>
    </table>

    <div class="section-title">Synthèse par Classification (Ventilation du Capital)</div>
    <table>
        <thead>
            <tr>
                <th>Classification</th>
                <th class="text-center">Échéances</th>
                <th class="text-right">Capital ventilé</th>
                <th class="text-center">Taux</th>
                <th class="text-right">Prov. Calculée</th>
            </tr>
        </thead>
        <tbody>
            @foreach($statsByClassification as $class => $s)
                <tr>
                    <td>
                        @if($class == 'saine') Saine (Futur)
                        @elseif($class == '1-30') 1-30 jours de retard
                        @elseif($class == '31-60') 31-60 jours de retard
                        @elseif($class == '61-90') 61-90 jours de retard
                        @else +90 jours (Perte probable)
                        @endif
                    </td>
                    <td class="text-center">{{ $s['count'] }}</td>
                    <td class="text-right">{{ number_format($s['outstanding'], 2) }}</td>
                    <td class="text-center">
                        @php $r = match ($class) { 'saine' => 0, '1-30' => 10, '31-60' => 25, '61-90' => 50, '>90' => 100}; @endphp
                        {{ $r }}%
                    </td>
                    <td class="text-right font-bold">{{ number_format($s['provision'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="font-bold">
                <td colspan="2">TOTAL</td>
                <td class="text-right">{{ number_format($totaux['outstanding'], 2) }}</td>
                <td></td>
                <td class="text-right">{{ number_format($totaux['provision'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    @foreach(['1-30', '31-60', '61-90', '>90'] as $class)
        @if(isset($detailsByClassification[$class]) && $detailsByClassification[$class]->isNotEmpty())
            <div class="page-break" style="page-break-before: always;"></div>
            <div class="section-title">Détails Classification : {{ $class }} jours</div>
            <table>
                <thead>
                    <tr>
                        <th>Membre</th>
                        <th>Crédit #</th>
                        <th class="text-center">Retard</th>
                        <th class="text-right">Portion Capital</th>
                        <th class="text-right">Provision
                            ({{ match ($class) { '1-30' => 10, '31-60' => 25, '61-90' => 50, '>90' => 100} }}%)
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($detailsByClassification[$class] as $c)
                        <tr>
                            <td>{{ $c->user->name }} {{ $c->user->postnom }}</td>
                            <td>{{ $c->id }}</td>
                            <td class="text-center">{{ $c->days_overdue }} jours</td>
                            <td class="text-right">{{ number_format($c->outstanding_amount, 2) }} {{ $c->currency }}</td>
                            <td class="text-right">{{ number_format($c->provision_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    <div class="footer">
        {{ $company->name ?? config('app.name') }} - Système de Gestion Comptable - Généré le {{ date('d/m/Y H:i') }}
    </div>
</body>

</html>
