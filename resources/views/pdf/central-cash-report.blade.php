<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport Caisse Centrale</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 5px;
            color: #000;
        }
        .footer { text-align: center; margin-top: 50px }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
        }
        .table td, .table th {
            border: 1px solid #000; padding: 2px; font-size: 8px;
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
        .footer {
            position: fixed;
            bottom: 10px;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #888;
        }
    </style>
</head>
<body>

    @include('partials.pdf-header', ['reportTitle' => 'RAPPORT DE LA CAISSE CENTRALE'])

    <!-- Soldes actuels -->
    <div class="title-section">
        <h4 style="text-align:center;">Soldes Actuels</h4>
        <table class="table">
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
        <table class="table">
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
        <table class="table">
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
        Généré par {{ auth()->user()->name. ' '.auth()->user()->postnom }} – {{ $company->name ?? config('app.name') }}
    </div>

</body>
</html>
