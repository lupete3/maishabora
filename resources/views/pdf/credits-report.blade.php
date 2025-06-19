<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Rapport Global des Crédits</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            margin: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 80px;
            height: auto;
            margin-bottom: 10px;
        }

        h2 {
            margin: 0;
        }

        p {
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #555;
            padding: 6px;
            text-align: left;
            font-size: 12px;
        }

        th {
            background-color: #f0f0f0;
        }

        .summary-table th, .summary-table td {
            text-align: center;
        }

        .section-title {
            margin-top: 40px;
            font-size: 16px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>{{ config('app.name') }}</h2>
        <p><strong>Rapport Global des Crédits</strong></p>
        <p>Date d'impression : {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID Crédit</th>
                <th>Code Membre</th>
                <th>Nom Membre</th>
                <th>Date Crédit</th>
                <th>Montant</th>
                <th>Solde restant</th>
                <th>Pénalité</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($credits as $credit)
                <tr>
                    <td>{{ $credit->id }}</td>
                    <td>{{ $credit->user->code }}</td>
                    <td>{{ $credit->user->name.' '.$credit->user->postnom.' '.$credit->user->prenom ?? '' }}</td>
                    <td>{{ \Carbon\Carbon::parse($credit->start_date)->format('d/m/Y') }}</td>
                    <td>{{ number_format($credit->amount, 2) }} {{ $credit->currency }}</td>
                    <td>{{ number_format($credit->amount - $credit->repayments->where('is_paid', true)->sum('paid_amount'), 2) }} {{ $credit->currency }}</td>
                    <td>{{ number_format($credit->repayments->sum('penalty'), 2) }} {{ $credit->currency }}</td>
                    <td>{{ $credit->is_paid ? 'Remboursé' : 'En cours' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3 class="section-title">Récapitulatif</h3>
    <table class="summary-table">
        <thead>
            <tr>
                <th>Devise</th>
                <th>Total Crédits</th>
                <th>Remboursés</th>
                <th>En cours</th>
                <th>Pénalités</th>
            </tr>
        </thead>
        <tbody>
            @foreach(['USD', 'CDF'] as $curr)
                <tr>
                    <td>{{ $curr }}</td>
                    <td>{{ number_format($totals['totalByCurrency'][$curr] ?? 0, 2) }}</td>
                    <td>{{ number_format($totals['totalPaidByCurrency'][$curr] ?? 0, 2) }}</td>
                    <td>{{ number_format($totals['totalUnpaidByCurrency'][$curr] ?? 0, 2) }}</td>
                    <td>{{ number_format($totals['penaltyByCurrency'][$curr] ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
