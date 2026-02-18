<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Rapport des Écarts de Caisse</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 8px;
            margin: 10px;
            color: #000;
        }

        .header {
            padding-bottom: 5px;
        }

        .logo {
            width: 80px;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .table th,
        .table td {
            border: 1px solid #000;
            padding: 4px;
        }

        th {
            background-color: #f1c206;
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 7px;
            color: white;
        }

        .bg-success {
            background: #28a745;
        }

        .bg-danger {
            background: #dc3545;
        }

        .bg-warning {
            background: #ffc107;
            color: black;
        }

        .bg-info {
            background: #17a2b8;
        }

        .filters {
            margin-bottom: 10px;
            font-style: italic;
            background: #f8f9fa;
            padding: 5px;
            border: 1px solid #ddd;
            font-size: 7px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 7px;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        .summary-box {
            margin-top: 15px;
            width: 35%;
            float: right;
        }

        .summary-box table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-box td {
            border: 1px solid #000;
            padding: 4px;
        }

        .clear {
            clear: both;
        }
    </style>
</head>

<body>
    <div class="header">
        <table style="width:100%;">
            <tr>
                <td style="width: 15%;">
                    @if(file_exists(public_path('logo.jpg')))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.jpg'))) }}"
                            class="logo">
                    @endif
                </td>
                <td style="width: 60%; text-align:center;">
                    <h2 style="margin: 0; font-size: 14px;">{{ strtoupper(config('app.name')) }}</h2>
                    <p style="margin: 0;">Adresse : {{ env('APP_ADRESS') }}</p>
                    <p style="margin: 0;">Tel : {{ env('APP_PHONE') }} – Email : {{ env('APP_EMAIL') }}</p>
                </td>
                <td style="width: 25%; text-align:right; font-size: 9px;">
                    <strong>Date :</strong> {{ now()->format('d/m/Y') }}<br>
                    <strong>Heure :</strong> {{ now()->format('H:i') }}<br>
                    <strong>Généré par :</strong> {{ auth()->user()->name }}
                </td>
            </tr>
        </table>
        <hr style="margin: 10px 0; border-bottom: 2px solid #ed8d0f;">
        <h3 class="text-center" style="text-decoration: underline; margin-bottom: 5px;">RAPPORT DES ÉCARTS DE CAISSE
        </h3>
    </div>

    <div class="filters">
        <strong>Filtres appliqués :</strong>
        Devise: {{ $filters['currency'] ?: 'Toutes' }} |
        Statut: {{ $filters['status'] ?: 'Tous' }} |
        Type: {{ $filters['type'] ?: 'Tous' }} |
        Période: {{ ($filters['date_from'] ?: '...') . ' au ' . ($filters['date_to'] ?: '...') }}
        @if($filters['agent_name']) | Agent: {{ $filters['agent_name'] }} @endif
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date Clôture</th>
                <th>Agent</th>
                <th>Type</th>
                <th>Devise</th>
                <th>Montant</th>
                <th>Statut</th>
                <th>Justification / Résolution</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ecarts as $ecart)
                <tr>
                    <td>#{{ $ecart->id }}</td>
                    <td class="text-center">
                        {{ $ecart->cloture ? \Carbon\Carbon::parse($ecart->cloture->closing_date)->format('d/m/Y') : '-' }}
                    </td>
                    <td>{{ $ecart->user->name ?? '-' }} {{ $ecart->user->postnom ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $ecart->type === 'surplus' ? 'bg-success' : 'bg-danger' }}">
                            {{ strtoupper($ecart->type) }}
                        </span>
                    </td>
                    <td class="text-center">{{ $ecart->currency }}</td>
                    <td class="text-end fw-bold">{{ number_format($ecart->amount, 2) }}</td>
                    <td class="text-center">
                        <span
                            class="badge {{ $ecart->status === 'cloture' ? 'bg-success' : ($ecart->status === 'en_cours' ? 'bg-info' : 'bg-warning') }}">
                            {{ strtoupper($ecart->status) }}
                        </span>
                    </td>
                    <td>
                        @if($ecart->description) <small><strong>Initiale:</strong> {{ $ecart->description }}</small><br>
                        @endif
                        @if($ecart->resolution_note) <small><strong>Résolution:</strong>
                        {{ $ecart->resolution_note }}</small> @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <table>
            <tr>
                <td colspan="2" style="background: #f1c206; font-weight: bold; text-align: center;">RÉSUMÉ DES ÉCARTS
                    FILTRÉS</td>
            </tr>
            <tr>
                <td>Total Surplus USD</td>
                <td class="text-end">
                    {{ number_format($ecarts->where('type', 'surplus')->where('currency', 'USD')->sum('amount'), 2) }} $
                </td>
            </tr>
            <tr>
                <td>Total Déficit USD</td>
                <td class="text-end" style="color: red;">
                    {{ number_format($ecarts->where('type', 'deficit')->where('currency', 'USD')->sum('amount'), 2) }} $
                </td>
            </tr>
            <tr>
                <td>Total Surplus CDF</td>
                <td class="text-end">
                    {{ number_format($ecarts->where('type', 'surplus')->where('currency', 'CDF')->sum('amount'), 2) }}
                    FC
                </td>
            </tr>
            <tr>
                <td>Total Déficit CDF</td>
                <td class="text-end" style="color: red;">
                    {{ number_format($ecarts->where('type', 'deficit')->where('currency', 'CDF')->sum('amount'), 2) }}
                    FC
                </td>
            </tr>
        </table>
    </div>

    <div class="clear"></div>

    <div class="footer">
        {{ config('app.name') }} - Système de Gestion Intégré - Page 1
    </div>
</body>

</html>