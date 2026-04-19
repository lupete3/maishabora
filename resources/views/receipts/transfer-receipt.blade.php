<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Reçu Virement #{{ $transfer->id }}</title>

    <style>
    /* Format ticket thermique */
    @page {
    margin: 0;
    size: 58mm auto;
    }

    body {
    margin: 0;
    padding: 5px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    line-height: 1.4;
    width: 100%;
    background-color: #fff;
    }

    .container {
    width: 100%;
    max-width: 54mm;
    margin: 0 auto;
    }

    .img-center {
    display: block;
    margin-left: auto;
    margin-right: auto;
    max-width: 100%;
    }

    .center {
    text-align: center;
    }

    .bold {
    font-weight: bold;
    }

    .line {
    border-top: 1px dashed #000;
    margin: 5px 0;
    width: 100%;
    }

    /* Utilisation de table pour une meilleure compatibilité PDF pour le "flex" */
    .row-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 2px;
    }

    .row-table td {
    vertical-align: top;
    }

    .text-right {
    text-align: right;
    }

    .footer {
    font-size: 10px;
    text-align: center;
    margin-top: 10px;
    margin-bottom: 20px;
    }
    </style>
</head>

<body>
    <div class="container">
        <!-- En-tête -->
        @php
            $logoPath = public_path('assets/img/logo.jpg');
            $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
        @endphp
        @if($logoData)
            <div class="center">
                <img src="data:image/png;base64,{{ $logoData }}" width="50px" alt="logo" class="img-center"
                    style="margin-bottom: 5px;">
            </div>
        @endif
        <div class="center bold">{{ strtoupper($company?->name ?? config('app.name')) }}</div>
        <div class="center" style="font-size: 10px;">
            N° ID : {{ $company?->rccm ?? env('APP_RCCM', '000-000-000') }}<br>
            Adresse : {{ $company?->address ?? env('APP_ADRESS', 'Adresse non définie') }}<br>
            Tél : {{ $company?->phone ?? env('APP_PHONE', '+243 000 000 000') }}
        </div>

        <div class="line"></div>

        <!-- Titre -->
        <div class="center bold">REÇU DE VIREMENT</div>
        <div class="center small">{{ now()->format('d/m/Y H:i') }}</div>

        <div class="line"></div>

        <!-- Détails du virement -->
        <table class="row-table">
            <tr>
                <td>Type</td>
                <td class="text-right bold">Virement</td>
            </tr>
            <tr>
                <td>Destination</td>
                <td class="text-right">Caisse Centrale</td>
            </tr>
            <tr>
                <td>Montant</td>
                <td class="text-right bold">
                    {{ number_format($transfer->amount, 2, ',', ' ') }}
                    {{ $transfer->currency }}
                </td>
            </tr>
            <tr>
                <td>Date</td>
                <td class="text-right">{{ $transfer->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td>Réf.</td>
                <td class="text-right">#{{ $transfer->id }}</td>
            </tr>
            <tr>
                <td>Agent</td>
                <td class="text-right">{{ $agent->name . ' ' . $agent->postnom }}</td>
            </tr>
        </table>

        <div class="line"></div>

        <div class="center bold" style="margin-top: 5px;">Merci pour votre collaboration</div>

        <!-- Pied de page -->
        <div class="footer">

            <table class="row-table" style="margin-top: 15px;">
                <tr>
                    <td class="center" style="font-size: 9px;">Signature Agent<br><br>..........</td>
                    <td class="center" style="font-size: 9px;">Signature Admin<br><br>..........</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Impression automatique -->
    <script>
        window.onload = function () {
            setTimeout(function () {
                window.print();
            }, 500);
        }
    </script>

</body>

</html>
