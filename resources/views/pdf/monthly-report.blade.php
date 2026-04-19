<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport Mensuel des Contributions - {{ $member->name }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #f1c206;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #1a5276;
        }
        .info-section {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #f1c206;
            color: #000;
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
        td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
            font-size: 12px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    @include('partials.pdf-header', ['reportTitle' => 'RAPPORT MENSUEL DES CONTRIBUTIONS'])

<div class="info-section">
    <p><strong>Membre :</strong> {{ $member->name }} {{ $member->postnom }} {{ $member->prenom }}</p>
    <p><strong>Code Membre :</strong> {{ $member->code }}</p>
    <p><strong>Date d'émission :</strong> {{ now()->format('d/m/Y H:i') }}</p>
</div>

<table>
    <thead>
        <tr>
            <th>Date de Contribution</th>
            <th>Numéro de Carnet</th>
            <th>Type</th>
            <th style="text-align: right;">Montant</th>
        </tr>
    </thead>
    <tbody>
        @foreach($contributions as $contribution)
            <tr>
                <td>{{ \Carbon\Carbon::parse($contribution->contribution_date)->format('d/m/Y') }}</td>
                <td>{{ $contribution->card->code ?? 'N/A' }}</td>
                <td>{{ ucfirst($contribution->card->card_type ?? 'Cotisation') }}</td>
                <td style="text-align: right;">{{ number_format($contribution->amount, 0, ',', '.') }} {{ $contribution->card->currency ?? 'FC' }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="3" style="text-align: right;">TOTAL DÉPOSÉ</td>
            <td style="text-align: right;">{{ number_format($totalDeposited, 0, ',', '.') }} {{ $contributions->first()->card->currency ?? 'FC' }}</td>
        </tr>
    </tfoot>
</table>

<div class="footer">
    Ce document est un récapitulatif automatique de vos versements pour le mois de {{ $month }}.<br>
    Merci pour votre contribution au développement de {{ $company->name ?? config('app.name') }}.
</div>

</body>
</html>
