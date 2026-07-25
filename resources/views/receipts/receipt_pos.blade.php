<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Reçu #{{ $transaction->id }}</title>
  <style>
    @media print {
      @page {
        margin: 0;
      }

      body {
        margin: 0;
        padding: 0;
        font-family: 'Courier New', monospace;
        font-size: 35px;
      }
    }

    body {
      margin: 0;
      padding: 10px;
      font-family: 'Courier New', monospace;
      font-size: 35px;
      line-height: 1.8; /* Augmenté pour aérer le texte */
    }

    .center {
      text-align: center;
    }

    .bold {
      font-weight: bold;
    }

    .line {
      border-top: 4px dashed #000;
      margin: 18px 0; /* Plus d'espace autour des lignes */
    }

    .info-item {
      margin: 12px 0; /* Plus d'espace pour les infos client */
    }

    .row {
      display: flex;
      justify-content: space-between;
      margin: 12px 0; /* Plus d'espace entre les éléments côte à côte */
    }

    .footer {
      font-size: 25px;
      text-align: center;
      margin-top: 25px;
      margin-bottom: 25px;
    }

    .img-center {
      display: block;
      margin: 0 auto 10px auto;
      max-width: 100px;
    }

    .signatures {
      margin-top: 35px;
      margin-bottom: 20px;
    }
  </style>
</head>

<body>

  <!-- Logo & En-tête -->
  @php
      $logoPath = public_path('assets/img/logo.jpg');
      $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
  @endphp
  @if($logoData)
      <div class="center">
          <img src="data:image/png;base64,{{ $logoData }}" width="80px" alt="logo" />
      </div>
  @endif
  <div class="center bold">{{ strtoupper($company?->name ?? config('app.name')) }}</div>
  <div class="center" style="font-size: 25px; line-height: 1.5;">
    N° ID : {{ $company?->rccm ?? env('APP_RCCM', '000-000-000') }}<br>
    Adresse : {{ $company?->address ?? env('APP_ADRESS', 'Adresse non définie') }}<br>
    Tél : {{ $company?->phone ?? env('APP_PHONE', '+243 000 000 000') }}
  </div>

  <div class="line"></div>

  <!-- Titre -->
  <div class="center bold" style="font-size: 40px; margin: 10px 0;">REÇU DE TRANSACTION</div>
  <div class="center" style="margin-bottom: 10px;">{{ now()->format('d/m/Y H:i') }}</div>

  <div class="line"></div>

  <!-- Client -->
  <div class="info-item"><strong>Client:</strong> {{ $member->name }} {{ $member->postnom }} {{ $member->prenom }}</div>
  <div class="info-item"><strong>Tél:</strong> {{ $member->telephone }}</div>
  <div class="info-item"><strong>Code:</strong> {{ $member->code }}</div>

  <div class="line"></div>

  <!-- Transaction -->
  <div class="row">
    <div>Type: <strong>{{ ucfirst($transaction->type) }}</strong></div>
  </div>
  <div class="row">
    <div>Montant:</div>
    <div class="bold">
      @if($transaction->type == 'retrait') - @endif
      {{ number_format($transaction->amount, 2, ',', ' ') }} {{ $transaction->currency }}
    </div>
  </div>
  <div class="row">
    <div>Date:</div>
    <div class="bold">{{ $transaction->created_at->format('d/m/Y H:i') }}</div>
  </div>
  <div class="row">
    <div>Réf:</div>
    <div class="bold">#{{ $transaction->id }}</div>
  </div>
  <div class="row">
    <div>Agent:</div>
    <div class="bold">{{ $agent->name }}</div>
  </div>

  <div class="line"></div>
  <div class="center bold" style="margin: 15px 0;">Merci pour votre confiance</div>

  <!-- Pied de page -->
  <div class="footer">
    Ce reçu est la preuve de transaction.<br>
    Aucun remboursement sans ce document.
  </div>

  <div class="line"></div>

  <!-- Signatures Client et Agent -->
  <div class="row signatures">
    <div>
      Sig. Client:<br>
      <strong>{{ $member->name }} {{ $member->postnom }}</strong>
    </div>
    <div style="text-align: right;">
      Sig. Agent:<br>
      <strong>{{ $agent->name }}</strong>
    </div>
  </div>

  <!-- Impression auto -->
  <script>
    window.onload = function () {
      window.print();
    };
  </script>

</body>

</html>
