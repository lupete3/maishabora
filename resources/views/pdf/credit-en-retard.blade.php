<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport des crédits en retard</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 5px; color: #000; }
        .header { text-align: center; margin-bottom: 30px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table td, .table th { border: 1px solid #000; padding: 2px; font-size: 10px; }
        th { background-color: #f1c206; }
        .logo { width: 80px; }
        td:first-child, th:first-child { text-align: center; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
@foreach ($data as $section)
    <div class="header">
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
            CRÉDITS EN RETARD - {{ $section['currency'] }}
        </h3>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th><th>Membre</th><th>Date Crédit</th><th>Date Debut</th>
                <th>Montant</th><th>Solde</th><th>Pénalités</th><th>% Pénalités</th><th>Jours Retard</th>
                <th>1-30j</th><th>31-60j</th><th>61-90j</th><th>91-180j</th>
                <th>181-360j</th><th>361-720j</th><th>>720j</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($section['details'] as $d)
                <tr>
                    <td>{{ $d['id'] }}</td>
                    <td>{{ $d['member'] }}</td>
                    <td>{{ $d['date_credit'] }}</td>
                    <td>{{ $d['date'] }}</td>
                    <td>{{ number_format($d['amount'], 2) }}</td>
                    <td>{{ number_format($d['remaining'], 2) }}</td>
                    <td>{{ number_format($d['penalty'], 2) }}</td>
                    <td>{{ $d['penalty_percent'] }}%</td>
                    <td>{{ $d['days_late'] }}</td>
                    <td>{{ $d['range_1'] ? number_format($d['range_1'], 2) : '' }}</td>
                    <td>{{ $d['range_2'] ? number_format($d['range_2'], 2) : '' }}</td>
                    <td>{{ $d['range_3'] ? number_format($d['range_3'], 2) : '' }}</td>
                    <td>{{ $d['range_4'] ? number_format($d['range_4'], 2) : '' }}</td>
                    <td>{{ $d['range_5'] ? number_format($d['range_5'], 2) : '' }}</td>
                    <td>{{ $d['range_6'] ? number_format($d['range_6'], 2) : '' }}</td>
                    <td>{{ $d['range_7'] ? number_format($d['range_7'], 2) : '' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold;">
                <td colspan="4">Totaux</td>
                <td>{{ number_format($section['totaux']['credit_amount'], 2) }}</td>
                <td>{{ number_format($section['totaux']['remaining_balance'], 2) }}</td>
                <td>{{ number_format($section['totaux']['total_penalty'], 2) }}</td>
                <td colspan="2"></td>
                <td>{{ number_format($section['totaux']['range_1'], 2) }}</td>
                <td>{{ number_format($section['totaux']['range_2'], 2) }}</td>
                <td>{{ number_format($section['totaux']['range_3'], 2) }}</td>
                <td>{{ number_format($section['totaux']['range_4'], 2) }}</td>
                <td>{{ number_format($section['totaux']['range_5'], 2) }}</td>
                <td>{{ number_format($section['totaux']['range_6'], 2) }}</td>
                <td>{{ number_format($section['totaux']['range_7'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    @if (!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach
</body>
</html>