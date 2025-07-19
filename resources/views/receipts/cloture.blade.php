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
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-success { background: #28a745; color: #fff; }
        .badge-danger { background: #dc3545; color: #fff; }
        .logo { width: 80px; }
    </style>
</head>
<body>

    <div class="header" style="border-bottom: 1px solid #000; padding-bottom: 10px;">
        <table style="width:100%;">
            <tr>
                <td style="width: 20%;">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.jpg'))) }}" class="logo" alt="Logo">
                </td>
                <td style="width: 60%; text-align:center;">
                    <h2 style="margin: 0; font-size: 14px;">{{ strtoupper(config('app.name')) }}</h2>
                    <p style="margin: 0;">Structure de Microfinance</p>
                    <p style="margin: 0;">Adresse : Bukavu, RDC – Tel : +243 999 999 999 – Email : contact@maisha.cd</p>
                </td>
                <td style="width: 20%; text-align:right; font-size: 9px;">
                    <strong>Date :</strong> {{ \Carbon\Carbon::parse($cloture->closing_date)->format('d/m/Y') }}<br>
                    <strong>Heure :</strong> {{ now()->format('H:i') }}<br>
                    <strong>Agent :</strong><br>
                    {{ $cloture->user->name }} {{ $cloture->user->postnom }}
                </td>
            </tr>
        </table>
        <hr style="margin: 10px 0;">
        <h3 class="text-center" style="text-decoration: underline; margin-bottom: 2px;">FICHE DE CLÔTURE DE CAISSE</h3>
        <p class="text-center">Journée du {{ \Carbon\Carbon::parse($cloture->closing_date)->translatedFormat('d F Y') }}</p>
        <p class="text-center">
            Statut :
            @if($cloture->status == 'valide')
                <span class="badge badge-success">VALIDÉE</span>
            @elseif($cloture->status == 'rejete')
                <span class="badge badge-danger">REJETÉE</span>
            @else
                EN ATTENTE
            @endif
        </p>
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
