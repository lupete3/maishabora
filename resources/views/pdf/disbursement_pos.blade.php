<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 10px;
            margin: 0;
            padding: 5px;
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 5px;
        }

        .logo {
            width: 40px;
        }

        .text-center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .divider {
            border-top: 1px dashed black;
            margin: 5px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 8px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="bold">{{ strtoupper(config('app.name')) }}</div>
        <div>{{ env('APP_ADRESS') }}</div>
        <div>Tel: {{ env('APP_PHONE') }}</div>
    </div>

    <div class="divider"></div>
    <div class="text-center bold">RECU DE DECAISSEMENT</div>
    <div class="divider"></div>

    <div class="row">
        <span>Date:</span>
        <span>{{ $transaction->created_at->format('d/m/Y H:i') }}</span>
    </div>
    <div class="row">
        <span>Ref:</span>
        <span>#{{ $transaction->id }}</span>
    </div>
    <div class="row">
        <span>Agent:</span>
        <span>{{ $transaction->user->name }}</span>
    </div>

    <div class="divider"></div>

    <div style="margin-bottom: 5px;">
        <span class="bold">Motif:</span><br>
        {{ $transaction->disbursementType->name ?? 'Autre' }}
    </div>

    <div style="margin-bottom: 5px;">
        <span class="bold">Description:</span><br>
        {{ $transaction->description }}
    </div>

    <div class="divider"></div>

    <div class="row bold" style="font-size: 12px;">
        <span>MONTANT:</span>
        <span>{{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}</span>
    </div>

    <div class="divider"></div>
    <div class="footer">
        Merci de votre confiance.<br>
        Signature Agent
        <br><br><br>
        .....................
    </div>
</body>

</html>