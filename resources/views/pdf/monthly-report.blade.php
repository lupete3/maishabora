<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport Mensuel</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: center; }
        .header { text-align: center; margin-bottom: 30px; }
        .total { font-weight: bold; }
    </style>
</head>
<body>

<div class="header">
    <h2>Rapport Mensuel des Contributions</h2>
    <p>Membre : {{ $member->name }} {{ $member->postnom }}</p>
    <p>Période : {{ $month }}</p>
</div>

<h4>Détails des dépôts</h4>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Référence</th>
            <th>Montant (FC)</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($contributions as $op)
            <tr>
                <td>{{ $op->created_at->format('d/m/Y') }}</td>
                <td>{{ $op->type_operation }}</td>
                <td>
                    @if ($op->reference_type === 'App\Models\ContributionLine')
                        {{ optional(optional($op->reference)->book)->code ?? '-' }}
                    @else
                        {{ optional($op->reference)->code ?? '-' }}
                    @endif
                </td>
                <td>{{ number_format($op->montant, 0, ',', '.') }} FC</td>
            </tr>
        @endforeach
    </tbody>
</table>

<p class="total">Total déposé : {{ number_format($totalDeposited, 0, ',', '.') }} FC</p>

</body>
</html>
