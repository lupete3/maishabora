<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Performance des Agents</title>
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

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table td,
        .table th {
            border: 1px solid #000;
            padding: 3px;
            font-size: 8px;
        }

        th {
            background-color: #f1c206;
        }

        .section-title {
            margin-top: 22px;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }

        .filters p {
            margin: 2px 0;
        }

        .nowrap {
            white-space: nowrap;
        }

        .highlight {
            font-weight: bold;
            background-color: #fff6cc;
        }

        .rank-1 {
            background-color: #f1c206;
            font-weight: bold;
        }

        .rank-2 {
            background-color: #c0c0c0;
            font-weight: bold;
        }

        .rank-3 {
            background-color: #cd7f32;
            color: #fff;
            font-weight: bold;
        }

        .zero {
            color: #999;
        }

        tfoot tr {
            font-weight: bold;
            background-color: #eee;
        }
    </style>
</head>

<body>
    @php
        // Format français, identique à l'écran : 53 602 000,00
        $fmt = fn ($n, $d = 2) => number_format((float) $n, $d, ',', ' ');
        $typeLabel = fn ($t) => $types[$t] ?? ucfirst(str_replace('_', ' ', $t));
    @endphp

    @include('partials.pdf-header', ['reportTitle' => 'RAPPORT DE PERFORMANCE DES AGENTS'])

    <div class="filters">
        <p>Période : <strong>{{ ucfirst($periodLabel) }}</strong>
            (du {{ $startDate->format('d/m/Y') }} au {{ $endDate->format('d/m/Y') }})</p>
        <p>Types de transaction :
            <strong>{{ collect(array_keys($typeSummary))->map($typeLabel)->join(', ') }}</strong></p>
        <p>Critère de classement : <strong>{{ $criterion }}</strong></p>
        <p>Agents comparés : <strong>{{ count($results) }}</strong></p>
    </div>

    {{-- 1. CLASSEMENT --}}
    <div class="section-title">CLASSEMENT GÉNÉRAL</div>
    <table class="table" cellspacing="0" cellpadding="4">
        <thead>
            <tr>
                <th class="text-center" style="width: 6%;">Rang</th>
                <th>Agent</th>
                <th class="text-end">Nb opérations</th>
                <th class="text-end">Montant CDF</th>
                <th class="text-end">Montant USD</th>
                <th class="text-end">Part ({{ $criterion }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($results as $row)
                <tr>
                    <td class="text-center {{ $row['rank'] <= 3 ? 'rank-' . $row['rank'] : '' }}">{{ $row['rank'] }}</td>
                    <td>{{ $row['name'] }} (#{{ $row['id'] }})</td>
                    <td class="text-end nowrap {{ $sortBy === 'count' ? 'highlight' : '' }}">{{ $fmt($row['count'], 0) }}</td>
                    <td class="text-end nowrap {{ $sortBy === 'cdf' ? 'highlight' : '' }}">{{ $fmt($row['cdf']) }}</td>
                    <td class="text-end nowrap {{ $sortBy === 'usd' ? 'highlight' : '' }}">{{ $fmt($row['usd']) }}</td>
                    <td class="text-end nowrap">{{ $fmt($row['share'], 1) }} %</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">TOTAL</td>
                <td class="text-end nowrap">{{ $fmt($totals['count'], 0) }}</td>
                <td class="text-end nowrap">{{ $fmt($totals['cdf']) }}</td>
                <td class="text-end nowrap">{{ $fmt($totals['usd']) }}</td>
                <td class="text-end">100 %</td>
            </tr>
        </tfoot>
    </table>

    {{-- 2. OPÉRATIONS PAR AGENT ET PAR TYPE --}}
    <div class="section-title">NOMBRE D'OPÉRATIONS PAR AGENT ET PAR TYPE</div>
    <table class="table" cellspacing="0" cellpadding="4">
        <thead>
            <tr>
                <th class="text-center" style="width: 6%;">Rang</th>
                <th>Agent</th>
                @foreach (array_keys($typeSummary) as $type)
                    <th class="text-end">{{ $typeLabel($type) }}</th>
                @endforeach
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($results as $row)
                <tr>
                    <td class="text-center">{{ $row['rank'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    @foreach (array_keys($typeSummary) as $type)
                        @php $n = $row['by_type'][$type]['count'] ?? 0; @endphp
                        <td class="text-end nowrap {{ $n ? '' : 'zero' }}">{{ $fmt($n, 0) }}</td>
                    @endforeach
                    <td class="text-end nowrap"><strong>{{ $fmt($row['count'], 0) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">TOTAL</td>
                @foreach ($typeSummary as $v)
                    <td class="text-end nowrap">{{ $fmt($v['count'], 0) }}</td>
                @endforeach
                <td class="text-end nowrap">{{ $fmt($totals['count'], 0) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- 3. RÉCAP PAR TYPE --}}
    <div class="section-title">RÉCAPITULATIF PAR TYPE DE TRANSACTION</div>
    <table class="table" cellspacing="0" cellpadding="4">
        <thead>
            <tr>
                <th>Type de transaction</th>
                <th class="text-end">Nb opérations</th>
                <th class="text-end">Montant CDF</th>
                <th class="text-end">Montant USD</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($typeSummary as $type => $v)
                <tr class="{{ $v['count'] ? '' : 'zero' }}">
                    <td>{{ $typeLabel($type) }}</td>
                    <td class="text-end nowrap">{{ $fmt($v['count'], 0) }}</td>
                    <td class="text-end nowrap">{{ $fmt($v['cdf']) }}</td>
                    <td class="text-end nowrap">{{ $fmt($v['usd']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>TOTAL</td>
                <td class="text-end nowrap">{{ $fmt($totals['count'], 0) }}</td>
                <td class="text-end nowrap">{{ $fmt($totals['cdf']) }}</td>
                <td class="text-end nowrap">{{ $fmt($totals['usd']) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Rapport généré le {{ now()->format('d/m/Y H:i') }} par {{ Auth::user()->name }} {{ Auth::user()->postnom }} -
        {{ $company->name ?? config('app.name') }}
    </div>

</body>

</html>
