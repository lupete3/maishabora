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
        @php
            $logoPath = public_path('assets/img/logo.jpg');
            $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
        @endphp
        @if($logoData)
            <img src="data:image/png;base64,{{ $logoData }}" style="width: 50px; margin-bottom: 5px;">
        @endif
        <div class="bold">{{ strtoupper($company?->name ?? config('app.name')) }}</div>
        <div style="font-size: 10px;">
            {{ $company?->address ?? env('APP_ADRESS') }}<br>
            Tél : {{ $company?->phone ?? env('APP_PHONE') }}
        </div>
        <p class="bold" style="margin-top: 5px;">Reçu de Virement</p>
        <p style="font-size: 10px;">Date : {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <hr class="line">

    <!-- Informations du transfert -->
    <table>
        <tr>
            <td class="bold">Type </td>
            <td>{{ $transfer->type }}</td>
        </tr>
        <tr>
            <td class="bold">Devise</td>
            <td>{{ $transfer->currency }}</td>
        </tr>
        <tr>
            <td class="bold">Montant </td>
            <td>{{ number_format($transfer->amount, 2) }} {{ $transfer->currency }}</td>
        </tr>
        <tr>
            <td class="bold">Agent</td>
            <td>{{ $agent->name.' '.$agent->postnom }}</td>
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
        <p>Fait par : {{ Auth::user()->name.' '.Auth::user()->postnom }}</p>
    </div>

</body>
</html>
