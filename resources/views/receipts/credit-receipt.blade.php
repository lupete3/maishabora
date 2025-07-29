<!-- resources/views/receipts/credit-receipt.blade.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu #{{ $credit->id }}</title>
    <style>
        @media print {
            @page { margin:0; size:58mm auto; }
            body { margin:0; padding:0; font-family:'Courier New', monospace; font-size:12px; }
            .no-print { display:none !important; }
        }
        body {
            width:50mm; margin:-1px; padding:1px;
            font-family:monospace,'Courier New'; line-height:1;
        }
        .img-center { display:block; margin:0 auto; }
        .center { text-align:center; }
        .bold { font-weight:bold; }
        .line { border-top:1px dashed #000; margin:4px 0; }
        .row { display:flex; justify-content:space-between; }
        .footer { font-size:10px; text-align:center; margin:10px 0; }
    </style>
</head>
<body>
    <img src="{{ asset('assets/img/logo.jpg') }}" width="80px" alt="logo" class="img-center">
    <div class="center bold">{{ config('app.name') }}</div>
    <div class="center">N° ID : {{ env('APP_RCCM', '000-000-000') }}</div>
    <div class="center">Adresse : {{ env('APP_ADRESS', 'Adresse non définie') }}</div>
    <div class="center">Tél : {{ env('APP_PHONE', '+243 000 000 000') }}</div>
    <div class="line"></div>

    <div class="center bold">REÇU D'OCTROI DE CRÉDIT</div>
    <div class="center">{{ now()->format('d/m/Y H:i') }}</div>
    <div class="line"></div>

    <div><strong>Client:</strong> {{ $member->name }} {{ $member->postnom }} {{ $member->prenom }}</div>
    <div><strong>Tél:</strong> {{ $member->telephone }}</div>
    <div><strong>Code Client:</strong> {{ $member->code }}</div>
    <div class="line"></div>

    <div class="row"><div>ID Crédit</div><div class="bold">#{{ $credit->id }}</div></div>
    <div class="row"><div>Montant</div><div class="bold">{{ number_format($credit->amount,2,',',' ') }} {{ $credit->currency }}</div></div>
    <div class="row"><div>Intérêt</div><div>{{ $credit->interest_rate }}%</div></div>
    <div class="row"><div>Échéances</div><div>{{ $credit->installments }}</div></div>
    <div class="row"><div>Début</div><div>{{ \Carbon\Carbon::parse($credit->start_date)->format('d/m/Y') }}</div></div>
    <div class="row"><div>Proch. Échéance</div><div>{{ \Carbon\Carbon::parse($firstRepayment->due_date)->format('d/m/Y') }}</div></div>
    <div class="line"></div>

    <div class="row"><div>Agent</div><div>{{ $agent->name }}</div></div>
    <div class="line"></div>

    <div class="center">Merci pour votre confiance</div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
