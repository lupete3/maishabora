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

        th,
        td {
            border: 1px solid #aaa;
            padding: 2px;
            font-size: 7px;
            text-align: left;
        }

        th {
            background-color: #f1c206;
        }

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

    @include('partials.pdf-header', ['reportTitle' => 'RAPPORT DES TRANSACTIONS'])
    <p style="text-align: center; margin-top: -10px;">
        <strong>Membre :</strong> {{ $member->name . ' ' . $member->postnom . ' ' . $member->prenom ?? '' }} (ID:
        {{ $member->code }})
    </p>

    @if ($member->visible_account)
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
    @endif

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Devise</th>
                <th>Montant</th>
                @if ($member->visible_account)
                    <th>Solde après</th>
                @endif
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
                    @if ($member->visible_account)
                        <td>{{ number_format($t->balance_after, 2) }}</td>
                    @endif
                    <td>{{ $t->description ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $member->visible_account ? 6 : 5 }}" style="text-align:center;">Aucune transaction
                        trouvée.</td>
                </tr>
            @endforelse
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
        <h4 style="text-align:center;">Récapitulatif des Transactions par Type</h4>

        @foreach($groupedTotals as $currency => $types)
            <div class="totals" style="margin-bottom: 20px;">
                <h5 style="text-align:center; margin-bottom: 5px; color: #f1c206;">Devise : {{ $currency }}</h5>
                <table style="width: 70%; margin: 0 auto;">
                    <thead>
                        <tr>
                            <th>Type de Transaction</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $currencyTotal = 0; @endphp
                        @foreach($types as $type => $amount)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $type)) }}</td>
                                <td style="text-align: right;">{{ number_format($amount, 2) }}</td>
                            </tr>
                            @php $currencyTotal += $amount; @endphp
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: bold; background-color: #eee;">
                            <td>TOTAL NET (Flux)</td>
                            <td style="text-align: right;">{{ number_format($currencyTotal, 2) }} {{ $currency }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endforeach
    </div>

    <div class="footer">
        Généré par {{ auth()->user()->name }} - {{ $company->name ?? config('app.name') }}
    </div>

</body>

</html>
