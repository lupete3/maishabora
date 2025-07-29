<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reçu #{{ $transfer->id }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            margin: 0 auto;
            padding: 10px;
            max-width: 320px; /* Largeur typique pour reçu 80mm */
            line-height: 1.4;
        }

        .header,
        .footer {
            text-align: center;
            margin-bottom: 10px;
        }

        h2 {
            font-size: 16px;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th, td {
            padding: 4px 0;
            text-align: left;
        }

        .bold {
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }

        @media print {
            body {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <!-- En-tête -->
    <div class="header">
        <h2>{{ config('app.name') }}</h2>
        <p><strong>Reçu de Virement</strong></p>
        <p>Date : {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <hr class="line">

    <!-- Informations du transfert -->
    <table>
        <tr>
            <td class="bold">Type</td>
            <td>Virement Caisse Centrale</td>
        </tr>
        <tr>
            <td class="bold">Devise</td>
            <td>{{ $transfer->currency }}</td>
        </tr>
        <tr>
            <td class="bold">Montant</td>
            <td>{{ number_format($transfer->amount, 2) }} {{ $transfer->currency }}</td>
        </tr>
        <tr>
            <td class="bold">Agent</td>
            <td>{{ $agent->name }}</td>
        </tr>
        <tr>
            <td class="bold">Réf.</td>
            <td>#{{ $transfer->id }}</td>
        </tr>
        <tr>
            <td class="bold">Date</td>
            <td>{{ $transfer->created_at->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <hr class="line">

    <!-- Pied de page -->
    <div class="footer">
        <p>Fait par : {{ $agent->name.' '.$agent->postnom }}</p>
        <p class="center">Merci pour votre travail diligent !</p>
    </div>

</body>
</html>
