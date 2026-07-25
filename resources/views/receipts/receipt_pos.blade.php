<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reçu #{{ $transaction->id }}</title>
  <style>
    /* Style global et réinitialisation */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Courier New', Courier, monospace;
      font-size: 12px;
      line-height: 1.3;
      color: #000;
      background-color: #fff;
    }

    /* Le conteneur Ticket POS (80mm = ~280px / 58mm = ~200px) */
    .receipt {
      width: 280px; /* Adaptez à 200px si vous utilisez du papier 58mm */
      margin: 0 auto;
      padding: 10px 5px;
    }

    /* Configuration d'impression */
    @media print {
      @page {
        margin: 0;
        size: auto;
      }

      body {
        margin: 0;
        padding: 0;
      }

      .receipt {
        width: 100%;
        padding: 2mm;
      }
    }

    /* Alignements et typographie */
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .bold { font-weight: bold; }
    .uppercase { text-transform: uppercase; }

    /* Séparateurs pointillés */
    .line {
      border-top: 1px dashed #000;
      margin: 6px 0;
    }

    /* Lignes d'informations clés/valeurs */
    .row {
      display: flex;
      justify-content: space-between;
      margin: 3px 0;
      word-break: break-word;
    }

    .row div:first-child {
      padding-right: 5px;
    }

    /* En-tête / Logo */
    .logo {
      display: block;
      margin: 0 auto 5px auto;
      max-width: 60px;
      height: auto;
    }

    .company-title {
      font-size: 14px;
      font-weight: bold;
      text-transform: uppercase;
    }

    .company-info {
      font-size: 10px;
    }

    /* Titre Reçu & Montant */
    .receipt-title {
      font-size: 14px;
      font-weight: bold;
      margin-top: 3px;
    }

    .amount-box {
      font-size: 15px;
      font-weight: bold;
      margin: 5px 0;
    }

    /* Pied de page */
    .footer {
      font-size: 9px;
      text-align: center;
      margin-top: 8px;
    }

    .signature-space {
      margin-top: 15px;
      padding-top: 10px;
      font-size: 10px;
    }
  </style>
</head>

<body>

  <div class="receipt">
    <!-- Logo & En-tête -->
    @php
      $logoPath = public_path('assets/img/logo.jpg');
      $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
    @endphp

    @if($logoData)
      <div class="text-center">
        <img class="logo" src="data:image/png;base64,{{ $logoData }}" alt="logo" />
      </div>
    @endif

    <div class="text-center company-title">{{ $company?->name ?? config('app.name') }}</div>
    <div class="text-center company-info">
      N° ID : {{ $company?->rccm ?? env('APP_RCCM', '000-000-000') }}<br>
      Tél : {{ $company?->phone ?? env('APP_PHONE', '+243 000 000 000') }}<br>
      {{ $company?->address ?? env('APP_ADRESS', 'Adresse non définie') }}
    </div>

    <div class="line"></div>

    <!-- Titre et Date -->
    <div class="text-center receipt-title">REÇU DE TRANSACTION</div>
    <div class="text-center" style="font-size: 10px;">{{ now()->format('d/m/Y H:i') }}</div>

    <div class="line"></div>

    <!-- Infos Client -->
    <div class="row">
      <span>Client:</span>
      <span class="bold">{{ $member->name }} {{ $member->postnom }} {{ $member->prenom }}</span>
    </div>
    <div class="row">
      <span>Tél:</span>
      <span>{{ $member->telephone }}</span>
    </div>
    <div class="row">
      <span>Code Client:</span>
      <span>{{ $member->code }}</span>
    </div>

    <div class="line"></div>

    <!-- Détails de la transaction -->
    <div class="row">
      <span>Réf:</span>
      <span class="bold">#{{ $transaction->id }}</span>
    </div>
    <div class="row">
      <span>Type:</span>
      <span class="bold uppercase">{{ $transaction->type }}</span>
    </div>
    <div class="row">
      <span>Agent:</span>
      <span>{{ $agent->name }}</span>
    </div>
    <div class="row">
      <span>Date Tx:</span>
      <span>{{ $transaction->created_at->format('d/m/Y H:i') }}</span>
    </div>

    <div class="line"></div>

    <!-- Montant mis en valeur -->
    <div class="row amount-box">
      <span>MONTANT:</span>
      <span class="text-right">
        @if($transaction->type == 'retrait') - @endif
        {{ number_format($transaction->amount, 2, ',', ' ') }} {{ $transaction->currency }}
      </span>
    </div>

    <div class="line"></div>

    <!-- Pied de page -->
    <div class="text-center bold" style="margin-top: 5px;">Merci pour votre confiance !</div>

    <div class="footer">
      Ce reçu fait foi de preuve de transaction.<br>
      Conservez ce document pour toute réclamation.
    </div>

    <!-- Signature facultative -->
    <div class="row signature-space">
      <span>Signature client:</span>
      <span>__________</span>
    </div>
  </div>

  <!-- Impression automatique -->
  <script>
    window.onload = function () {
      window.print();
    };
  </script>

</body>

</html>
