<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport des Transactions - {{ $member->name }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            margin: 10px;
            color: #222;
        }

        .header {
            text-align: center;
            margin-bottom: 5px;
        }

        .header h2 {
            margin: 0;
            font-size: 12px;
        }

        .header p {
            margin: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th, td { border: 1px solid #aaa; padding: 2px; font-size: 7px; text-align: left;}
        th { background-color: #f1c206; }
        .totals {
            margin-top: 10px;
        }

        .totals table {
            width: 50%;
            margin: 0 auto;
        }

        .footer {
            position: fixed;
            bottom: 5px;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #888;
        }

        .balances ul {
            list-style: none;
            padding: 0;
            margin: 10px auto;
            width: 50%;
        }

        .balances li {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px dashed #ccc;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>RAPPORT DES TRANSACTIONS</h2>
        <p><strong>{{ config('app.name') }}</strong></p>
        <p><strong>Membre :</strong> {{ $member->name.' '.$member->postnom.' '.$member->prenom ?? '' }} (ID: {{ $member->code }})</p>
        <p><strong>Date d'impression :</strong> {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="balances">
        <h4 style="text-align:center;">Soldes Actuels</h4>
        <ul>
            @foreach(['USD', 'CDF'] as $curr)
                @php
                    $account = $member->accounts->firstWhere('currency', $curr);
                @endphp
                <li>
                    <span>{{ $curr }}</span>
                    <span>{{ number_format($account?->balance ?? 0, 2) }} {{ $curr }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Devise</th>
                <th>Montant</th>
                <th>Solde après</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $t)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($t->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ ucfirst($t->type) }}</td>
                    <td>{{ $t->currency }}</td>
                    <td>{{ number_format($t->amount, 2) }}</td>
                    <td>{{ number_format($t->balance_after, 2) }}</td>
                    <td>{{ $t->description ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;">Aucune transaction trouvée.</td></tr>
            @endforelse
        </tbody>
    </table>

    @php
        $totalByCurrency = $transactions->groupBy('currency')->map(function ($group) {
            return $group->sum('amount');
        });
    @endphp

    <div class="totals">
        <h4 style="text-align:center;">Totaux par devise</h4>
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
    </div>

    <div class="footer">
        Généré par {{ auth()->user()->name }} - {{ config('app.name') }}
    </div>

</body>
</html>
