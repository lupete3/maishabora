<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Situation détaillée de crédit client</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 5px;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .table {
            width: 100%; border-collapse: collapse; margin-top: 15px;
        }
        .table td, .table th {
            border: 1px solid #000; padding: 4px 6px; font-size: 11px;
        }
        th {
            background-color: #f1c206;
            font-weight: bold;
            text-align: left;
        }
        .totals {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-success {
            color: #2e7d32;
        }
        .text-danger {
            color: #c62828;
        }
        .text-warning {
            color: #ef6c00;
        }
    </style>
</head>
<body>

    @include('partials.pdf-header', ['reportTitle' => 'SITUATION DÉTAILLÉE DE CRÉDIT CLIENT'])

    <!-- Informations du membre et du crédit -->
    <table style="border: none; border-collapse: collapse; width: 100%; margin-bottom: 20px;">
        <tr>
            <td style="border: none; padding: 0; text-align: left; width: 50%; vertical-align: top; font-size: 11px; line-height: 1.5;">
                <h3 style="margin-top: 0; margin-bottom: 8px; border-bottom: 2px solid #f1c206; padding-bottom: 3px; display: inline-block;">INFORMATIONS DU MEMBRE</h3><br>
                <strong>Code Membre :</strong> {{ $member->code }}<br>
                <strong>Nom Complet :</strong> {{ $member->name.' '.$member->postnom.' '.$member->prenom }}<br>
                <strong>Sexe :</strong> {{ $member->sexe }} <br>
                <strong>Téléphone :</strong> {{ $member->telephone }}<br>
                <strong>Email :</strong> {{ $member->email }}<br>
                <strong>Adresse :</strong> {{ $member->adresse ?? 'N/A' }}<br>
            </td>
            <td style="border: none; padding: 0; text-align: left; width: 50%; vertical-align: top; font-size: 11px; line-height: 1.5; padding-left: 20px;">
                <h3 style="margin-top: 0; margin-bottom: 8px; border-bottom: 2px solid #f1c206; padding-bottom: 3px; display: inline-block;">DÉTAILS DU CRÉDIT</h3><br>
                <strong>Crédit ID :</strong> #{{ $credit->id }}<br>
                <strong>Montant Octroyé :</strong> {{ number_format($credit->amount, 2) }} {{ $credit->currency }}<br>
                <strong>Taux d'intérêt :</strong> {{ $credit->interest_rate }}% ({{ ucfirst($credit->credit_type) }})<br>
                <strong>Date d'octroi :</strong> {{ \Carbon\Carbon::parse($credit->created_at)->format('d/m/Y H:i') }} <br>
                <strong>Date de début :</strong> {{ \Carbon\Carbon::parse($credit->start_date)->format('d/m/Y') }} <br>
                <strong>Date de fin :</strong> {{ \Carbon\Carbon::parse($credit->due_date)->format('d/m/Y') }} <br>
                <strong>Type d'échéance :</strong> {{ number_format($credit->installments, 0) }} × {{ $credit->repayment_type }} <br>
            </td>
        </tr>
    </table>

    <div style="background-color: #f9f9f9; border: 1px solid #ddd; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
        <table style="border: none; border-collapse: collapse; width: 100%;">
            <tr>
                <td style="border: none; padding: 0; font-size: 12px; width: 33%;">
                    <strong>Total payé :</strong> <span class="text-success">{{ number_format($totalPaid, 2) }} {{ $credit->currency }}</span>
                </td>
                <td style="border: none; padding: 0; font-size: 12px; width: 33%; text-align: center;">
                    <strong>Total pénalités :</strong> <span class="text-warning">{{ number_format($totalPenaltyCumulative, 2) }} {{ $credit->currency }}</span>
                </td>
                <td style="border: none; padding: 0; font-size: 12px; width: 33%; text-align: right;">
                    <strong>Reste à payer :</strong> <span class="text-danger" style="font-weight: bold;">{{ number_format($totalRemaining, 2) }} {{ $credit->currency }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 12%;">Échéance</th>
                <th style="width: 23%;">Attendu (Ventilation)</th>
                <th style="width: 10%;" class="text-right">Pénalité</th>
                <th style="width: 12%;" class="text-right">Total dû</th>
                <th style="width: 23%;">Déjà payé (Ventilation)</th>
                <th style="width: 12%;" class="text-right text-danger">Reste à payer</th>
                <th style="width: 8%;" class="text-center">Statut</th>
                <th style="width: 7%;" class="text-center">Retard</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($repayments as $index => $r)
                @php
                    $rowStyle = '';
                    if ($r['is_paid']) {
                        $rowStyle = 'background-color: #e8f5e9;'; // light green
                    } elseif ($r['remaining'] > 0 && $r['paid_amount'] > 0) {
                        $rowStyle = 'background-color: #fff9c4;'; // light yellow
                    } elseif (\Carbon\Carbon::parse($r['due_date'])->isPast()) {
                        $rowStyle = 'background-color: #ffebee;'; // light red
                    }
                @endphp
                <tr style="{{ $rowStyle }}">
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($r['due_date'])->format('d/m/Y') }}</td>
                    <td>
                        Cap : {{ number_format($r['principal_amount'], 2) }}<br>
                        Int : {{ number_format($r['interest_amount'], 2) }}
                    </td>
                    <td class="text-right">{{ number_format($r['penalty'], 2) }}</td>
                    <td class="text-right fw-semibold">{{ number_format($r['total_due'], 2) }}</td>
                    <td>
                        @if ($r['paid_amount'] > 0)
                            Cap : {{ number_format($r['paid_principal'], 2) }}<br>
                            Int : {{ number_format($r['paid_interest'], 2) }}<br>
                            Pén : {{ number_format($r['paid_penalty'], 2) }}
                        @else
                            <span style="color: #777; font-style: italic;">Aucun</span>
                        @endif
                    </td>
                    <td class="text-right text-danger fw-semibold">{{ number_format($r['remaining'], 2) }}</td>
                    <td class="text-center">
                        @if ($r['is_paid'])
                            <span style="color: green; font-weight: bold;">Payé</span>
                        @elseif ($r['paid_amount'] > 0 && $r['remaining'] > 0)
                            <span style="color: orange; font-weight: bold;">Partiel</span>
                        @else
                            <span style="color: red; font-weight: bold;">Non payé</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if ($r['days_late'] > 0)
                            <span style="color: red; font-weight: bold;">{{ $r['days_late'] }}j</span>
                        @else
                            0
                        @endif
                    </td>
                </tr>
            @endforeach
            <tr class="totals">
                <td colspan="2" class="text-center">Totaux</td>
                <td>
                    Cap : {{ number_format($totalPrincipalExpected, 2) }}<br>
                    Int : {{ number_format($totalInterestExpected, 2) }}
                </td>
                <td class="text-right">{{ number_format($totalPenaltyCumulative, 2) }}</td>
                <td class="text-right">{{ number_format($totalDueCumulative, 2) }}</td>
                <td>
                    Cap : {{ number_format($totalPrincipalPaid, 2) }}<br>
                    Int : {{ number_format($totalInterestPaid, 2) }}<br>
                    Pén : {{ number_format($totalPenaltyPaid, 2) }}
                </td>
                <td class="text-right text-danger">{{ number_format($totalRemaining, 2) }}</td>
                <td colspan="2" class="text-center">
                    Payé : {{ number_format($totalPaid, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Signatures -->
    <table style="border: none; border-collapse: collapse; width: 100%; margin-top: 35px;">
        <tr>
            <td style="border: none; padding: 0; text-align: left; width: 50%;">
                <strong>Signature du Membre</strong><br>
                <span style="font-size: 10px; color: #777;">(précédée de la mention "Lu et approuvé")</span><br><br><br><br>
                <strong>{{ $member->name.' '.$member->postnom }}</strong>
            </td>
            <td style="border: none; padding: 0; text-align: right; width: 50%;">
                <strong>Pour la Direction / Agent</strong><br>
                <span style="font-size: 10px; color: #777;">Fait le {{ now()->format('d/m/Y') }}</span><br><br><br><br>
                <strong>{{ $agent->name.' '.$agent->postnom }}</strong>
            </td>
        </tr>
    </table>

    <div style="text-align: center; margin-top: 40px; font-size: 9px; color: #777; border-top: 1px solid #eee; padding-top: 10px;">
        Ce relevé officiel de situation est généré par le système {{ $company->name ?? config('app.name') }} le {{ now()->format('d/m/Y à H:i') }}.
    </div>
</body>
</html>
