<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche de Clôture - {{ $cloture->user->name }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            margin: 10px;
            color: #222;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .header h2 {
            margin: 0;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th, td {
            border: 1px solid #aaa;
            padding: 4px;
            font-size: 8px;
            text-align: left;
        }

        th {
            background-color: #f1c206;
        }

        .section-title {
            margin-top: 20px;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }

        .balances, .billetage {
            width: 48%;
            display: inline-block;
            vertical-align: top;
        }

        .footer {
            position: fixed;
            bottom: 5px;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #888;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>FICHE DE CLÔTURE DE CAISSE</h2>
        <h3>{{ config('app.name') }}</h3>
        <p><strong>Utilisateur :</strong> {{ $cloture->user->name. ' '.$cloture->user->postnom }}</p>
        <p><strong>Date de clôture :</strong> {{ \Carbon\Carbon::parse($cloture->closing_date)->format('d/m/Y') }}</p>
        <p><strong>Statut :</strong> {{ strtoupper($cloture->status) }}</p>
        @if($cloture->validated_at)
            <p><strong>Validée le :</strong> {{ $cloture->validated_at->format('d/m/Y H:i') }} par {{ $cloture->validatedBy?->name. ' '.$cloture->validatedBy?->postnom ?? '-' }}</p>
        @endif
    </div>

    <div class="section-title">SOLDES</div>
    <table>
        <thead>
            <tr>
                <th>Devise</th>
                <th>Logique</th>
                <th>Physique</th>
                <th>Écart</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>USD</td>
                <td>{{ number_format($cloture->logical_usd, 2) }}</td>
                <td>{{ number_format($cloture->physical_usd, 2) }}</td>
                <td>{{ number_format($cloture->gap_usd, 2) }}</td>
            </tr>
            <tr>
                <td>CDF</td>
                <td>{{ number_format($cloture->logical_cdf, 2) }}</td>
                <td>{{ number_format($cloture->physical_cdf, 2) }}</td>
                <td>{{ number_format($cloture->gap_cdf, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">BILLETAGE</div>
    <div class="billetage">
        <h4 style="text-align:center;">USD</h4>
        <table>
            <thead>
                <tr>
                    <th>Valeur</th>
                    <th>Quantité</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cloture->billetages->where('currency', 'USD') as $billet)
                    <tr>
                        <td>${{ number_format($billet->denomination, 0) }}</td>
                        <td>{{ $billet->quantity }}</td>
                        <td>${{ number_format($billet->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="billetage">
        <h4 style="text-align:center;">CDF</h4>
        <table>
            <thead>
                <tr>
                    <th>Valeur</th>
                    <th>Quantité</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cloture->billetages->where('currency', 'CDF') as $billet)
                    <tr>
                        <td>{{ number_format($billet->denomination, 0) }} CDF</td>
                        <td>{{ $billet->quantity }}</td>
                        <td>{{ number_format($billet->total, 2) }} CDF</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($cloture->note || $cloture->rejection_reason)
        <div class="section-title">NOTE / MOTIF</div>
        <table>
            <tr>
                <th>Note de clôture</th>
                <td>{{ $cloture->note ?? '-' }}</td>
            </tr>
            @if($cloture->status === 'rejected')
                <tr>
                    <th>Motif du rejet</th>
                    <td>{{ $cloture->rejection_reason ?? '-' }}</td>
                </tr>
            @endif
        </table>
    @endif

    <div class="footer">
        Fiche générée par {{ auth()->user()->name. ' '. auth()->user()->postnom }} le {{ now()->format('d/m/Y H:i') }} - {{ config('app.name') }}
    </div>

</body>
</html>
