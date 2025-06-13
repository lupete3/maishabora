<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fiche de Plan de Remboursement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: right;
        }
        th {
            background-color: #eaeaea;
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

    <div class="header">
        {{-- <img src="{{ asset('assets/img/logo.jpg') }}" width="80px" alt="logo" class="img-center"> --}}
        <h2>PLAN DE REMBOURSEMENT DE CRÉDIT</h2>
        <p><strong>{{ config('app.name') }}</strong></p>
        <p>Date d'impression : {{ now()->format('d/m/Y H:m') }}</p>
    </div>

    <!-- Informations du membre et du crédit -->
    <table style="border: none; border-collapse: collapse; width: 100%;">
        <tr>
            <td style="border: none; padding: 0; text-align: left;">
                <strong>Code Membre :</strong> {{ $member->code }}<br>
                <strong>Nom Complet :</strong> {{ $member->name.' '.$member->postnom.' '.$member->prenom }}<br>
                <strong>Email :</strong> {{ $member->email }}
            </td>
            <td style="border: none; padding: 0;">
                <strong>Montant du prêt :</strong> {{ number_format($credit->amount, 2) }} {{ $credit->currency }}<br>
                <strong>Taux d'intérêt :</strong> {{ $credit->interest_rate }}%<br>
                <strong>Type de remboursement :</strong> Mensuel<br>
                <strong>Date de début :</strong> {{ \Carbon\Carbon::parse($credit->start_date)->format('d/m/Y') }}
            </td>
        </tr>
    </table>

    <!-- Calendrier des remboursements -->
    <table>
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
                $balance = $credit->amount;
                $totalCapital = 0;
                $totalInterest = 0;
                $totalPenalty = 0;
                $totalDue = 0;
            @endphp

            @foreach ($repayments as $index => $r)
                @php
                    $interest = round($credit->amount * ($credit->interest_rate / 100) / $credit->installments, 4);
                    $capital = round($credit->amount / $credit->installments, 4);
                    $penalty = $r->penalty;
                    $due = round($capital + $interest + $penalty, 4);
                    $balance -= $capital;

                    $totalCapital += $capital;
                    $totalInterest += $interest;
                    $totalPenalty += $penalty;
                    $totalDue += $due;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ \Carbon\Carbon::parse($r->due_date)->format('d/m/Y') }}</td>
                    <td>{{ number_format($capital, 2) }}</td>
                    <td>{{ number_format($interest, 2) }}</td>
                    <td>{{ number_format($penalty, 2) }}</td>
                    <td>{{ number_format($due, 2) }}</td>
                    <td>{{ number_format($balance, 2) }}</td>
                </tr>
            @endforeach

            <!-- Totaux -->
            <tr class="totals">
                <td colspan="2">Totaux</td>
                <td>{{ number_format($totalCapital, 2) }}</td>
                <td>{{ number_format($totalInterest, 2) }}</td>
                <td>{{ number_format($totalPenalty, 2) }}</td>
                <td>{{ number_format($totalDue, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- Signatures -->
    <table style="border: none; border-collapse: collapse; width: 100%;">
        <tr>
            <td style="border: none; padding: 0; text-align: left;">
                Signature Membre<br><br><br><br>
                <strong>{{ $member->name.' '.$member->postnom }}</strong>
            </td>
            <td style="border: none; padding: 0;">
                Signature Agent<br><br><br><br>
                <strong>{{ $agent->name.' '.$agent->name }}</strong>
            </td>
        </tr>
    </table>


</body>
</html>
