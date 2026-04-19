<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Rapport des Journaux</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 5px;
            color: #000;
        }

        .footer {
            text-align: center;
            margin-top: 20px
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table td,
        .table th {
            border: 1px solid #000;
            padding: 3px;
            font-size: 9px;
        }

        th {
            background-color: #f1c206;
        }

        .text-center {
            text-align: center;
        }

        .logo {
            width: 70px;
        }
    </style>
</head>

<body>

    @include('partials.pdf-header', ['reportTitle' => 'BALANCES COMPTABLES'])
    @if(isset($date_debut) && isset($date_fin) && $period_type !== 'tout')
        <p class="text-center" style="margin: 0; font-size: 11px;">
            Période : du {{ \Carbon\Carbon::parse($date_debut)->format('d/m/Y') }}
            au {{ \Carbon\Carbon::parse($date_fin)->format('d/m/Y') }}
        </p>
    @endif
    <p class="text-center"><strong>Devise :</strong> {{ $currency ?: 'Toutes' }}</p>

    <table class="table">
        <thead>
            <tr>
                <th>N° Compte</th>
                <th>Intitulé</th>
                <th>Total Débit</th>
                <th>Total Crédit</th>
                <th>Solde Débiteur</th>
                <th>Solde Créditeur</th>
            </tr>
        </thead>
        <tbody>
            @foreach($comptes as $compte)
                <tr>
                    <td>{{ $compte['code'] }}</td>
                    <td>{{ $compte['intitule'] }}</td>
                    <td>{{ number_format($compte['total_debit'], 2, ',', ' ') }}</td>
                    <td>{{ number_format($compte['total_credit'], 2, ',', ' ') }}</td>
                    <td>{{ $compte['solde_debiteur'] > 0 ? number_format($compte['solde_debiteur'], 2, ',', ' ') : '' }}
                    </td>
                    <td>{{ $compte['solde_crediteur'] > 0 ? number_format($compte['solde_crediteur'], 2, ',', ' ') : '' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #eee; font-weight: bold;">
                <td colspan="2" class="text-center">TOTAUX</td>
                <td>{{ number_format($totals['total_debit'], 2, ',', ' ') }}</td>
                <td>{{ number_format($totals['total_credit'], 2, ',', ' ') }}</td>
                <td>{{ number_format($totals['solde_debiteur'], 2, ',', ' ') }}</td>
                <td>{{ number_format($totals['solde_crediteur'], 2, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>


    <div class="footer">
        Rapport généré le {{ now()->format('d/m/Y H:i') }} par {{ Auth::user()->name }}
        {{ Auth::user()->postnom ?? '' }} - {{ $company->name ?? config('app.name') }}
    </div>

</body>

</html>
