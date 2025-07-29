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
      font-family: 'Courier New', monospace;
      font-size: 35px;
      line-height: 1.4;
    }

    .center {
      text-align: center;
    }

    .bold {
      font-weight: bold;
    }

    .line {
      border-top: 4px dashed #000;
      margin: 8px 0;
    }

    .row {
      display: flex;
      justify-content: space-between;
      margin: 2px 0;
    }

    .footer {
      font-size: 25px;
      text-align: center;
      margin-top: 15px;
    }

    .img-center {
      display: block;
      margin: 0 auto 5px auto;
      max-width: 100px;
    }
  </style>
</head>
<body>

  <!-- Logo -->
  {{-- <div class="center">
    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.jpg'))) }}"
        width="100px" alt="" />
  </div>}}
      {{-- <img src="{{ asset('assets/img/logo.jpg') }}" width="100px" alt="" class="img-center" srcset=""> --}}


  <!-- En-tête -->
  <div class="center bold" style="font-size: 50px;">{{ config('app.name') }}</div>
  <div class="center">N° ID : {{ env('APP_RCCM', '000-000-000') }}</div>
  <div class="center">Adresse : {{ env('APP_ADRESS', 'Adresse non définie') }}</div>
  <div class="center">Tél : {{ env('APP_PHONE', '+243 000 000 000') }}</div>
  <div class="line"></div>

  <!-- Titre -->
  <div class="center bold" style="font-size: 40px;">REÇU DE TRANSACTION</div>
  <div class="center">{{ now()->format('d/m/Y H:i') }}</div>
  <div class="line"></div>

  <!-- Client -->
  <div><strong>Client:</strong> {{ $member->name }} {{ $member->postnom }} {{ $member->prenom }}</div>
  <div><strong>Tél:</strong> {{ $member->telephone }}</div>
  <div><strong>Code:</strong> {{ $member->code }}</div>
  <div class="line"></div>

  <!-- Transaction -->
  <div class="row">
    <div>Type: <strong>{{ ucfirst($transaction->type) }}</strong></div>
  </div>
  <div class="row">
    <div>Montant</div>
    <div class="bold">
      @if($transaction->type == 'retrait') - @endif
      {{ number_format($transaction->amount, 2, ',', ' ') }} {{ $transaction->currency }}
    </div>
  </div>
  <div class="row">
    <div>Date: <strong>{{ $transaction->created_at->format('d/m/Y H:i') }}</strong></div>
  </div>
  <div class="row">
    <div>Réf: <strong>#{{ $transaction->id }}</strong></div>

  </div>
  <div class="row">
    <div>Agent: <strong>{{ $agent->name }}</strong></div>

  </div>

  <div class="line"></div>
  <div class="center bold">Merci pour votre confiance</div>

  <!-- Pied de page -->
  <div class="footer">
    Ce reçu est valable comme preuve de transaction.<br>
    Aucun remboursement sans ce document.
  </div>

  <div class="row" style="margin-top: 15px;">
    <div>Client: <strong>{{ $member->name }} {{ $member->postnom }}</strong></div>
  </div>

  <!-- Impression auto -->
  <script>
    window.onload = function () {
      window.print();
    };
  </script>

</body>
</html>
