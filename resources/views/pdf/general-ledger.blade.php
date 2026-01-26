<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Grand Livre - {{ $compte->code }}</title>
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
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
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

        .footer {
            margin-top: 20px;
            font-size: 8px;
            text-align: center;
            color: #666;
        }

        .solde-initial {
            background-color: #ecf0f1;
            padding: 8px;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .solde-final {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .debit {
            color: #000;
        }

        .credit {
            color: #000;
        }

        .positive {
            color: green;
        }

        .negative {
            color: red;
        }
    </style>
</head>

<body>
    {{-- En-tête --}}
    <div class="header">
        <h2>MAÏSHA BORA - Institution de Microfinance</h2>
        <h3>GRAND LIVRE</h3>
    </div>

    {{-- Informations compte --}}
    <div class="info">
        <div class="info-row">
            <div><strong>Compte:</strong> {{ $compte->code }} - {{ $compte->intitule }}</div>
            <div><strong>Devise:</strong> {{ $devise }}</div>
        </div>
        <div class="info-row">
            <div><strong>Hiérarchie:</strong> {{ $compte->getHierarchyPath() }}</div>
            <div><strong>Type:</strong> {{ $compte->type }}</div>
        </div>
        <div class="info-row">
            <div>
                <strong>Période:</strong>
                @if($dateDebut && $dateFin)
                    Du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }}
                    au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
                @else
                    Toutes les opérations
                @endif
            </div>
            <div><strong>Généré le:</strong> {{ $generatedAt }}</div>
        </div>
        <div class="info-row">
            <div><strong>Utilisateur:</strong> {{ $user->name }}</div>
            <div><strong>Nombre d'écritures:</strong> {{ $journals->count() }}</div>
        </div>
    </div>

    {{-- Solde initial --}}
    <div class="solde-initial">
        Solde initial:
        <span class="{{ $soldeInitial >= 0 ? 'positive' : 'negative' }}">
            {{ number_format(abs($soldeInitial), 2, ',', ' ') }} {{ $devise }}
            @if($soldeInitial >= 0)
                (Débiteur)
            @else
                (Créditeur)
            @endif
        </span>
    </div>

    {{-- Tableau des écritures --}}
    @if($journals->count() > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">Date</th>
                    <th style="width: 35%;">Libellé</th>
                    <th style="width: 15%;">Référence</th>
                    <th style="width: 13%;" class="text-right">Débit</th>
                    <th style="width: 13%;" class="text-right">Crédit</th>
                    <th style="width: 14%;" class="text-right">Solde</th>
                </tr>
            </thead>
            <tbody>
                @foreach($journals as $journal)
                    <tr>
                        <td class="text-center">{{ \Carbon\Carbon::parse($journal->date_operation)->format('d/m/Y') }}</td>
                        <td>{{ $journal->libelle }}</td>
                        <td><small>{{ $journal->reference }}</small></td>
                        <td class="text-right debit">
                            @if($journal->montant_debit > 0)
                                {{ number_format($journal->montant_debit, 2, ',', ' ') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right credit">
                            @if($journal->montant_credit > 0)
                                {{ number_format($journal->montant_credit, 2, ',', ' ') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right {{ $journal->solde_progressif >= 0 ? 'positive' : 'negative' }}">
                            {{ number_format(abs($journal->solde_progressif), 2, ',', ' ') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="solde-final">
                    <td colspan="3" class="text-right"><strong>TOTAUX ET SOLDE FINAL</strong></td>
                    <td class="text-right">
                        {{ number_format($journals->sum('montant_debit'), 2, ',', ' ') }}
                    </td>
                    <td class="text-right">
                        {{ number_format($journals->sum('montant_credit'), 2, ',', ' ') }}
                    </td>
                    <td class="text-right {{ $soldeFinal >= 0 ? 'positive' : 'negative' }}">
                        {{ number_format(abs($soldeFinal), 2, ',', ' ') }} {{ $devise }}
                    </td>
                </tr>
            </tfoot>
        </table>
    @else
        <p style="text-align: center; padding: 20px; background-color: #fff3cd;">
            Aucune écriture trouvée pour la période sélectionnée.
        </p>
    @endif

    {{-- Pied de page --}}
    <div class="footer">
        <p>Document généré automatiquement par le système MAÏSHA BORA le {{ $generatedAt }}</p>
        <p>Grand Livre - Compte {{ $compte->code }} - {{ $compte->intitule }}</p>
    </div>
</body>

</html>