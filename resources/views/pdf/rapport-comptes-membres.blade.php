<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Rapport Comptes Membres</title>
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
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .table td,
        .table th {
            border: 1px solid;
            font-size: 8px;
            padding: 2px;
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
    </style>
</head>

<body>

    <div class="header" style="padding-bottom: 5px;">
        <table style="width:100%;">
            <tr>
                <td style="width: 15%;">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.jpg'))) }}"
                        class="logo" alt="Logo">
                </td>
                <td style="width: 60%; text-align:center;">
                    <h2 style="margin: 0; font-size: 14px;">{{ strtoupper(config('app.name')) }}</h2>
                    <p style="margin: 0;">Adresse : {{ env('APP_ADRESS') }}</p>
                    <p style="margin: 0;">Tel : {{ env('APP_PHONE') }} – Email : {{ env('APP_EMAIL') }}</p>
                </td>
                <td style="width: 25%; text-align:right; font-size: 9px;">
                    <strong>Date :</strong> {{ now()->format('d/m/Y') }}<br>
                    <strong>Heure :</strong> {{ now()->format('H:i') }}<br>
                    <strong>Agent :</strong><br>
                    {{ Auth::user()->name }} {{ Auth::user()->postnom }}
                </td>
            </tr>
        </table>
        <hr style="margin: 10px 0; border-bottom: 2px solid #ed8d0f;">
        <h3 class="text-center" style="text-decoration: underline; margin-bottom: 2px;">
            RAPPORT DES COMPTES MEMBRES
            @if(isset($accountType) && $accountType !== 'all')
                ({{ $accountType === 'current' ? 'COMPTE COURANT' : 'COMPTE ÉPARGNE' }})
            @endif
        </h3>
    @if(isset($alphabetRange) && $alphabetRange !== 'all')
        <p class="text-center" style="margin: 0;">Tranche : {{ $alphabetRange }}</p>
    @endif
    @if(isset($minBalance) && $minBalance > 0)
        <p class="text-center" style="margin: 0;">Solde Minimum : {{ number_format($minBalance, 2) }}</p>
    @endif
    </div>

    @php
        $typeLabel = '';
        if (isset($accountType) && $accountType !== 'all') {
            $typeLabel = '(' . ($accountType === 'current' ? 'Courant' : 'Épargne') . ')';
        }
    @endphp

    @if ($currencyFilter === 'all' || $currencyFilter === 'USD')
        <h3>Total USD {{ $typeLabel }} : {{ number_format($globalUsd, 2) }} $</h3>
    @endif
    @if ($currencyFilter === 'all' || $currencyFilter === 'CDF')
        <h3>Total CDF {{ $typeLabel }} : {{ number_format($globalCdf, 2) }} CDF</h3>
    @endif
    <h3>Total Membres listés : {{ $balances->count() }}</h3>

    <table class="table" border="1" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th>Code</th>
                <th>Membre</th>
                @if ($currencyFilter === 'all' || $currencyFilter === 'USD')
                    <th>Solde USD</th>
                @endif
                @if ($currencyFilter === 'all' || $currencyFilter === 'CDF')
                    <th>Solde CDF</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($balances as $balance)
                <tr>
                    <td>{{ $balance['member']->code }}</td>
                    <td>{{ $balance['member']->name . ' ' . $balance['member']->postnom . ' ' . $balance['member']->prenom }}</td>
                    @if ($currencyFilter === 'all' || $currencyFilter === 'USD')
                        <td>{{ number_format($balance['usd_balance'], 2) }}</td>
                    @endif
                    @if ($currencyFilter === 'all' || $currencyFilter === 'CDF')
                        <td>{{ number_format($balance['cdf_balance'], 2) }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Rapport généré le {{ now()->format('d/m/Y H:i') }} - {{ config('app.name') }}
    </div>

</body>

</html>