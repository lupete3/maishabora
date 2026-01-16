<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Compte de Résultat - {{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 5px;
            color: #000;
        }

        .header {
            margin-bottom: 20px;
        }

        .footer {
            text-align: center;
            margin-top: 50px;
            font-size: 9px;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .text-start {
            text-align: left;
        }

        /* Table Styles */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .table td,
        .table th {
            border: 1px solid #333;
            padding: 4px;
        }

        th {
            background-color: #f1c206;
            /* Yellow header from reference */
            font-weight: bold;
        }

        /* Specific Layout for Resultat */
        .row:after {
            content: "";
            display: table;
            clear: both;
        }

        .column {
            float: left;
            width: 49%;
            margin-right: 1%;
        }

        .column:last-child {
            margin-right: 0;
            margin-left: 1%;
        }

        .title-charge {
            color: #dc3545;
            /* Red for charges */
            border-bottom: 2px solid #dc3545;
            margin-bottom: 10px;
            padding-bottom: 5px;
            text-transform: uppercase;
        }

        .title-produit {
            color: #28a745;
            /* Green for products */
            border-bottom: 2px solid #28a745;
            margin-bottom: 10px;
            padding-bottom: 5px;
            text-transform: uppercase;
        }

        .result-box {
            margin-top: 30px;
            text-align: center;
            border: 2px solid #333;
            padding: 15px;
            background-color: #f9f9f9;
            font-size: 14px;
        }

        .logo {
            width: 80px;
        }
    </style>
</head>

<body>

    <div class="header">
        <table style="width:100%;">
            <tr>
                <td style="width: 15%; border: none;">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.jpg'))) }}"
                        class="logo" alt="Logo">
                </td>
                <td style="width: 60%; text-align:center; border: none;">
                    <h2 style="margin: 0; font-size: 16px;">{{ strtoupper(config('app.name')) }}</h2>
                    <p style="margin: 0;">Adresse : {{ env('APP_ADRESS') }}</p>
                    <p style="margin: 0;">Tel : {{ env('APP_PHONE') }} – Email : {{ env('APP_EMAIL') }}</p>
                </td>
                <td style="width: 25%; text-align:right; font-size: 9px; border: none; vertical-align: top;">
                    <strong>Date :</strong> {{ now()->format('d/m/Y') }}<br>
                    <strong>Heure :</strong> {{ now()->format('H:i') }}<br>
                    <strong>Agent :</strong><br>
                    {{ Auth::user()->name }} {{ Auth::user()->postnom }}
                </td>
            </tr>
        </table>
        <hr style="margin: 10px 0; border-bottom: 2px solid #ed8d0f;">
        <h3 class="text-center" style="text-decoration: underline; margin-bottom: 5px; text-transform: uppercase;">
            COMPTE DE RÉSULTAT ({{ $currency }})
        </h3>
    </div>

    <div class="row">
        <!-- CHARGES -->
        <div class="column">
            <h3 class="title-charge">Charges (Dépenses)</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th width="20%">Code</th>
                        <th width="50%">Intitulé</th>
                        <th width="30%" class="text-end">Solde</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['Charge'] as $charge)
                        <tr>
                            <td>{{ $charge['code'] }}</td>
                            <td>{{ $charge['intitule'] }}</td>
                            <td class="text-end">{{ number_format($charge['solde'], 2, ',', ' ') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">Aucune charge</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background-color: #eee; font-weight: bold;">
                        <td colspan="2">TOTAL CHARGES</td>
                        <td class="text-end">{{ number_format($totals['Charge'], 2, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- PRODUITS -->
        <div class="column">
            <h3 class="title-produit">Produits (Recettes)</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th width="20%">Code</th>
                        <th width="50%">Intitulé</th>
                        <th width="30%" class="text-end">Solde</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['Produit'] as $product)
                        <tr>
                            <td>{{ $product['code'] }}</td>
                            <td>{{ $product['intitule'] }}</td>
                            <td class="text-end">{{ number_format($product['solde'], 2, ',', ' ') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">Aucun produit</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background-color: #eee; font-weight: bold;">
                        <td colspan="2">TOTAL PRODUITS</td>
                        <td class="text-end">{{ number_format($totals['Produit'], 2, ',', ' ') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="result-box">
        <strong>RÉSULTAT NET : {{ number_format($resultat, 2, ',', ' ') }} {{ $currency }}</strong>
        <p style="margin-top: 5px; font-size: 12px; color: {{ $resultat >= 0 ? '#28a745' : '#dc3545' }};">
            ({{ $resultat >= 0 ? 'BÉNÉFICE' : 'PERTE' }})
        </p>
    </div>

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y à H:i') }} - {{ config('app.name') }}
    </div>

</body>

</html>