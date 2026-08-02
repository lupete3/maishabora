<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Échéances en Retard - Maisha Bora</title>
    <style>
        @page {
            margin: 25px;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #333;
            line-height: 1.3;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .fw-bold { font-weight: bold; }
        
        .section-title {
            font-size: 11px;
            font-weight: bold;
            padding: 6px 10px;
            margin-top: 15px;
            margin-bottom: 8px;
            border-radius: 3px;
            background-color: #fce8e6;
            color: #a90000;
            border-left: 4px solid #d9534f;
        }
        
        .totals-box {
            margin-bottom: 10px;
            padding: 8px;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
        }
        .totals-badge {
            display: inline-block;
            padding: 3px 8px;
            margin-right: 10px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 3px;
            background-color: #d9534f;
            color: white;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .table th, .table td {
            border: 1px solid #dee2e6;
            padding: 5px 4px;
            vertical-align: middle;
        }
        .table th {
            background-color: #f1c206;
            color: #000;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }
        .table tbody tr:nth-of-type(even) {
            background-color: #f9f9f9;
        }
        
        .currency-badge {
            font-weight: bold;
            color: #4b6584;
        }
        .text-danger {
            color: #d9534f;
            font-weight: bold;
        }
        .text-primary {
            color: #1a73e8;
        }
        .text-muted {
            color: #777;
        }
        
        .balance-col {
            font-size: 8px;
            background-color: #fdfdfd;
        }
        
        .footer {
            position: fixed;
            bottom: -10px;
            left: 0px;
            right: 0px;
            height: 20px;
            text-align: center;
            font-size: 8px;
            color: #777;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    @include('partials.pdf-header', ['reportTitle' => 'SUIVI DES ÉCHÉANCES EN RETARD (PAIEMENTS DÉPASSÉS)'])

    @if($overdueTotals->isNotEmpty())
        <div class="totals-box" style="margin-top: 15px; padding: 10px; border: 1px solid #ddd; background-color: #f9f9f9;">
            <div style="font-weight: bold; margin-bottom: 5px; color: #333;">Détail des Totaux en Retard :</div>
            @foreach($overdueTotals as $currency => $amounts)
                <div style="margin-bottom: 8px; padding-bottom: 5px; border-bottom: 1px dashed #ccc;">
                    <span style="font-weight: bold; color: #d9534f;">{{ $currency }} :</span>
                    <span style="margin-left: 15px;">Principal : <strong>{{ number_format($amounts['capital'], 2, '.', ' ') }}</strong></span>
                    <span style="margin-left: 15px;">Intérêt : <strong>{{ number_format($amounts['interest'], 2, '.', ' ') }}</strong></span>
                    <span style="margin-left: 15px;">Pénalité : <strong>{{ number_format($amounts['penalty'], 2, '.', ' ') }}</strong></span>
                    <span style="margin-left: 15px; background-color: #d9534f; color: #fff; padding: 2px 6px; font-weight: bold;">
                        Total Dû : {{ number_format($amounts['total'], 2, '.', ' ') }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th style="width: 8%;">Code Membre</th>
                <th style="width: 20%;">Nom & Postnom Client</th>
                <th style="width: 10%;">Date Échéance</th>
                <th style="width: 8%;">Retard</th>
                <th style="width: 12%;">Montant Dû</th>
                <th style="width: 6%;">Devise</th>
                <th style="width: 9%;" class="text-center">Courant USD</th>
                <th style="width: 9%;" class="text-center">Carnet USD</th>
                <th style="width: 9%;" class="text-center">Courant CDF</th>
                <th style="width: 9%;" class="text-center">Carnet CDF</th>
            </tr>
        </thead>
        <tbody>
            @forelse($overdueCredits as $r)
                @php
                    $daysLate = \Carbon\Carbon::parse($r->due_date)->diffInDays(now());
                @endphp
                <tr>
                    <td class="fw-bold text-primary">{{ $r->credit->user->code }}</td>
                    <td>{{ $r->credit->user->name }} {{ $r->credit->user->postnom }}</td>
                    <td class="text-center">{{ $r->due_date->format('d/m/Y') }}</td>
                    <td class="text-center text-danger fw-bold">{{ number_format($daysLate, 0) }} j</td>
                    <td class="text-end text-danger fw-bold">{{ number_format(($r->total_due - $r->paid_amount), 2, '.', ' ') }}</td>
                    <td class="text-center currency-badge">{{ $r->credit->currency }}</td>
                    
                    <!-- Soldes USD -->
                    @php
                        $usdCurrent = (float) ($r->credit->user->accounts->where('currency', 'USD')->where('type', 'current')->first()?->balance ?? 0);
                        $usdSavings = (float) ($r->credit->user->accounts->where('currency', 'USD')->where('type', 'savings')->first()?->balance ?? 0);
                    @endphp
                    <td class="text-end balance-col">{{ $usdCurrent > 0 ? number_format($usdCurrent, 2, '.', ' ') : '-' }}</td>
                    <td class="text-end balance-col">{{ $usdSavings > 0 ? number_format($usdSavings, 2, '.', ' ') : '-' }}</td>
                    
                    <!-- Soldes CDF -->
                    @php
                        $cdfCurrent = (float) ($r->credit->user->accounts->where('currency', 'CDF')->where('type', 'current')->first()?->balance ?? 0);
                        $cdfSavings = (float) ($r->credit->user->accounts->where('currency', 'CDF')->where('type', 'savings')->first()?->balance ?? 0);
                    @endphp
                    <td class="text-end balance-col">{{ $cdfCurrent > 0 ? number_format($cdfCurrent, 0, '.', ' ') : '-' }}</td>
                    <td class="text-end balance-col">{{ $cdfSavings > 0 ? number_format($cdfSavings, 0, '.', ' ') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted">Aucune échéance en retard répertoriée.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Rapport généré le {{ now()->format('d/m/Y H:i') }} par 
        {{ Auth::user()->name ?? 'Système' }} {{ Auth::user()->postnom ?? '' }} – 
        {{ $company->name ?? config('app.name') }}
    </div>

</body>
</html>
