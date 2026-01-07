<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Historique Clôtures - PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
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

    @foreach ($cloture as $cl)
        <div class="header" style="padding-bottom: 5px;">
            <table style="width:100%;">
                <tr>
                    <td style="width: 15%;">
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.jpg'))) }}"
                            class="logo" alt="Logo">
                    </td>
                    <td style="width: 60%; text-align:center;">
                        <h2 style="margin: 0; font-size: 14px;">{{ strtoupper(config('app.name')) }}</h2>
                        <p style="margin: 0;">Adresse : {{ env('APP_ADRESS') }}</p>
                        <p style="margin: 0;">Tel : {{ env('APP_PHONE') }} – Email : {{ env('APP_EMAIL') }}</p>
                    </td>
                    <td style="width: 25%; text-align:right; font-size: 9px;">
                        <strong>Date :</strong> {{ \Carbon\Carbon::parse($cl->closing_date)->format('d/m/Y') }}<br>
                        <strong>Heure :</strong> {{ \Carbon\Carbon::parse($cl->created_at)->format('H:i') }}<br>
                        <strong>Agent :</strong><br>
                        {{ $cl->user->name }} {{ $cl->user->postnom }}
                    </td>
                </tr>
            </table>
            <hr style="margin: 10px 0; border-bottom: 2px solid #ed8d0f;">
            <h3 class="text-center" style="text-decoration: underline; margin-bottom: 2px;">FICHE DE CLÔTURE DE CAISSE</h3>
            <p class="text-center">
                Statut :
                @if($cl->status == 'validated')
                    <span class="badge badge-success">VALIDÉE</span>
                @elseif($cl->status == 'rejected')
                    <span class="badge badge-danger">REJETÉE</span>
                @else
                    EN ATTENTE
                @endif
                @if($cl->validated_at)
                    | du {{ $cl->validated_at->format('d/m/Y H:i') }}
                    par {{ $cl->validatedBy?->name }} {{ $cl->validatedBy?->postnom }}
                @endif
            </p>
        </div>

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
                    <td>{{ number_format($cl->logical_usd, 2) }}</td>
                    <td>{{ number_format($cl->physical_usd, 2) }}</td>
                    <td>{{ number_format($cl->gap_usd, 2) }}</td>
                </tr>
                <tr>
                    <td>CDF</td>
                    <td>{{ number_format($cl->logical_cdf, 2) }}</td>
                    <td>{{ number_format($cl->physical_cdf, 2) }}</td>
                    <td>{{ number_format($cl->gap_cdf, 2) }}</td>
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
                    @foreach ($cl->billetages->where('currency', 'USD') as $billet)
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
                    @foreach ($cl->billetages->where('currency', 'CDF') as $billet)
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
                    {{ $cl->user->name }} {{ $cl->user->postnom }}
                </td>
                <td style="width: 49%; text-align:right; font-size: 12px;">
                    <strong>Visa Responsable</strong><br><br><br>
                    @if($cl->validatedBy)
                        {{ $cl->validatedBy->name }} {{ $cl->validatedBy->postnom }}
                    @else
                        (Cette clôture a été rejetée)
                    @endif
                </td>
            </tr>
        </table>

        <div class="footer">
            Fiche générée le {{ now()->format('d/m/Y H:i') }} - {{ config('app.name') }}
        </div>

        <div class="page-break"></div>

        <div class="header" style="padding-bottom: 5px;">
            <table style="width:100%;">
                <tr>
                    <td style="width: 15%;">
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.jpg'))) }}"
                            class="logo" alt="Logo">
                    </td>
                    <td style="width: 60%; text-align:center;">
                        <h2 style="margin: 0; font-size: 14px;">{{ strtoupper(config('app.name')) }}</h2>
                        <p style="margin: 0;">Adresse : {{ env('APP_ADRESS') }}</p>
                        <p style="margin: 0;">Tel : {{ env('APP_PHONE') }} – Email : {{ env('APP_EMAIL') }}</p>
                    </td>
                    <td style="width: 25%; text-align:right; font-size: 9px;">
                        <strong>Date :</strong> {{ \Carbon\Carbon::parse($cl->closing_date)->format('d/m/Y') }}<br>
                        <strong>Heure :</strong> {{ \Carbon\Carbon::parse($cl->created_at)->format('H:i') }}<br>
                        <strong>Agent :</strong><br>
                        {{ $cl->user->name }} {{ $cl->user->postnom }}
                    </td>
                </tr>
            </table>
            <hr style="margin: 10px 0; border-bottom: 2px solid #ed8d0f;">
            <h3 class="text-center" style="text-decoration: underline; margin-bottom: 10px;">FEUILLE DE CAISSE N°
                {{ $cl->id }}</h3>
        </div>

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
                @forelse ($cl->deposits as $dep)
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
                    <th colspan="3" class="text-end">TOTAL DÉPÔTS USD</th>
                    <th class="text-end">{{ number_format($cl->total_deposits_usd, 2) }} USD</th>
                </tr>
                <tr>
                    <th colspan="3" class="text-end">TOTAL DÉPÔTS CDF</th>
                    <th class="text-end">{{ number_format($cl->total_deposits_cdf, 2) }} CDF</th>
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
                @forelse ($cl->withdrawals as $wit)
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
                    <th colspan="3" class="text-end">TOTAL RETRAITS USD</th>
                    <th class="text-end">{{ number_format($cl->total_withdrawals_usd, 2) }} USD</th>
                </tr>
                <tr>
                    <th colspan="3" class="text-end">TOTAL RETRAITS CDF</th>
                    <th class="text-end">{{ number_format($cl->total_withdrawals_cdf, 2) }} CDF</th>
                </tr>
            </tfoot>
        </table>

        <div class="section-title">PREUVE COMPTABLE</div>
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
                    <td class="text-end">{{ number_format($cl->previous_logical_usd, 2) }}</td>
                    <td class="text-end">{{ number_format($cl->previous_logical_cdf, 2) }}</td>
                </tr>
                <tr>
                    <td>Total Dépôts du Jour (+)</td>
                    <td class="text-end">{{ number_format($cl->total_deposits_usd, 2) }}</td>
                    <td class="text-end">{{ number_format($cl->total_deposits_cdf, 2) }}</td>
                </tr>
                <tr>
                    <td>Total Retraits du Jour (-)</td>
                    <td class="text-end">{{ number_format($cl->total_withdrawals_usd, 2) }}</td>
                    <td class="text-end">{{ number_format($cl->total_withdrawals_cdf, 2) }}</td>
                </tr>
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <td>SOLDE EN CAISSE ACTUALISÉ</td>
                    <td class="text-end">{{ number_format($cl->logical_usd, 2) }} USD</td>
                    <td class="text-end">{{ number_format($cl->logical_cdf, 2) }} CDF</td>
                </tr>
            </tbody>
        </table>

        @if($cl->note || $cl->rejection_reason)
            <div class="section-title">DESCRIPTION DÉTAILLÉE / MOTIF</div>
            <table class="table">
                @if($cl->note)
                    <tr>
                        <th style="width: 30%;">Justification de l'agent</th>
                        <td>{{ $cl->note }}</td>
                    </tr>
                @endif
                @if($cl->status === 'rejected')
                    <tr>
                        <th style="width: 30%;">Motif du rejet</th>
                        <td>{{ $cl->rejection_reason ?? '-' }}</td>
                    </tr>
                @endif
            </table>
        @endif

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>

</html>