<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Rapport des Remboursements</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 5px;
            color: #000;
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

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table td,
        .table th {
            border: 1px solid #000;
            padding: 4px;
            font-size: 9px;
        }

        th {
            background-color: #f1c206;
        }

        .logo {
            width: 80px;
        }
    </style>
</head>

<body>

    @include('partials.pdf-header', ['reportTitle' => 'RAPPORT DES REMBOURSEMENTS (' . ucfirst($reportType) . ')'])
        <p class="text-center">Devise : <strong>{{ strtoupper($currency) }}</strong></p>

        <table style="width:100%; margin-top:10px; border:1px solid #000; border-collapse: collapse;">
    <thead>
        <tr>
            <th style="border:1px solid #000; padding:4px;">Devise</th>
            <th style="border:1px solid #000; padding:4px;">Total Remboursé</th>
            <th style="border:1px solid #000; padding:4px;">Total Pénalité</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($totals as $currency => $values)
            <tr>
                <td style="border:1px solid #000; text-align:center; font-weight:bold;">
                    {{ $currency }}
                </td>
                <td style="border:1px solid #000; text-align:center;">
                    {{ number_format($values['total_paid'], 2, ',', ' ') }}
                </td>
                <td style="border:1px solid #000; text-align:center;">
                    {{ number_format($values['total_penality'], 2, ',', ' ') }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

    </div>

    {{-- ✅ TABLEAU DES REMBOURSEMENTS --}}
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Code</th>
                <th>Membre</th>
                <th>Montant Remboursé</th>
                <th>Pénalité</th>
                <th>Devise</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $repayment)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($repayment->paid_date)->format('d/m/Y') }}</td>
                    <td>
                        @if ($repayment->credit && $repayment->credit->user)
                            {{ $repayment->credit->user->code }}
                        @else
                            <span class="badge bg-label-secondary">N/A</span>
                        @endif
                    </td>
                    <td>
                        @if ($repayment->credit && $repayment->credit->user)
                            {{ $repayment->credit->user->name . ' ' . $repayment->credit->user->postnom . ' ' . $repayment->credit->user->prenom }}
                        @else
                            <span class="badge bg-label-secondary">N/A</span>
                        @endif
                    </td>
                    <td>{{ number_format($repayment->total_due, 2) }}</td>
                    <td>{{ number_format($repayment->penalty, 2) }}</td>
                    <td>
                        @if ($repayment->credit)
                            <span class="badge bg-label-info">{{ $repayment->credit->currency }}</span>
                        @else
                            <span class="badge bg-label-secondary">N/A</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">Aucune donnée disponible.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ✅ PIED DE PAGE --}}
    <div class="footer">
        Rapport généré le {{ now()->format('d/m/Y H:i') }} par
        {{ Auth::user()->name }} {{ Auth::user()->postnom }} – {{ $company->name ?? config('app.name') }}
    </div>

</body>

</html>
