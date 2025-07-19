<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport Caisse Centrale</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 10px;
            color: #222;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
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
            margin-top: 12px;
        }

        body { font-family: sans-serif; font-size: 14px; }
        th, td { border: 1px solid #000; padding: 2px; font-size: 8px;}
        th { background-color: #f1c206; }

        .footer {
            position: fixed;
            bottom: 10px;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #888;
        }

        .balances {
            width: 50%;
            margin: 10px auto;
        }

        .balances td {
            padding: 6px 10px;
        }

        .title-section {
            margin-top: 8px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>RAPPORT DE LA CAISSE CENTRALE</h2>
        <p><strong>{{ config('app.name') }}</strong></p>
        <p><strong>Date d'impression :</strong> {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <!-- Soldes actuels -->
    <div class="title-section">
        <h4 style="text-align:center;">Soldes Actuels</h4>
        <table class="balances">
            <thead>
                <tr>
                    <th>Devise</th>
                    <th>Solde actuel</th>
                </tr>
            </thead>
            <tbody>
                @foreach(['USD', 'CDF'] as $curr)
                    <tr>
                        <td>{{ $curr }}</td>
                        <td>{{ number_format($balances[$curr] ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="title-section" style="margin-top: 30px;">
        <h4 style="text-align:center;">Totaux des mouvements par devise</h4>
        <table>
            <thead>
                <tr>
                    <th>Devise</th>
                    <th>Total Entrées</th>
                    <th>Total Sorties</th>
                </tr>
            </thead>
            <tbody>
                @foreach(['USD', 'CDF'] as $curr)
                    <tr>
                        <td>{{ $curr }}</td>
                        <td>{{ number_format($totaux['entrées'][$curr] ?? 0, 2) }}</td>
                        <td>{{ number_format($totaux['sorties'][$curr] ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Transactions -->
    <div class="title-section">
        <h4 style="text-align:center;">Dernières Transactions</h4>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Devise</th>
                    <th>Montant</th>
                    <th>Solde après</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transactions as $t)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($t->created_at)->format('d/m/Y H:i') }}</td>
                        <td>{{ ucfirst($t->type) }}</td>
                        <td>{{ $t->currency }}</td>
                        <td>{{ number_format($t->amount, 2) }} {{ $t->currency }}</td>
                        <td>{{ number_format($t->balance_after, 2) }} {{ $t->currency }}</td>
                        <td>{{ $t->description ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;">Aucune transaction trouvée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        Généré par {{ auth()->user()->name. ' '.auth()->user()->postnom }} – {{ config('app.name') }}
    </div>

</body>
</html>
