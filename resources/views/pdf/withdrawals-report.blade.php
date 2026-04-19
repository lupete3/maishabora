<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport des Retraits</title>
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
            background-color: #dc3545;
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

        .amount-negative {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>

<body>

    @include('partials.pdf-header', ['reportTitle' => 'RAPPORT DES RETRAITS'])

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
        <p style="margin: 2px 0;"><strong>Nombre de transactions :</strong> {{ $withdrawals->count() }}</p>
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
            @forelse($withdrawals as $index => $withdrawal)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $withdrawal->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $withdrawal->user->code ?? '-' }}</td>
                    <td>{{ ($withdrawal->user->name ?? '') . ' ' . ($withdrawal->user->postnom ?? '') . ' ' . ($withdrawal->user->prenom ?? '') }}
                    </td>
                    <td>{{ ucfirst($withdrawal->type) }}</td>
                    <td class="amount-negative">-{{ number_format($withdrawal->amount, 2, ',', ' ') }}</td>
                    <td>{{ $withdrawal->currency }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center;">Aucun retrait trouvé</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="text-align: right; margin-top: 10px;">
        <h3 style="font-size: 12px;">TOTAL DES RETRAITS : {{ number_format($total, 2, ',', ' ') }}</h3>
    </div>

    <div class="footer">
        Rapport généré le {{ now()->format('d/m/Y H:i') }} - {{ $company->name ?? config('app.name') }}
    </div>
</body>

</html>
