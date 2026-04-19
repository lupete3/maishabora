<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Rapport des Transactions</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 5px;
            color: #000;
        }

        .footer {
            text-align: center;
            margin-top: 50px
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .text-start {
            text-align: left;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table td,
        .table th {
            border: 1px solid #000;
            padding: 2px;
            font-size: 8px;
        }

        .signature {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }

        .signature-block {
            width: 45%;
            text-align: center;
        }

        th {
            background-color: #f1c206;
        }

        .section-title {
            margin-top: 10px;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }

        .totals p {
            margin: 2px 0;
        }

        .logo {
            width: 80px;
        }

        .balances {
            width: 49%;
            display: inline-block;
            vertical-align: top;
        }
    </style>
</head>

<body>

    @include('partials.pdf-header', ['reportTitle' => 'RAPPORT DES TRANSACTIONS AGENT'])

    <h4>Transactions de {{ $user->name }} {{ $user->postnom }} {{ $user->prenom }} ({{ ucfirst($periodLabel) }})</h4>
    Nombre total de transactions : <strong>{{ $transactionCount }}</strong></p>

    <table class="table" border="1" cellspacing="0" cellpadding="4">
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
            @foreach ($transactions as $t)
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

    @php
        $groupedTotals = $transactions->groupBy('currency')->map(function ($currencyGroup) {
            return $currencyGroup->groupBy('type')->map(function ($typeGroup) {
                return $typeGroup->sum('amount');
            });
        });
    @endphp

    <div class="totals-container">
        @foreach($groupedTotals as $currency => $types)
            <div class="balances">
                <h3 style="margin-top: 30px;">Recap : {{ $currency }}</h3>
                <table class="table" border="1" cellspacing="0" cellpadding="4">
                    <thead>
                        <tr>
                            <th>Type de Transaction</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $currencyTotal = 0; @endphp
                        @foreach($types as $type => $amount)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $type)) }}</td>
                                <td class="text-end">{{ number_format($amount, 2) }}</td>
                            </tr>
                            @php $currencyTotal += $amount; @endphp
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: bold; background-color: #eee;">
                            <td>TOTAL NET ({{ $currency }})</td>
                            <td class="text-end">{{ number_format($currencyTotal, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endforeach
    </div>
    <div class="balances">
        <h3 style="margin-top: 30px;">Solde disponible en caisse</h3>
        <table class="table" border="1" cellspacing="0" cellpadding="4">
            <thead>
                <tr>
                    <th>Devise</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($agentAccounts as $agent)
                    @foreach ($agent->agentAccounts as $index => $acc)
                        <tr>
                            <td>
                                {{ $acc->currency }}
                            </td>
                            <td>
                                {{ number_format($acc->balance, 2) }}
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Rapport généré le {{ now()->format('d/m/Y H:i') }} par {{ Auth::user()->name }} {{ Auth::user()->postnom }} -
        {{ $company->name ?? config('app.name') }}
    </div>

</body>

</html>
