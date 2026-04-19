<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport des Carnets</title>
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
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .table td, .table th {
            border: 1px solid;
            padding: 4px;
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
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
            margin-top: 2px;
        }
        .badge-success { background: #28a745; color: #fff; }
        .badge-danger { background: #dc3545; color: #fff; }
        .logo { width: 80px; }
        th {
            background-color: #f1c206;
        }
        .section-title {
            margin-top: 10px;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }
    </style>
</head>
<body>

    @include('partials.pdf-header', ['reportTitle' => $titre])

    <table class="table" width="100%" border="1" cellspacing="0" cellpadding="5">
        <thead>
                <tr>
                    <th>#</th>
                    <th>Code</th>
                    <th>Nom complet</th>
                    <th>Téléphone</th>
                    <th>Devise</th>
                    <th>Jours</th>
                    <th>Déposé</th>
                    <th>Restant</th>
                    <th>Taux</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($carnets as $index => $carnet)
                    @php
                        $totalDepose = $carnet->contributed_days_count * $carnet->subscription_amount;
                        $totalRestant = (31 - $carnet->contributed_days_count) * $carnet->subscription_amount;
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $carnet->member->code ?? '' }}</td>
                        <td>{{ $carnet->member->name ?? '' }} {{ $carnet->member->postnom ?? '' }}
                            {{ $carnet->member->prenom ?? '' }}</td>
                        <td>{{ $carnet->member->telephone ?? '' }}</td>
                        <td>{{ strtoupper($carnet->currency) }}</td>
                        <td>{{ $carnet->contributed_days_count }}</td>
                        <td align="right">{{ number_format($totalDepose, 2) }}</td>
                        <td align="right">{{ number_format($totalRestant, 2) }}</td>
                        <td>{{ round(($carnet->contributed_days_count / 31) * 100) }} %</td>
                    </tr>
                @endforeach
        </tbody>
    </table>

    <div class="footer">
        Fiche générée le {{ now()->format('d/m/Y H:i') }} - {{ $company->name ?? config('app.name') }}
    </div>

</body>
</html>
