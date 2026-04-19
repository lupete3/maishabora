<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Compte de Résultat - {{ config('app.name') }}</title>
    <style>
    body {
    font-family: Arial, sans-serif;
    font-size: 9px;
    margin: 5px;
    color: #000;
    }

    .header {
    margin-bottom: 10px;
    }

    .footer {
    text-align: center;
    margin-top: 20px;
    font-size: 8px;
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
    margin-top: 5px;
    margin-bottom: 5px;
    }

    .table td,
    .table th {
    border: 1px solid #666;
    padding: 3px;
    }

    th {
    background-color: #f1c206;
    font-weight: bold;
    font-size: 9px;
    }


    .title-charge {
    color: #dc3545;
    border-bottom: 1px solid #dc3545;
    margin-bottom: 5px;
    padding-bottom: 2px;
    text-transform: uppercase;
    font-size: 11px;
    }

    .title-produit {
    color: #28a745;
    border-bottom: 1px solid #28a745;
    margin-bottom: 5px;
    padding-bottom: 2px;
    text-transform: uppercase;
    font-size: 11px;
    }

    .result-box {
    margin-top: 15px;
    text-align: center;
    border: 2px solid #333;
    padding: 10px;
    background-color: #f9f9f9;
    font-size: 12px;
    }

    .logo {
    width: 60px;
    }
    </style>
</head>

<body>

    <div class="header">
    @include('partials.pdf-header', ['reportTitle' => 'COMPTE DE RÉSULTAT (' . $currency . ')'])
        @if(isset($date_debut) && isset($date_fin) && $period_type !== 'tout')
            <p class="text-center" style="margin: 0; font-size: 11px;">
                Période : du {{ \Carbon\Carbon::parse($date_debut)->format('d/m/Y') }}
                au {{ \Carbon\Carbon::parse($date_fin)->format('d/m/Y') }}
            </p>
        @endif
    </div>

    <table style="width: 100%; border: none; border-collapse: collapse;">
        <tr>
            <td style="width: 49%; vertical-align: top; padding-right: 1%;">
                <!-- CHARGES -->
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
            </td>
            <td style="width: 49%; vertical-align: top; padding-left: 1%;">
                <!-- PRODUITS -->
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
            </td>
        </tr>
    </table>

    <div class="result-box">
        <strong>RÉSULTAT NET : {{ number_format($resultat, 2, ',', ' ') }} {{ $currency }}</strong>
        <p style="margin-top: 5px; font-size: 12px; color: {{ $resultat >= 0 ? '#28a745' : '#dc3545' }};">
            ({{ $resultat >= 0 ? 'BÉNÉFICE' : 'PERTE' }})
        </p>
    </div>

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y à H:i') }} - {{ $company->name ?? config('app.name') }}
    </div>

</body>

</html>
