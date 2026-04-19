<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport des Conversions</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 5px;
            color: #000;
        }
        .footer { text-align: center; margin-top: 50px }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
        }
        .table td, .table th {
            border: 1px solid #000; padding: 2px; font-size: 8px;
        }
        .signature {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }
        .signature-block {
            width: 45%;
            text-align: center;
        }
        th {
            background-color: #f1c206;
        }
        .section-title {
            margin-top: 10px;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }
        .totals p {
            margin: 2px 0;
        }
        .logo {
            width: 80px;
        }
        .footer {
            position: fixed;
            bottom: 10px;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #888;
        }
    </style>
</head>
<body>

    @include('partials.pdf-header', ['reportTitle' => 'RAPPORT DES CONVERSIONS'])

    <!-- Transactions -->
    <div class="title-section">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Utilisateur</th>
                    <th>Montant Sortie</th>
                    <th>Devise Sortie</th>
                    <th>Montant Entrée</th>
                    <th>Devise Entrée</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($conversions as $index => $sortie)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $sortie->user->name ?? 'N/A' }}</td>
                        <td>{{ number_format($sortie->amount, 2, ',', '.') }}</td>
                        <td>{{ strtoupper($sortie->currency) }}</td>
                        <td>{{ number_format(optional($sortie->paired_entry)->amount, 2, ',', '.') ?? '-' }}</td>
                        <td>{{ strtoupper(optional($sortie->paired_entry)->currency) ?? '-' }}</td>
                        <td>{{ $sortie->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        Généré par {{ auth()->user()->name. ' '.auth()->user()->postnom }} – {{ $company->name ?? config('app.name') }}
    </div>

</body>
</html>
