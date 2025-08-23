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
                {{ $user->name ?? '' }} {{ $user->postnom ?? '' }} {{ $user->prenom ?? '' }}
            </td>
        </tr>
    </table>

    <hr style="margin: 10px 0; border-bottom: 2px solid #ed8d0f;">
    <h3 class="text-center" style="margin: 2px 0; text-decoration: underline;">RAPPORT DES JOURNAUX</h3>

    <p>Nombre total d’écritures : <strong>{{ $transactionCount }}</strong></p>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Référence</th>
                <th>Libellé</th>
                <th>Journal</th>
                <th>Compte</th>
                <th>Débit</th>
                <th>Crédit</th>
                <th>Devise</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($journals as $j)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($j->date_operation)->format('d/m/Y') }}</td>
                    <td>{{ $j->reference }}</td>
                    <td>{{ $j->libelle }}</td>
                    <td>{{ $j->journalType->libelle ?? '' }}</td>
                    <td>{{ $j->account->code ?? '' }} - {{ $j->account->intitule ?? '' }}</td>
                    <td class="text-end">{{ number_format($j->montant_debit, 2, ',', ' ') }}</td>
                    <td class="text-end">{{ number_format($j->montant_credit, 2, ',', ' ') }}</td>
                    <td>{{ $j->devise }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3 style="margin-top: 30px;">Récapitulatif des totaux par devise</h3>
    <table class="table" border="1" cellspacing="0" cellpadding="4">
        <thead>
            <tr>
                <th>Devise</th>
                <th>Total Débit</th>
                <th>Total Crédit</th>
                <th>Solde (Débit - Crédit)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($totalByCurrency as $currency => $tot)
                <tr>
                    <td>{{ $currency }}</td>
                    <td class="text-end">{{ number_format($tot['debit'], 2, ',', ' ') }}</td>
                    <td class="text-end">{{ number_format($tot['credit'], 2, ',', ' ') }}</td>
                    <td class="text-end">{{ number_format($tot['net'], 2, ',', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Aucune donnée</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Rapport généré le {{ now()->format('d/m/Y H:i') }} par {{ Auth::user()->name }}
        {{ Auth::user()->postnom ?? '' }} - {{ config('app.name') }}
    </div>

</body>

</html>
