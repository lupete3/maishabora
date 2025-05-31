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
                size: 80mm auto;
            }

            body {
                margin: 0;
                padding: 0;
                font-family: 'Courier New', monospace;
                font-size: 30px;
            }

            .no-print {
                display: none !important;
            }
        }

        body {
            width: 200mm;
            margin: 0 auto;
            font-family: 'Courier New', monospace;
            padding: 5px;
            line-height: 1.2;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
        }

        .footer {
            font-size: 30px;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <!-- En-tête -->
    <div class="center bold">{{ config('app.name') }}</div>
    <div class="center">N° ID : 666-666-666</div>
    <div class="center">Adresse : 123 Avenue Kasa-Vubu, Kinshasa</div>
    <div class="center">Téléphone : +243 999 999 999</div>
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
        <div>Solde après</div>
        <div>{{ number_format($transaction->balance_after, 2, ',', ' ') }} {{ $transaction->currency }}</div>
    </div>
    <div class="row">
        <div>Date</div>
        <div>{{ $transaction->created_at->format('d/m/Y H:i') }}</div>
    </div>
    <div class="row">
        <div>Réf.</div>
        <div>#{{ $transaction->id }}</div>
    </div>

    <div class="line"></div>
    <div class="center">Merci pour votre confiance</div>

    <!-- Pied de page -->
    <div class="footer">
        Ce reçu est valable comme preuve de transaction. Aucun remboursement ne sera effectué sans ce document.
    </div>
    <!-- Ajout du QR Code -->
    <div class="center" style="margin-top: 10px;">
        {!! $qrCodeDataUri !!}
    </div>

    <!-- Script d'impression -->
    <script>
        window.onload = () => {
            // Attendre que tout soit chargé
            setTimeout(() => {
                window.print();
            }, 200); // délai léger pour éviter les problèmes d'affichage
        };
    </script>

</body>
</html>
