<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Reçu Virement #{{ $transfer->id }}</title>

    <style>
        /* Format ticket thermique */
        @media print {
            @page {
                margin: 0;
                size: 58mm auto;
            }

            body {
                margin: 0;
                padding: 0;
                font-family: 'Courier New', monospace;
                font-size: 10px !important;
            }

            .no-print {
                display: none !important;
            }
        }

        body {
            width: 50mm;
            margin: 1px;
            padding: 1px;
            font-family: 'Courier New', monospace;
            font-size: 12px;      /* 👈 Taille globale */
            line-height: 1.4;   /* 👈 Très important pour ticket */
        }

        .img-center {
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 4px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
        }

        .footer {
            font-size: 10px;
            text-align: center;
            margin-top: 10px;
            margin-bottom: 30px;
        }
    </style>
</head>

<body>

    <!-- Logo -->


    <!-- En-tête -->
    <div class="center bold">{{ config('app.name') }}</div>
    <div class="center">N° ID : {{ env('APP_RCCM', '000-000-000') }}</div>
    <div class="center">Adresse : {{ env('APP_ADRESS', 'Adresse non définie') }}</div>
    <div class="center">Tél : {{ env('APP_PHONE', '+243 000 000 000') }}</div>

    <div class="line"></div>

    <!-- Titre -->
    <div class="center bold">REÇU DE VIREMENT</div>
    <div class="center">{{ now()->format('d/m/Y H:i') }}</div>

    <div class="line"></div>

    <!-- Détails du virement -->
    <div class="row">
        <div>Type</div>
        <div class="bold">Virement</div>
    </div>

    <div class="row">
        <div>Destinnation</div>
        <div>Caisse Centrale</div>
    </div>

    <div class="row">
        <div>Montant</div>
        <div class="bold">
            {{ number_format($transfer->amount, 2, ',', ' ') }}
            {{ $transfer->currency }}
        </div>
    </div>

    <div class="row">
        <div>Devise</div>
        <div>{{ $transfer->currency }}</div>
    </div>

    <div class="row">
        <div>Date</div>
        <div>{{ $transfer->created_at->format('d/m/Y H:i') }}</div>
    </div>

    <div class="row">
        <div>Réf.</div>
        <div>#{{ $transfer->id }}</div>
    </div>

    <div class="row">
        <div>Agent</div>
        <div>{{ $agent->name. ' '. $agent->postnom }}</div>
    </div>

    <div class="line"></div>

    <div class="center">Merci pour votre collaboration</div>

    <!-- Pied de page -->
    <div class="footer">
        Ce reçu fait foi de l’opération de virement effectuée.

        <div class="row" style="margin-top: 10px;">
            <div>Signature Agent</div>
            <div>Signature Admin</div>
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