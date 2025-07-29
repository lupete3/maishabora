<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu #{{ $transaction->id }}</title>
    <style>
        /* Format du ticket */
        @media print {
            @page {
                margin: 0;
                size: 58mm auto;
            }

            body {
                margin: 0;
                padding: 0;
                font-family: 'Courier New', monospace;
                font-size: 12px;
            }

            .no-print {
                display: none !important;
            }
        }

        body {
            width: 50mm;
            margin: -1px;
            font-family: monospace, 'Courier New';
            padding: 1px;
            line-height: 1;
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

    <img src="{{ asset('assets/img/logo.jpg') }}" width="100px" alt="" class="img-center" srcset="">
    <!-- En-tête -->
    <div class="center bold">{{ config('app.name') }}</div>
    <div class="center">N° ID : {{ env('APP_RCCM', '000-000-000') }}</div>
    <div class="center">Adresse : {{ env('APP_ADRESS', 'Adresse non définie') }}</div>
    <div class="center">Tél : {{ env('APP_PHONE', '+243 000 000 000') }}</div>
    <div class="line"></div>

    <!-- Titre -->
    <div class="center bold">REÇU DE TRANSACTION</div>
    <div class="center">{{ now()->format('d/m/Y H:i') }}</div>
    <div class="line"></div>

    <!-- Informations client -->
    <div><strong>Client:</strong> {{ $member->name }} {{ $member->postnom }} {{ $member->prenom }}</div>
    <div><strong>Tél:</strong> {{ $member->telephone }}</div>
    <div><strong>Code Client:</strong> {{ $member->code }}</div>
    <div class="line"></div>

    <!-- Détails transaction -->
    <div class="row">
        <div>Type</div>
        <div class="bold">{{ ucfirst($transaction->type) }}</div>
    </div>
    <div class="row">
        <div>Montant</div>
        <div class="bold">
            @if($transaction->type == 'retrait') - @endif
            {{ number_format($transaction->amount, 2, ',', ' ') }} {{ $transaction->currency }}
        </div>
    </div>
    <div class="row">
        <div>Date</div>
        <div>{{ $transaction->created_at->format('d/m/Y H:i') }}</div>
    </div>
    <div class="row">
        <div>Réf.</div>
        <div>#{{ $transaction->id }}</div>
    </div>
    <div class="row">
        <div>Agent</div>
        <div>{{ $agent->name }}</div>
    </div>

    <div class="line"></div>
    <div class="center">Merci pour votre confiance</div>

    <!-- Pied de page -->
    <div class="footer">
        Ce reçu est valable comme preuve de transaction. Aucun remboursement ne sera effectué sans ce document.

    <!-- Ajout du QR Code -->
    <div class="center" style="margin-top: 10px;">
        {!! $qrCodeDataUri !!}
    </div>

    <div class="row">
        <div>Signature Client.</div>
        <div>Signature Agent</div>
    </div>

    </div>

    <!-- Script d'impression -->

    <script>
        window.onload = function() {
            window.print();
        };
    </script>

</body>
</html>
