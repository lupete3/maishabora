<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Fiche de Clôture - {{ $cloture->user->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 7px;
            margin: 5px;
            color: #000;
        }

        .footer {
            text-align: center;
            margin-top: 50px
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

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .table td,
        .table th {
            border: 1px solid;
            padding: 2px;
        }

        .signature {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            font-size: 8px;
        }

        .signature-block {
            width: 45%;
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            margin-top: 2px;
        }

        .badge-success {
            background: #28a745;
            color: #fff;
        }

        .badge-danger {
            background: #dc3545;
            color: #fff;
        }

        .logo {
            width: 80px;
        }

        th {
            background-color: #f1c206;
        }

        .balances,
        .billetage {
            width: 49%;
            display: inline-block;
            vertical-align: top;
        }

        .section-title {
            margin-top: 5px;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 2px;
        }
    </style>
</head>

<body>

    @include('partials.pdf-header', [
        'reportTitle' => 'FICHE DE CLÔTURE DE CAISSE',
        'date' => \Carbon\Carbon::parse($cloture->closing_date)->format('d/m/Y'),
        'heure' => \Carbon\Carbon::parse($cloture->created_at)->format('H:i'),
        'agent_name' => $cloture->user->name . ' ' . $cloture->user->postnom
    ])
    <p class="text-center">
        Statut :
        @if($cloture->status == 'validated')
            <span class="badge badge-success">VALIDÉE</span>
        @elseif($cloture->status == 'rejected')
            <span class="badge badge-danger">REJETÉE</span>
        @else
            EN ATTENTE
        @endif
        @if($cloture->validated_at)
            | du {{ $cloture->validated_at->format('d/m/Y H:i') }}
            par {{ $cloture->validatedBy?->name }} {{ $cloture->validatedBy?->postnom }}
        @endif
    </p>

    <div class="section-title">SOLDES</div>
    <table class="table">
        <thead>
            <tr>
                <th>Devise</th>
                <th>Logique</th>
                <th>Physique</th>
                <th>Écart</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>USD</td>
                <td>{{ number_format($cloture->logical_usd, 2) }}</td>
                <td>{{ number_format($cloture->physical_usd, 2) }}</td>
                <td>{{ number_format($cloture->gap_usd, 2) }}</td>
            </tr>
            <tr>
                <td>CDF</td>
                <td>{{ number_format($cloture->logical_cdf, 2) }}</td>
                <td>{{ number_format($cloture->physical_cdf, 2) }}</td>
                <td>{{ number_format($cloture->gap_cdf, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">BILLETAGE</div>
    <div class="billetage">
        <h4 style="text-align:center;">USD</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Valeur</th>
                    <th>Quantité</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cloture->billetages->where('currency', 'USD') as $billet)
                    <tr>
                        <td>${{ number_format($billet->denomination, 0) }}</td>
                        <td>{{ $billet->quantity }}</td>
                        <td>${{ number_format($billet->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="billetage">
        <h4 style="text-align:center;">CDF</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Valeur</th>
                    <th>Quantité</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cloture->billetages->where('currency', 'CDF') as $billet)
                    <tr>
                        <td>{{ number_format($billet->denomination, 0) }} CDF</td>
                        <td>{{ $billet->quantity }}</td>
                        <td>{{ number_format($billet->total, 2) }} CDF</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>


    <table style="width:100%; margin-top:40px">
        <tr>
            <td style="width: 49%; text-align:left; font-size: 12px;">
                <strong>Agent :</strong><br><br><br>
                {{ $cloture->user->name }} {{ $cloture->user->postnom }}
            </td>
            <td style="width: 49%; text-align:right; font-size: 12px;">
                <strong>Visa Responsable</strong><br><br><br>
                @if($cloture->validatedBy)

                    {{ $cloture->validatedBy->name }} {{ $cloture->validatedBy->postnom }}
                @else

                    (Cette clôture a été rejetée)
                @endif
            </td>
        </tr>
    </table>

    <div class="footer">
        Fiche générée par {{ auth()->user()->name . ' ' . auth()->user()->postnom }} le {{ now()->format('d/m/Y H:i') }}
        - {{ $company->name ?? config('app.name') }}
    </div>

    <div style="page-break-after: always;"></div>

    @include('partials.pdf-header', [
        'reportTitle' => 'FEUILLE DE CAISSE N° ' . $cloture->id,
        'date' => \Carbon\Carbon::parse($cloture->closing_date)->format('d/m/Y'),
        'heure' => \Carbon\Carbon::parse($cloture->created_at)->format('H:i'),
        'agent_name' => $cloture->user->name . ' ' . $cloture->user->postnom
    ])

    <div class="section-title">TABLEAU DES DÉPÔTS</div>
    <table class="table">
        <thead>
            <tr>
                <th>N° Transaction</th>
                <th>Date & Heure</th>
                <th>Libellé</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cloture->deposits as $dep)
                <tr>
                    <td>{{ $dep->id }}</td>
                    <td>{{ $dep->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $dep->description ?? $dep->type }}</td>
                    <td class="text-end">{{ number_format($dep->amount, 2) }} {{ $dep->currency }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Aucun dépôt enregistré</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-end">TOTAL DÉPÔTS (Opérations) USD</th>
                <th class="text-end">{{ number_format($cloture->pure_deposits_usd, 2) }} USD</th>
            </tr>
            <tr>
                <th colspan="3" class="text-end">TOTAL DÉPÔTS (Opérations) CDF</th>
                <th class="text-end">{{ number_format($cloture->pure_deposits_cdf, 2) }} CDF</th>
            </tr>
        </tfoot>
    </table>

    <div class="section-title">TABLEAU DES RETRAITS</div>
    <table class="table">
        <thead>
            <tr>
                <th>N° Transaction</th>
                <th>Date & Heure</th>
                <th>Libellé</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cloture->withdrawals as $wit)
                <tr>
                    <td>{{ $wit->id }}</td>
                    <td>{{ $wit->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $wit->description ?? $wit->type }}</td>
                    <td class="text-end">{{ number_format($wit->amount, 2) }} {{ $wit->currency }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">Aucun retrait enregistré</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-end">TOTAL RETRAITS (Opérations) USD</th>
                <th class="text-end">{{ number_format($cloture->pure_withdrawals_usd, 2) }} USD</th>
            </tr>
            <tr>
                <th colspan="3" class="text-end">TOTAL RETRAITS (Opérations) CDF</th>
                <th class="text-end">{{ number_format($cloture->pure_withdrawals_cdf, 2) }} CDF</th>
            </tr>
        </tfoot>
    </table>

    <div class="section-title">TABLEAU DES TRANSFERTS ET AUTRES FLUX</div>
    <table class="table">
        <thead>
            <tr>
                <th>N° Transaction</th>
                <th>Date & Heure</th>
                <th>Type / Libellé</th>
                <th>Sens</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cloture->other_flows as $flow)
                @php
                    $isInflow = in_array($flow->type, ['virement_caisse_entrant', 'frais_credit_pour_retrait']);
                @endphp
                <tr>
                    <td>{{ $flow->id }}</td>
                    <td>{{ $flow->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $flow->type)) }} - {{ $flow->description ?? '' }}</td>
                    <td style="color: {{ $isInflow ? 'green' : 'red' }};">
                        {{ $isInflow ? 'ENTRÉE (+)' : 'SORTIE (-)' }}
                    </td>
                    <td class="text-end">{{ number_format($flow->amount, 2) }} {{ $flow->currency }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">Aucun transfert ou flux divers enregistré</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            @if($cloture->other_inflows_usd > 0 || $cloture->other_outflows_usd > 0)
                <tr>
                    <th colspan="4" class="text-end">TOTAL ENTRÉES TRANSFERTS USD</th>
                    <th class="text-end">{{ number_format($cloture->other_inflows_usd, 2) }} USD</th>
                </tr>
                <tr>
                    <th colspan="4" class="text-end">TOTAL SORTIES TRANSFERTS USD</th>
                    <th class="text-end">{{ number_format($cloture->other_outflows_usd, 2) }} USD</th>
                </tr>
            @endif
            @if($cloture->other_inflows_cdf > 0 || $cloture->other_outflows_cdf > 0)
                <tr>
                    <th colspan="4" class="text-end">TOTAL ENTRÉES TRANSFERTS CDF</th>
                    <th class="text-end">{{ number_format($cloture->other_inflows_cdf, 2) }} CDF</th>
                </tr>
                <tr>
                    <th colspan="4" class="text-end">TOTAL SORTIES TRANSFERTS CDF</th>
                    <th class="text-end">{{ number_format($cloture->other_outflows_cdf, 2) }} CDF</th>
                </tr>
            @endif
        </tfoot>
    </table>

    <div class="section-title">PREUVE COMPTABLE (Récapitulatif Global)</div>
    <table class="table">
        <thead>
            <tr>
                <th>Détails</th>
                <th class="text-end">USD</th>
                <th class="text-end">CDF</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Solde Logique Précédent</td>
                <td class="text-end">{{ number_format($cloture->previous_logical_usd, 2) }}</td>
                <td class="text-end">{{ number_format($cloture->previous_logical_cdf, 2) }}</td>
            </tr>
            <tr>
                <td>Total Entrées (Dépôts + Transferts In) (+)</td>
                <td class="text-end">{{ number_format($cloture->total_inflows_usd, 2) }}</td>
                <td class="text-end">{{ number_format($cloture->total_inflows_cdf, 2) }}</td>
            </tr>
            <tr>
                <td>Total Sorties (Retraits + Transferts Out) (-)</td>
                <td class="text-end">{{ number_format($cloture->total_outflows_usd, 2) }}</td>
                <td class="text-end">{{ number_format($cloture->total_outflows_cdf, 2) }}</td>
            </tr>
            <tr style="background-color: #f8f9fa; font-weight: bold;">
                <td>SOLDE EN CAISSE ACTUALISÉ</td>
                <td class="text-end">{{ number_format($cloture->logical_usd, 2) }} USD</td>
                <td class="text-end">{{ number_format($cloture->logical_cdf, 2) }} CDF</td>
            </tr>
        </tbody>
    </table>

    @if($cloture->note || $cloture->rejection_reason)
        <div class="section-title">DESCRIPTION DÉTAILLÉE / MOTIF</div>
        <table class="table">
            @if($cloture->note)
                <tr>
                    <th style="width: 30%;">Justification de l'agent</th>
                    <td>{{ $cloture->note }}</td>
                </tr>
            @endif
            @if($cloture->status === 'rejected')
                <tr>
                    <th style="width: 30%;">Motif du rejet</th>
                    <td>{{ $cloture->rejection_reason ?? '-' }}</td>
                </tr>
            @endif
        </table>
    @endif

</body>

</html>
