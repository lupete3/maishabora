<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fiche de Plan de Remboursement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 5px;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .container {
            width: 100%;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 6px;
            background-color: #f9f9f9;
        }
        .info-row div {
            width: 48%;
        }
        .table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
        }
        .table td, .table th {
            border: 1px solid #000; padding: 5px; font-size: 12px;
        }
        th {
            background-color: #f1c206;
        }
        .logo {
            width: 80px;
        }
        td:first-child, th:first-child {
            text-align: center;
        }
        .totals {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .signature {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>

    @include('partials.pdf-header', ['reportTitle' => 'PLAN DE REMBOURSEMENT DE CRÉDIT'])

    <!-- Informations du membre et du crédit -->
    <table style="border: none; border-collapse: collapse; width: 100%;">
        <tr>
            <td style="border: none; padding: 0; text-align: left;">
                <strong>Code Membre :</strong> {{ $member->code }}<br>
                <strong>Nom Complet :</strong> {{ $member->name.' '.$member->postnom.' '.$member->prenom }}<br>
                <strong>Sexe :</strong> {{ $member->sexe }} <br>
                <strong>Téléphone :</strong> {{ $member->telephone }}<br>
                <strong>Email :</strong> {{ $member->email }}<br>
                <strong>Adresse :</strong> {{ $member->adresse ?? 'N/A' }}<br>
            </td>
            <td style="border: none; padding: 0; text-align: right">
                <strong>Montant du prêt :</strong> {{ number_format($credit->amount, 2) }} {{ $credit->currency }}<br>
                <strong>Taux d'intérêt :</strong> {{ $credit->interest_rate }}%<br>
                <strong>Frais du dossier :</strong> {{ number_format(($credit->amount * $credit->frais_credit) / 100, 2) }} {{ $credit->currency }}<br>
                <strong>Date d'octroit :</strong> {{ \Carbon\Carbon::parse($credit->created_at)->format('d/m/Y H:i') }} <br>
                <strong>Date de début :</strong> {{ \Carbon\Carbon::parse($credit->start_date)->format('d/m/Y') }} <br>
                <strong>Date de fin :</strong> {{ \Carbon\Carbon::parse($credit->due_date)->format('d/m/Y') }} <br>
                <strong>Durée :</strong> {{ number_format($credit->installments, 0) }}
                @if ($credit->repayment_type == 'daily')
                    jours
                @elseif ($credit->repayment_type == 'weekly')
                    semaines
                @elseif ($credit->repayment_type == 'monthly')
                    mois
                @endif <br>
            </td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Date prévue</th>
                <th>Capital Début</th>
                <th>Capital Remboursé</th>
                <th>Intérêt</th>
                <th>Pénalité</th>
                <th>Mensualité Totale</th>
                <th>Capital Restant</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($repayments as $index => $r)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($r['due_date'])->format('d/m/Y') }}</td>
                    <td>{{ number_format($r['opening_capital'], 2) }}</td>
                    <td>{{ number_format($r['capital_repaid'], 2) }}</td>
                    <td>{{ number_format($r['interest'], 2) }}</td>
                    <td>{{ number_format($r['penalty'], 2) }}</td>
                    <td>{{ number_format($r['due'], 2) }}</td>
                    <td>{{ number_format($r['remaining_capital'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="totals">
                <td colspan="2">Totaux</td>
                <td>{{ number_format($totalCapital, 2) }}</td>
                <td></td>
                <td>{{ number_format($totalInterest, 2) }}</td>
                <td>{{ number_format($totalPenalty, 2) }}</td>
                <td>{{ number_format($totalDue, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    {{-- <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date prévue</th>
                <th>Capital Début</th>
                <th>Intérêt</th>
                <th>Capital Remboursé</th>
                <th>Mensualité Totale</th>
                <th>Capital Restant</th>
            </tr>
        </thead>
        <tbody>
            @php
                $remainingCapital = $credit->amount;
                $capitalPart = round($credit->amount / $credit->installments, 2);

                $totalCapital = 0;
                $totalInterest = 0;
                $totalDue = 0;
            @endphp

            @foreach ($repayments as $index => $r)
                @php
                    // Capital début de période
                    $openingCapital = $remainingCapital;

                    // Intérêt calculé sur le capital restant
                    $interest = round($openingCapital * ($credit->interest_rate / 100), 2);

                    // Capital à rembourser
                    $capitalRepaid = $capitalPart;

                    // Mensualité totale
                    $due = round($capitalRepaid + $interest, 2);

                    // Capital restant après paiement
                    $remainingCapital = round($openingCapital - $capitalRepaid, 2);

                    // Totaux
                    $totalCapital += $capitalRepaid;
                    $totalInterest += $interest;
                    $totalDue += $due;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ \Carbon\Carbon::parse($r->due_date)->format('d/m/Y') }}</td>
                    <td>{{ number_format($openingCapital, 2) }}</td>
                    <td>{{ number_format($interest, 2) }}</td>
                    <td>{{ number_format($capitalRepaid, 2) }}</td>
                    <td>{{ number_format($due, 2) }}</td>
                    <td>{{ number_format($remainingCapital, 2) }}</td>
                </tr>
            @endforeach

            <tr class="totals">
                <td colspan="3"><strong>Totaux</strong></td>
                <td>{{ number_format($totalInterest, 2) }}</td>
                <td>{{ number_format($totalCapital, 2) }}</td>
                <td>{{ number_format($totalDue, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table> --}}


    <!-- Calendrier des remboursements -->
    {{-- <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date prévue</th>
                <th>Capital</th>
                <th>Intérêt</th>
                <th>Pénalité</th>
                <th>Montant total</th>
                <th>Solde restant</th>
            </tr>
        </thead>
        <tbody>
            @php
                $remainingCapital = $credit->amount;
                $capitalPart = round($credit->amount / $credit->installments, 4);

                $totalCapital = 0;
                $totalInterest = 0;
                $totalPenalty = 0;
                $totalDue = 0;
            @endphp

            @foreach ($repayments as $index => $r)
                @php
                    $interest = round($remainingCapital * ($credit->interest_rate / 100), 4);
                    $penalty = $r->penalty ?? 0;
                    $due = round($capitalPart + $interest + $penalty, 4);
                    $balance = $remainingCapital - $capitalPart;

                    $totalCapital += $capitalPart;
                    $totalInterest += $interest;
                    $totalPenalty += $penalty;
                    $totalDue += $due;

                    $remainingCapital = $balance;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ \Carbon\Carbon::parse($r->due_date)->format('d/m/Y') }}</td>
                    <td>{{ number_format($capitalPart, 2) }}</td>
                    <td>{{ number_format($interest, 2) }}</td>
                    <td>{{ number_format($penalty, 2) }}</td>
                    <td>{{ number_format($due, 2) }}</td>
                    <td>{{ number_format($balance, 2) }}</td>
                </tr>
            @endforeach
            <tr class="totals">
                <td colspan="2">Totaux</td>
                <td>{{ number_format($totalCapital, 2) }}</td>
                <td>{{ number_format($totalInterest, 2) }}</td>
                <td>{{ number_format($totalPenalty, 2) }}</td>
                <td>{{ number_format($totalDue, 2) }}</td>
                <td></td>
            </tr>

        </tbody>
    </table> --}}

    <!-- Signatures -->
    <table style="border: none; border-collapse: collapse; width: 100%; margin-top:40px">
        <tr>
            <td style="border: none; padding: 0; text-align: left;">
                Signature Membre<br><br><br><br>
                <strong>{{ $member->name.' '.$member->postnom }}</strong>
            </td>
            <td style="border: none; padding: 0; text-align:right">
                Signature Agent<br><br><br><br>
                <strong>{{ $agent->name.' '.$agent->postnom }}</strong>
            </td>
        </tr>
    </table>


    <div style="text-align: center; margin-top: 50px; font-size: 10px; color: #888; border-top: 1px solid #eee; padding-top: 10px;">
        Ce document est généré par le système {{ $company->name ?? config('app.name') }} le {{ now()->format('d/m/Y H:i') }}.
    </div>
</body>
</html>
