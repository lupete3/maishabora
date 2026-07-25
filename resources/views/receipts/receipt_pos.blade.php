<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reçu #{{ $transaction->id }}</title>
  <style>
    /* Configuration d'impression sans marges pour occuper tout le papier */
    @page {
      margin: 0;
      size: auto;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html, body {
      width: 100%;
      background-color: #fff;
      color: #000;
      font-family: 'Courier New', monospace;
      /* Police agrandie et lisible pour impression thermique */
      font-size: 18px;
      font-weight: 600;
      line-height: 1.3;
    }

    /* Le conteneur occupe toute la largeur de la tête d'impression */
    .receipt {
      width: 100%;
      padding: 5px 8px;
    }

    /* Alignements et utilitaires */
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .bold { font-weight: bold; }
    .uppercase { text-transform: uppercase; }

    /* Lignes séparatrices bien visibles */
    .line {
      border-top: 3px dashed #000;
      margin: 10px 0;
      width: 100%;
    }

    /* Dispositions en lignes (clé / valeur) */
    .row {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      margin: 5px 0;
      word-break: break-word;
    }

    /* En-tête */
    .company-name {
      font-size: 24px;
      font-weight: 900;
      text-transform: uppercase;
      margin-bottom: 4px;
    }

    .company-info {
      font-size: 16px;
      font-weight: normal;
    }

    .receipt-title {
      font-size: 22px;
      font-weight: 900;
      margin: 6px 0 2px 0;
    }

    /* Bloc Montant très lisible */
    .amount-box {
      font-size: 24px;
      font-weight: 900;
      margin: 12px 0;
      padding: 4px 0;
    }

    .logo {
      display: block;
      margin: 0 auto 8px auto;
      max-width: 90px;
      height: auto;
    }

    .footer {
      font-size: 14px;
      text-align: center;
      margin-top: 10px;
      font-weight: normal;
    }

    .signature-area {
      margin-top: 20px;
      font-size: 16px;
    }
  </style>
</head>

<body>

  <div class="receipt">

    <!-- Logo & En-tête entreprise -->
    @php
      $logoPath = public_path('assets/img/logo.jpg');
      $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
    @endphp

    @if($logoData)
      <div class="text-center">
        <img class="logo" src="data:image/png;base64,{{ $logoData }}" alt="logo" />
      </div>
    @endif

    <div class="text-center company-name">{{ $company?->name ?? config('app.name') }}</div>
    <div class="text-center company-info">
      ID: {{ $company?->rccm ?? env('APP_RCCM', '000-000-000') }}<br>
      Tél: {{ $company?->phone ?? env('APP_PHONE', '+243 000 000 000') }}<br>
      {{ $company?->address ?? env('APP_ADRESS', 'Adresse non définie') }}
    </div>

    <div class="line"></div>

    <!-- Titre du reçu -->
    <div class="text-center receipt-title">REÇU DE TRANSACTION</div>
    <div class="text-center" style="font-size: 16px;">{{ now()->format('d/m/Y H:i') }}</div>

    <div class="line"></div>

    <!-- Informations Client -->
    <div class="row">
      <span>Client:</span>
      <span class="bold">{{ $member->name }} {{ $member->postnom }} {{ $member->prenom }}</span>
    </div>
    <div class="row">
      <span>Tél:</span>
      <span>{{ $member->telephone }}</span>
    </div>
    <div class="row">
      <span>Code:</span>
      <span>{{ $member->code }}</span>
    </div>

    <div class="line"></div>

    <!-- Détails Transaction -->
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

    <!-- Montant (Mise en valeur maximale) -->
    <div class="row amount-box">
      <span>MONTANT:</span>
      <span class="text-right">
        @if($transaction->type == 'retrait') - @endif
        {{ number_format($transaction->amount, 2, ',', ' ') }} {{ $transaction->currency }}
      </span>
    </div>

    <div class="line"></div>

    <!-- Message & Pied de page -->
    <div class="text-center bold" style="font-size: 18px; margin-top: 6px;">
      Merci pour votre confiance !
    </div>

    <div class="footer">
      Ce reçu est la preuve de transaction.<br>
      Aucun remboursement sans ce document.
    </div>

    <div class="row signature-area">
      <span>Sig. Client:</span>
      <span>...............</span>
    </div>

  </div>

  <script>
    window.onload = function () {
      window.print();
    };
  </script>

</body>

</html>
