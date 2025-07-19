<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transactions Agent</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 2px; font-size: 8px;}
        th { background-color: #f1c206; }
    </style>
</head>
<body>
    <h4>Transactions de {{ $user->name }} {{ $user->postnom }} ({{ ucfirst($filter) }})</h4>
    <p>Date du rapport : {{ now()->format('d/m/Y H:i') }}<br>
    Nombre total de transactions : <strong>{{ $transactionCount }}</strong></p>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Montant</th>
                <th>Devise</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $t)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($t->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ ucfirst($t->type) }}</td>
                    <td>{{ number_format($t->amount, 2) }}</td>
                    <td>{{ $t->currency }}</td>
                    <td>{{ $t->description }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3 style="margin-top: 30px;">Récapitulatif des totaux par devise</h3>
    <table>
        <thead>
            <tr>
                <th>Devise</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($totalByCurrency as $currency => $total)
                <tr>
                    <td>{{ $currency }}</td>
                    <td>{{ number_format($total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
