<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultat Financier</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }
        h2, h4 {
            text-align: center;
            margin: 0;
            padding: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }
        thead {
            background: #f2f2f2;
            font-weight: bold;
        }
        tfoot {
            background: #e6e6e6;
            font-weight: bold;
        }
        .summary {
            margin-top: 20px;
            padding: 10px;
        }
        .info { color: #004085; }
        .success { color: #155724; }
    </style>
</head>
<body>
    <h2>Résultat Financier</h2>
    <h4>Devise : {{ $currency }}</h4>

    <table>
        <thead>
            <tr>
                <th colspan="3">ACTIFS</th>
                <th colspan="3">PASSIFS</th>
                <th colspan="3">PRODUITS</th>
                <th colspan="3">CHARGES</th>
            </tr>
            <tr>
                <th>Code</th>
                <th>Intitulé</th>
                <th>Solde</th>
                <th>Code</th>
                <th>Intitulé</th>
                <th>Solde</th>
                <th>Code</th>
                <th>Intitulé</th>
                <th>Solde</th>
                <th>Code</th>
                <th>Intitulé</th>
                <th>Solde</th>
            </tr>
        </thead>
        <tbody>
            @php
                $max = max(
                    $accounts->where('type','Actif')->count(),
                    $accounts->where('type','Passif')->count(),
                    $accounts->where('type','Produit')->count(),
                    $accounts->where('type','Charge')->count()
                );
            @endphp

            @for ($i = 0; $i < $max; $i++)
                <tr>
                    {{-- Actif --}}
                    @if(isset($accounts->where('type','Actif')->values()[$i]))
                        @php $a = $accounts->where('type','Actif')->values()[$i]; @endphp
                        <td>{{ $a->code }}</td>
                        <td>{{ $a->intitule }}</td>
                        <td>{{ number_format($a->journals->sum('montant_debit') - $a->journals->sum('montant_credit'),2,',',' ') }}</td>
                    @else
                        <td colspan="3"></td>
                    @endif

                    {{-- Passif --}}
                    @if(isset($accounts->where('type','Passif')->values()[$i]))
                        @php $p = $accounts->where('type','Passif')->values()[$i]; @endphp
                        <td>{{ $p->code }}</td>
                        <td>{{ $p->intitule }}</td>
                        <td>{{ number_format($p->journals->sum('montant_debit') - $p->journals->sum('montant_credit'),2,',',' ') }}</td>
                    @else
                        <td colspan="3"></td>
                    @endif

                    {{-- Produit --}}
                    @if(isset($accounts->where('type','Produit')->values()[$i]))
                        @php $pr = $accounts->where('type','Produit')->values()[$i]; @endphp
                        <td>{{ $pr->code }}</td>
                        <td>{{ $pr->intitule }}</td>
                        <td>{{ number_format($pr->journals->sum('montant_debit') - $pr->journals->sum('montant_credit'),2,',',' ') }}</td>
                    @else
                        <td colspan="3"></td>
                    @endif

                    {{-- Charge --}}
                    @if(isset($accounts->where('type','Charge')->values()[$i]))
                        @php $c = $accounts->where('type','Charge')->values()[$i]; @endphp
                        <td>{{ $c->code }}</td>
                        <td>{{ $c->intitule }}</td>
                        <td>{{ number_format($c->journals->sum('montant_debit') - $c->journals->sum('montant_credit'),2,',',' ') }}</td>
                    @else
                        <td colspan="3"></td>
                    @endif
                </tr>
            @endfor
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total Actif</td>
                <td>{{ number_format($totals['Actif']['solde'],2,',',' ') }}</td>

                <td colspan="2">Total Passif</td>
                <td>{{ number_format($totals['Passif']['solde'],2,',',' ') }}</td>

                <td colspan="2">Total Produits</td>
                <td>{{ number_format($totals['Produit']['solde'],2,',',' ') }}</td>

                <td colspan="2">Total Charges</td>
                <td>{{ number_format($totals['Charge']['solde'],2,',',' ') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="summary">
        <p class="info">
            Différence Bilan (Actif - Passif) :
            <strong>{{ number_format($differences['bilan'],2,',',' ') }}</strong>
        </p>
        <p class="success">
            Résultat Net (Produits - Charges) :
            <strong>{{ number_format($differences['resultat'],2,',',' ') }}</strong>
        </p>
    </div>
</body>
</html>