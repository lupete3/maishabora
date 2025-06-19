<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Rapport des crédits en retard</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }

        th {
            background: #eee;
        }
    </style>
</head>

<body>
    <h3>Rapport global des crédits avec retards</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Membre</th>
                <th>Date Crédit</th>
                <th>Montant</th>
                <th>Solde</th>
                <th>Pénalités</th>
                <th>% Pénalités</th>
                <th>Jours Retard</th>
                <th>1-30j</th>
                <th>31-60j</th>
                <th>61-90j</th>
                <th>91-180j</th>
                <th>181-360j</th>
                <th>361-720j</th>
                <th>>720j</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($details as $d)
            <tr>
                <td>{{ $d['id'] }}</td>
                <td>{{ $d['member'] }}</td>
                <td>{{ $d['date'] }}</td>
                <td>{{ number_format($d['amount'], 2) }}</td>
                <td>{{ number_format($d['remaining'], 2) }}</td>
                <td>{{ number_format($d['penalty'], 2) }}</td>
                <td>{{ $d['penalty_percent'] }}%</td>
                <td>{{ $d['days_late'] }}</td>
                <td>{{ $d['range_1'] ? number_format($d['range_1'], 2) : '' }}</td>
                <td>{{ $d['range_2'] ? number_format($d['range_2'], 2) : '' }}</td>
                <td>{{ $d['range_3'] ? number_format($d['range_3'], 2) : '' }}</td>
                <td>{{ $d['range_4'] ? number_format($d['range_4'], 2) : '' }}</td>
                <td>{{ $d['range_5'] ? number_format($d['range_5'], 2) : '' }}</td>
                <td>{{ $d['range_6'] ? number_format($d['range_6'], 2) : '' }}</td>
                <td>{{ $d['range_7'] ? number_format($d['range_7'], 2) : '' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold;">
                <td colspan="3">Totaux</td>
                <td>{{ number_format($totaux['credit_amount'], 2) }}</td>
                <td>{{ number_format($totaux['remaining_balance'], 2) }}</td>
                <td>{{ number_format($totaux['total_penalty'], 2) }}</td>
                <td colspan="2"></td>
                <td>{{ number_format($totaux['range_1'], 2) }}</td>
                <td>{{ number_format($totaux['range_2'], 2) }}</td>
                <td>{{ number_format($totaux['range_3'], 2) }}</td>
                <td>{{ number_format($totaux['range_4'], 2) }}</td>
                <td>{{ number_format($totaux['range_5'], 2) }}</td>
                <td>{{ number_format($totaux['range_6'], 2) }}</td>
                <td>{{ number_format($totaux['range_7'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>

</html>
