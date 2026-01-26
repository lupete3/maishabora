<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Compte de Résultat</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 5px 0;
        }

        .info {
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 15px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
        }

        th {
            background-color: #3498db;
            color: white;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .produits-header {
            background-color: #27ae60 !important;
        }

        .charges-header {
            background-color: #e74c3c !important;
        }

        .total-row {
            background-color: #ecf0f1;
            font-weight: bold;
        }

        .resultat {
            background-color: #f8f9fa;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
            border: 2px solid #000;
        }

        .benefice {
            color: green;
        }

        .perte {
            color: red;
        }

        .indent-1 {
            padding-left: 15px;
        }

        .indent-2 {
            padding-left: 30px;
        }

        .footer {
            margin-top: 20px;
            font-size: 8px;
            text-align: center;
            color: #666;
        }
    </style>
</head>

<body>
    {{-- En-tête --}}
    <div class="header">
        <h2>MAÏSHA BORA - Institution de Microfinance</h2>
        <h3>COMPTE DE RÉSULTAT</h3>
    </div>

    {{-- Informations --}}
    <div class="info">
        <div class="info-row">
            <div>
                <strong>Période:</strong>
                Du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }}
                au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
            </div>
            <div><strong>Devise:</strong> {{ $devise }}</div>
        </div>
        <div class="info-row">
            <div><strong>Généré le:</strong> {{ $generatedAt }}</div>
            <div><strong>Par:</strong> {{ $user->name }}</div>
        </div>
    </div>

    {{-- PRODUITS --}}
    <h4 style="background-color: #27ae60; color: white; padding: 8px;">PRODUITS D'EXPLOITATION (Classe 7)</h4>
    <table>
        <thead>
            <tr class="produits-header">
                <th style="width: 15%;">Code</th>
                <th style="width: 65%;">Intitulé</th>
                <th style="width: 20%;" class="text-right">Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse($produits as $produit)
                <tr>
                    <td>{{ $produit['code'] }}</td>
                    <td class="{{ $produit['level'] == 2 ? 'indent-1' : ($produit['level'] == 3 ? 'indent-2' : '') }}">
                        {{ $produit['intitule'] }}
                    </td>
                    <td class="text-right">{{ number_format($produit['montant'], 2, ',', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">Aucun produit enregistré</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" class="text-right"><strong>TOTAL PRODUITS</strong></td>
                <td class="text-right"><strong>{{ number_format($totalProduits, 2, ',', ' ') }} {{ $devise }}</strong>
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- CHARGES --}}
    <h4 style="background-color: #e74c3c; color: white; padding: 8px;">CHARGES D'EXPLOITATION (Classe 6)</h4>
    <table>
        <thead>
            <tr class="charges-header">
                <th style="width: 15%;">Code</th>
                <th style="width: 65%;">Intitulé</th>
                <th style="width: 20%;" class="text-right">Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse($charges as $charge)
                <tr>
                    <td>{{ $charge['code'] }}</td>
                    <td class="{{ $charge['level'] == 2 ? 'indent-1' : ($charge['level'] == 3 ? 'indent-2' : '') }}">
                        {{ $charge['intitule'] }}
                    </td>
                    <td class="text-right">{{ number_format($charge['montant'], 2, ',', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">Aucune charge enregistrée</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" class="text-right"><strong>TOTAL CHARGES</strong></td>
                <td class="text-right"><strong>{{ number_format($totalCharges, 2, ',', ' ') }} {{ $devise }}</strong>
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- RÉSULTAT NET --}}
    <div class="resultat">
        <div style="font-size: 12px; margin-bottom: 10px;">
            <strong>RÉSULTAT NET DE L'EXERCICE</strong>
        </div>
        <div style="font-size: 16px;" class="{{ $resultatNet >= 0 ? 'benefice' : 'perte' }}">
            @if($resultatNet >= 0)
                <strong>BÉNÉFICE:</strong>
            @else
                <strong>PERTE:</strong>
            @endif
            <strong>{{ number_format(abs($resultatNet), 2, ',', ' ') }} {{ $devise }}</strong>
        </div>
        <div style="font-size: 9px; margin-top: 5px; color: #666;">
            (Produits {{ number_format($totalProduits, 2, ',', ' ') }} - Charges
            {{ number_format($totalCharges, 2, ',', ' ') }})
        </div>
    </div>

    {{-- Pied de page --}}
    <div class="footer">
        <p>Document généré automatiquement par le système MAÏSHA BORA le {{ $generatedAt }}</p>
        <p>Compte de Résultat - Période du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} au
            {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}</p>
    </div>
</body>

</html>