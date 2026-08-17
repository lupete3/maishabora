<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Rapport Global des Crédits</title>

<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        margin: 5px;
        color: #000;
    }
    .header {
        text-align: center;
        margin-bottom: 30px;
    }
    .container {
        width: 100%;
    }
    .summary-table th, .summary-table td {
            text-align: center;
        }
    .info-row {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        padding: 15px;
        border-radius: 6px;
        background-color: #f9f9f9;
    }
    .info-row div {
        width: 48%;
    }
    .table {
        width: 100%; border-collapse: collapse; margin-top: 20px;
    }
    .table td, .table th {
        border: 1px solid #000; padding: 2px; font-size: 10px;
    }
    th {
        background-color: #f1c206;
    }
    .logo {
        width: 80px;
    }
    td:first-child, th:first-child {
        text-align: center;
    }
</style>

</head>

<body>

    @include('partials.pdf-header', ['reportTitle' => 'RAPPORT GLOBAL DES CRÉDITS'])

    <table class="table">
        <thead>
            <tr>
                <th>ID Crédit</th>
                <th>Code Membre</th>
                <th>Nom Membre</th>
                <th>Date Crédit</th>
                <th>Date Début</th>
                <th>Date Fin</th>
                <th>Montant Crédit</th>
                <th>Montant payé</th>
                <th>Interêt</th>
                <th>Pénalité</th>
                <th>Agent Crédit</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($credits as $credit)
                <tr>
                    <td>#{{ $credit->id }}</td>
                    <td>{{ $credit->user->code }}</td>
                    <td>{{ $credit->user->name.' '.$credit->user->postnom.' '.$credit->user->prenom ?? '' }}</td>
                    <td>{{ \Carbon\Carbon::parse($credit->created_at)->format('d/m/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($credit->start_date)->format('d/m/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($credit->due_date)->format('d/m/Y') }}</td>
                    <td>{{ number_format($credit->amount, 2) }} {{ $credit->currency }}</td>
                    <td>{{ number_format($credit->repayments->where('is_paid', true)->sum('paid_amount'), 2) }} {{ $credit->currency }}</td>
                    <td>{{ number_format(($credit->amount * $credit->interest_rate / 100), 2) }} {{ $credit->currency }}</td>
                    <td>{{ number_format($credit->repayments->sum('penalty'), 2) }} {{ $credit->currency }}</td>
                    <td>{{ $credit->agent ? $credit->agent->name . ' ' . $credit->agent->postnom : 'N/A' }}</td>
                    <td>{{ $credit->is_paid ? 'Remboursé' : 'En cours' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3 class="section-title">Récapitulatif</h3>
    <table class="summary-table table">
        <thead>
            <tr>
                <th>Devise</th>
                <th>Total Crédits</th>
                <th>Remboursés</th>
                <th>En cours</th>
                <th>Intérêt</th>
                <th>Pénalités</th>
            </tr>
        </thead>
        <tbody>
            @foreach(['USD', 'CDF'] as $curr)
                <tr>
                    <td>{{ $curr }}</td>
                    <td>{{ number_format($totals['totalByCurrency'][$curr] ?? 0, 2) }}</td>
                    <td>
                        {{ number_format($totals['totalPaidByCurrency'][$curr] ?? 0, 2) }}
                        <div class="small text-muted">
                            C: {{ number_format($totals['collectedPrincipalByCurrency'][$curr] ?? 0, 2) }} • I: {{ number_format($totals['interestByCurrency'][$curr] ?? 0, 2) }} • P: {{ number_format($totals['penaltyByCurrency'][$curr] ?? 0, 2) }}
                        </div>
                    </td>
                    <td>{{ number_format($totals['totalUnpaidByCurrency'][$curr] ?? 0, 2) }}</td>
                    <td>{{ number_format($totals['interestByCurrency'][$curr] ?? 0, 2) }}</td>
                    <td>{{ number_format($totals['penaltyByCurrency'][$curr] ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div style="text-align: center; margin-top: 30px; font-size: 10px; color: #888;">
        Document généré automatiquement par le système {{ $company->name ?? config('app.name') }} le {{ now()->format('d/m/Y H:i') }}
    </div>
</body>

</html>
