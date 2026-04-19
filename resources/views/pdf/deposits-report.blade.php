<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport des Dépôts</title>
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

        th {
            background-color: #28a745;
            color: white;
        }

        .section-title {
            margin-top: 10px;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }

        .logo {
            width: 80px;
        }

        .amount-positive {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>

<body>

    @include('partials.pdf-header', ['reportTitle' => 'RAPPORT DES DÉPÔTS'])

    <div style="margin-bottom: 10px;">
        <p style="margin: 2px 0;"><strong>Période :</strong>
            @if ($filterType === 'today')
                Aujourd'hui
            @elseif($filterType === 'week')
                Cette semaine
            @elseif($filterType === 'month')
                Ce mois
            @elseif($filterType === 'year')
                Cette année
            @elseif($filterType === 'custom' && $startDate && $endDate)
                Du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} au
                {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
            @endif
        </p>
        <p style="margin: 2px 0;"><strong>Devise :</strong> {{ $currency ?: 'Toutes' }}</p>
        <p style="margin: 2px 0;"><strong>Nombre de transactions :</strong> {{ $deposits->count() }}</p>
    </div>

    <table class="table" border="1" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Code</th>
                <th>Membre</th>
                <th>Type</th>
                <th>Montant</th>
                <th>Devise</th>
            </tr>
        </thead>
        <tbody>
            @forelse($deposits as $index => $deposit)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $deposit->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $deposit->user->code ?? '-' }}</td>
                    <td>{{ ($deposit->user->name ?? '') . ' ' . ($deposit->user->postnom ?? '') . ' ' . ($deposit->user->prenom ?? '') }}
                    </td>
                    <td>{{ ucfirst($deposit->type) }}</td>
                    <td class="amount-positive">+{{ number_format($deposit->amount, 2, ',', ' ') }}</td>
                    <td>{{ $deposit->currency }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center;">Aucun dépôt trouvé</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="text-align: right; margin-top: 10px;">
        <h3 style="font-size: 12px;">TOTAL DES DÉPÔTS : {{ number_format($total, 2, ',', ' ') }}</h3>
    </div>

    <div class="footer">
        Rapport généré le {{ now()->format('d/m/Y H:i') }} - {{ $company->name ?? config('app.name') }}
    </div>
</body>

</html>
