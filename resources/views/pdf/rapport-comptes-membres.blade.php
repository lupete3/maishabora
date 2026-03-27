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

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <h4 style="margin-bottom: 5px; border-bottom: 1px solid #ccc;">Comptes Courants</h4>
                <p style="margin: 2px 0;">Total USD : <strong>{{ number_format($globalCurrentUsd, 2) }} $</strong></p>
                <p style="margin: 2px 0;">Total CDF : <strong>{{ number_format($globalCurrentCdf, 2) }} FC</strong></p>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <h4 style="margin-bottom: 5px; border-bottom: 1px solid #ccc;">Comptes Épargnes</h4>
                <p style="margin: 2px 0;">Total USD : <strong>{{ number_format($globalSavingsUsd, 2) }} $</strong></p>
                <p style="margin: 2px 0;">Total CDF : <strong>{{ number_format($globalSavingsCdf, 2) }} FC</strong></p>
            </td>
        </tr>
    </table>
    <h4 style="margin: 0 0 10px 0;">Total Membres listés : {{ $balances->count() }}</h4>

    <table class="table" border="1" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th rowspan="2" style="width: 10%;">Code</th>
                <th rowspan="2" style="width: 30%;">Membre</th>
                <th colspan="2" style="width: 30%;">Solde USD</th>
                <th colspan="2" style="width: 30%;">Solde CDF</th>
            </tr>
            <tr>
                <th style="font-size: 7px;">Courant</th>
                <th style="font-size: 7px;">Épargne</th>
                <th style="font-size: 7px;">Courant</th>
                <th style="font-size: 7px;">Épargne</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($balances as $balance)
                <tr>
                    <td class="text-center">{{ $balance['member']->code }}</td>
                    <td>{{ $balance['member']->name . ' ' . $balance['member']->postnom . ' ' . $balance['member']->prenom }}
                    </td>

                    <td class="text-end">{{ number_format($balance['current_usd'], 2) }}</td>
                    <td class="text-end">{{ number_format($balance['savings_usd'], 2) }}</td>

                    <td class="text-end">{{ number_format($balance['current_cdf'], 2) }}</td>
                    <td class="text-end">{{ number_format($balance['savings_cdf'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Rapport généré le {{ now()->format('d/m/Y H:i') }} - {{ config('app.name') }}
    </div>

</body>

</html>