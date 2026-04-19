<!DOCTYPE html>
<html lang="fr">

<head>
    @php
        header('Content-Type: text/html; charset=UTF-8');
    @endphp
    <meta charset="utf-8">
    <title>Plan de Remboursement</title>
    <style>
        body {
            font-family: sans-serif;
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
            text-align: right;
        }

        th {
            background-color: #f4cd22;
        }

        td:first-child,
        th:first-child {
            text-align: center;
        }
    </style>
</head>

<body>

    @include('partials.pdf-header', ['reportTitle' => 'PLAN DE REMBOURSEMENT DE CRÉDIT'])
    <p>
        <strong>Code Membre :</strong> {{ $user->code ?? '3420250000000000' }}<br>
        <strong>Nom Complet :</strong> {{ $user->name ?? 'MATATA' }} {{ $user->postnom ?? 'KODI' }}
        {{ $user->prenom ?? 'Jules' }} <br>
        <strong>Téléphone :</strong> {{ $user->telephone ?? '+243999999990' }} <br>
        <strong>Email :</strong> {{ $user->email ?? 'matatkodi@amb.com' }} <br>
        <strong>Montant du prêt :</strong> {{ number_format($amount, 2) }}<br>
        <strong>Taux d’intérêt :</strong> {{ number_format($rate, 2) }}%<br>
        <strong>Nombre d'échéances :</strong> {{ $installments }}<br>
        <strong>Date :</strong> {{ now()->format('d/m/Y H:i') }}
    </p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Capital Début</th>
                <th>Capital Remboursé</th>
                <th>Intérêt</th>
                <th>Mensualité</th>
                <th>Capital Restant</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($schedule as $line)
                <tr>
                    <td>{{ $line['no'] }}</td>
                    <td>{{ number_format($line['opening_capital'], 2) }}</td>
                    <td>{{ number_format($line['capital_repaid'], 2) }}</td>
                    <td>{{ number_format($line['interest'], 2) }}</td>
                    <td>{{ number_format($line['due'], 2) }}</td>
                    <td>{{ number_format($line['remaining_capital'], 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <td><strong>Totaux</strong></td>
                <td>-</td>
                <td><strong>{{ number_format(collect($schedule)->sum('capital_repaid'), 2) }}</strong></td>
                <td><strong>{{ number_format(collect($schedule)->sum('interest'), 2) }}</strong></td>
                <td><strong>{{ number_format(collect($schedule)->sum('due'), 2) }}</strong></td>
                <td>-</td>
            </tr>
        </tbody>
    </table>
</body>

</html>
