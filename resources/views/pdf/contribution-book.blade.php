<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Récapitulatif Carnet - {{ $book->code }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 1px;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        h2 {
            font-size: 14px;
            margin-bottom: 5px;
        }

        h4 {
            margin-top: 10px;
            font-size: 14px;
            border-bottom: 1px solid #999;
            padding-bottom: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        th, td {
            border: 0.5px solid #444;
            padding: 5px;
            text-align: center;
        }

        th {
            background-color: #f0f0f0;
        }

        .total {
            font-weight: bold;
            margin-top: 15px;
            font-size: 12px;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>{{ __('Récapitulatif du carnet') }} : {{ $book->code }}</h2>
    <p><strong>{{ __('Membre') }} :</strong> {{ $user->name }} {{ $user->postnom }}</p>
</div>

<h4>{{ __('Détails des dépôts') }} | <strong>{{ __('Date de création') }} :</strong> {{ $book->subscription->cree_a }}</h4>

<table>
    <thead>
        <tr>
            <th>{{ __('Jour') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Montant') }} (FC)</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lines as $line)
            @if ($line->montant > 0)
                <tr>
                    <td>{{ $line->numero_ligne }}</td>
                    <td>{{ date('d-m-Y', strtotime($line->date_contribution) ?? '-') }}</td>
                    <td>{{ number_format($line->montant, 0, ',', '.') ?? '-' }}</td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>

<p class="total">{{ __('Total déposé') }} : {{ number_format($totalDeposited, 0, ',', '.') }} FC</p>
<p class="total">{{ __('Total rétiré') }} : {{ number_format($totalDeposited - $line->montant, 0, ',', '.') }} FC</p>

</body>
</html>
