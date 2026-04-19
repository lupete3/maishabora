<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport des clients - PDF</title>
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
        .balances, .billetage {
            width: 49%;
            display: inline-block;
            vertical-align: top;
        }
        .section-title {
            margin-top: 10px;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    @include('partials.pdf-header', ['reportTitle' => $titre])

    <table class="table" width="100%" style="margin-bottom: 5px;">
        <tr>
            <td><strong>Total clients :</strong> {{ $total }}</td>
            <td><strong>Hommes :</strong> {{ $totalMale }}</td>
            <td><strong>Femmes :</strong> {{ $totalFemale }}</td>
        </tr>
    </table>

        <table class="table" width="100%" border="1" cellspacing="0" cellpadding="5">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Nom</th>
                    <th>Sexe</th>
                    <th>Téléphone</th>
                    <th>Date Adhésion</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($clients as $client)
                    <tr>
                        <td>{{ $client->code }}</td>
                        <td>{{ $client->name }} {{ $client->postnom }} {{ $client->prenom }}</td>
                        <td>{{ $client->sexe }}</td>
                        <td>{{ $client->telephone }}</td>
                        <td>{{ \Carbon\Carbon::parse($client->created_at)->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>


        <div class="footer">
            Fiche générée le {{ now()->format('d/m/Y H:i') }} - {{ $company->name ?? config('app.name') }}
        </div>

</body>
</html>
