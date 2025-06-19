<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transactions Agent</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; }
        th { background-color: #f0f0f0; }
    </style>
</head>
<body>
    <h2>Transactions de {{ $user->name }} {{ $user->postnom }} ({{ ucfirst($filter) }})</h2>
    <p>Date : {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Montant</th>
                <th>Devise</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $t)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($t->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ ucfirst($t->type) }}</td>
                    <td>{{ number_format($t->amount, 2) }}</td>
                    <td>{{ $t->currency }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <h3 style="margin-top: 30px;">Récapitulatif</h3>
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
