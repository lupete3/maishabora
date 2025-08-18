<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport des Remboursements</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 5px;
            color: #000;
        }
        .footer { text-align: center; margin-top: 50px; font-size: 9px; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
        }
        .table td, .table th {
            border: 1px solid #000; padding: 4px; font-size: 9px;
        }
        th {
            background-color: #f1c206;
        }
        .logo { width: 80px; }
    </style>
</head>
<body>

    {{-- ✅ ENTÊTE --}}
    <div class="header" style="padding-bottom: 5px;">
        <table style="width:100%;">
            <tr>
                <td style="width: 15%;">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.jpg'))) }}" class="logo" alt="Logo">
                </td>
                <td style="width: 60%; text-align:center;">
                    <h2 style="margin: 0; font-size: 14px;">{{ strtoupper(config('app.name')) }}</h2>
                    <p style="margin: 0;">Adresse : {{ env('APP_ADRESS') }}</p>
                    <p style="margin: 0;">Tel : {{ env('APP_PHONE') }} – Email : {{ env('APP_EMAIL') }}</p>
                </td>
                <td style="width: 25%; text-align:right; font-size: 9px;">
                    <strong>Date :</strong> {{ now()->format('d/m/Y') }}<br>
                    <strong>Heure :</strong> {{ now()->format('H:i') }}<br>
                    <strong>Agent :</strong><br>
                    {{ Auth::user()->name ?? 'N/A' }} {{ Auth::user()->postnom ?? '' }} {{ Auth::user()->prenom ?? '' }}
                </td>
            </tr>
        </table>
        <hr style="margin: 10px 0; border-bottom: 2px solid #ed8d0f;">
        <h3 class="text-center" style="text-decoration: underline; margin-bottom: 2px;">
            RAPPORT DES REMBOURSEMENTS ({{ ucfirst($reportType) }})
        </h3>
        <p class="text-center">Devise : <strong>{{ strtoupper($currency) }}</strong></p>
    </div>

    {{-- ✅ TABLEAU DES REMBOURSEMENTS --}}
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Code</th>
                <th>Membre</th>
                <th>Montant Remboursé</th>
                <th>Pénalité</th>
                <th>Devise</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $repayment)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($repayment->paid_at)->format('d/m/Y') }}</td>
                    <td>
                        @if ($repayment->credit && $repayment->credit->user)
                            {{ $repayment->credit->user->code }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>
                        @if ($repayment->credit && $repayment->credit->user)
                            {{ $repayment->credit->user->name . ' ' . $repayment->credit->user->postnom . ' ' . $repayment->credit->user->prenom }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>{{ number_format($repayment->expected_amount, 2) }}</td>
                    <td>{{ number_format($repayment->penality, 2) }}</td>
                    <td>
                        @if ($repayment->credit)
                            {{ $repayment->credit->currency }}
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">Aucune donnée disponible.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ✅ PIED DE PAGE --}}
    <div class="footer">
        Rapport généré le {{ now()->format('d/m/Y H:i') }} par 
        {{ Auth::user()->name }} {{ Auth::user()->postnom }} – {{ config('app.name') }}
    </div>

</body>
</html>