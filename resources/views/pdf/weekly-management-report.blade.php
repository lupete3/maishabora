<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport hebdomadaire global</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #222;
            margin: 24px;
            line-height: 1.35;
        }
        h4 {
            color: #1a5276;
            margin: 16px 0 6px;
            border-bottom: 1px solid #d8dee4;
            padding-bottom: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 7px 0 12px;
        }
        th, td {
            border: 1px solid #b8c0cc;
            padding: 5px;
            vertical-align: top;
        }
        th {
            background: #eaf2f8;
            color: #154360;
            font-weight: bold;
            text-align: center;
        }
        td.number {
            text-align: right;
            white-space: nowrap;
        }
        .intro {
            text-align: justify;
            margin: 10px 0 14px;
        }
        .comment {
            background: #f8f9fa;
            border-left: 3px solid #ed8d0f;
            padding: 7px 9px;
            margin: 4px 0 10px;
            text-align: justify;
        }
        .total-row td {
            background: #f3f6f9;
            font-weight: bold;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
@php
    $money = fn ($value, $currency) => number_format((float) $value, $currency === 'CDF' ? 0 : 2, ',', ' ') . ' ' . $currency;
    $variation = function ($value) {
        if ($value === null) {
            return 'N/A';
        }

        return ($value > 0 ? '+' : '') . number_format((float) $value, 1, ',', ' ') . ' %';
    };
    $current = $report['current'];
    $previous = $report['previous'];
    $vars = $report['variations'];
@endphp

@include('partials.pdf-header', [
    'reportTitle' => "RAPPORT DE GESTION HEBDOMADAIRE",
    'metadata' => '<strong>Période :</strong><br>' . e($report['period']['label']) . '<br><strong>Comparaison :</strong><br>' . e($report['comparison_period']['label'])
])

<p class="intro">
    Le présent rapport couvre la période {{ $report['period']['label'] }} en comparaison avec
    {{ $report['comparison_period']['label'] }}. Il fournit une vue d'ensemble des clients enregistrés,
    des carnets vendus, des mouvements de dépôts et retraits, des crédits, des remboursements,
    des commissions et des charges.
</p>

<h4>1. Effectif nouveaux clients</h4>
<table>
    <thead>
        <tr><th>Intervalle de temps</th><th>Hommes</th><th>Femmes</th><th>Total</th><th>Objectif en %</th></tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $report['comparison_period']['label'] }}</td>
            <td class="number">{{ $previous['new_clients']['men'] }}</td>
            <td class="number">{{ $previous['new_clients']['women'] }}</td>
            <td class="number">{{ $previous['new_clients']['total'] }}</td>
            <td class="number">{{ number_format($previous['new_clients']['target_rate'], 1, ',', ' ') }} %</td>
        </tr>
        <tr>
            <td>{{ $report['period']['label'] }}</td>
            <td class="number">{{ $current['new_clients']['men'] }}</td>
            <td class="number">{{ $current['new_clients']['women'] }}</td>
            <td class="number">{{ $current['new_clients']['total'] }}</td>
            <td class="number">{{ number_format($current['new_clients']['target_rate'], 1, ',', ' ') }} %</td>
        </tr>
        <tr class="total-row">
            <td>Variation en pourcentage</td>
            <td class="number">{{ $variation($vars['new_clients_men']) }}</td>
            <td class="number">{{ $variation($vars['new_clients_women']) }}</td>
            <td class="number">{{ $variation($vars['new_clients_total']) }}</td>
            <td></td>
        </tr>
    </tbody>
</table>
<div class="comment">
    Objectif calculé sur {{ $report['period']['business_days'] }} jours ouvrables à raison de 5 nouveaux clients par jour.
</div>

<h4>2. Ventes des carnets</h4>
<table>
    <thead>
        <tr><th>Intervalle de temps</th><th>Carnets CDF</th><th>Montant CDF</th><th>Carnets USD</th><th>Montant USD</th><th>Retenu mise</th></tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $report['comparison_period']['label'] }}</td>
            <td class="number">{{ $previous['membership_cards']['count']['CDF'] }}</td>
            <td class="number">{{ $money($previous['membership_cards']['price_total']['CDF'], 'CDF') }}</td>
            <td class="number">{{ $previous['membership_cards']['count']['USD'] }}</td>
            <td class="number">{{ $money($previous['membership_cards']['price_total']['USD'], 'USD') }}</td>
            <td class="number">{{ $money($previous['retenu_mise']['CDF'], 'CDF') }}<br>{{ $money($previous['retenu_mise']['USD'], 'USD') }}</td>
        </tr>
        <tr>
            <td>{{ $report['period']['label'] }}</td>
            <td class="number">{{ $current['membership_cards']['count']['CDF'] }}</td>
            <td class="number">{{ $money($current['membership_cards']['price_total']['CDF'], 'CDF') }}</td>
            <td class="number">{{ $current['membership_cards']['count']['USD'] }}</td>
            <td class="number">{{ $money($current['membership_cards']['price_total']['USD'], 'USD') }}</td>
            <td class="number">{{ $money($current['retenu_mise']['CDF'], 'CDF') }}<br>{{ $money($current['retenu_mise']['USD'], 'USD') }}</td>
        </tr>
    </tbody>
</table>
<div class="comment">
    Les carnets vendus sont comptés depuis les opérations du compte agent 97. Les retenues de mises utilisent le compte agent 195.
</div>

<h4>3. Situation des dépôts et retraits clients</h4>
<table>
    <thead>
        <tr><th>Intervalle de temps</th><th>Dépôts</th><th>Retraits</th><th>Reste</th></tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $report['comparison_period']['label'] }}</td>
            <td class="number">{{ $money($previous['deposits_withdrawals']['deposits']['CDF'], 'CDF') }}<br>{{ $money($previous['deposits_withdrawals']['deposits']['USD'], 'USD') }}</td>
            <td class="number">{{ $money($previous['deposits_withdrawals']['withdrawals']['CDF'], 'CDF') }}<br>{{ $money($previous['deposits_withdrawals']['withdrawals']['USD'], 'USD') }}</td>
            <td class="number">{{ $money($previous['deposits_withdrawals']['net']['CDF'], 'CDF') }}<br>{{ $money($previous['deposits_withdrawals']['net']['USD'], 'USD') }}</td>
        </tr>
        <tr>
            <td>{{ $report['period']['label'] }}</td>
            <td class="number">{{ $money($current['deposits_withdrawals']['deposits']['CDF'], 'CDF') }}<br>{{ $money($current['deposits_withdrawals']['deposits']['USD'], 'USD') }}</td>
            <td class="number">{{ $money($current['deposits_withdrawals']['withdrawals']['CDF'], 'CDF') }}<br>{{ $money($current['deposits_withdrawals']['withdrawals']['USD'], 'USD') }}</td>
            <td class="number">{{ $money($current['deposits_withdrawals']['net']['CDF'], 'CDF') }}<br>{{ $money($current['deposits_withdrawals']['net']['USD'], 'USD') }}</td>
        </tr>
    </tbody>
</table>
<div class="comment">
    Les dépôts et retraits ne prennent que les transactions rattachées aux membres.
</div>

<h4>4. Situation des crédits</h4>
<table>
    <thead>
        <tr><th>Indicateur</th><th>CDF</th><th>USD</th></tr>
    </thead>
    <tbody>
        <tr><td>Nombre de crédits octroyés</td><td class="number">{{ $current['granted_credits']['count']['CDF'] }}</td><td class="number">{{ $current['granted_credits']['count']['USD'] }}</td></tr>
        <tr><td>Montant des crédits octroyés</td><td class="number">{{ $money($current['granted_credits']['amount_total']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['granted_credits']['amount_total']['USD'], 'USD') }}</td></tr>
        <tr><td>Frais dossiers crédits</td><td class="number">{{ $money($current['granted_credits']['fees_total']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['granted_credits']['fees_total']['USD'], 'USD') }}</td></tr>
        <tr><td>Mutuelle crédit</td><td class="number">{{ $money($current['granted_credits']['mutuelle_total']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['granted_credits']['mutuelle_total']['USD'], 'USD') }}</td></tr>
        <tr><td>Crédits en retard</td><td class="number">{{ $current['overdue_credits']['count']['CDF'] }}</td><td class="number">{{ $current['overdue_credits']['count']['USD'] }}</td></tr>
        <tr><td>Retards supérieurs à 30 jours</td><td class="number">{{ $current['overdue_credits']['over_30_count']['CDF'] }}</td><td class="number">{{ $current['overdue_credits']['over_30_count']['USD'] }}</td></tr>
    </tbody>
</table>

@if($current['granted_credits']['items']->count())
    <table>
        <thead>
            <tr><th>N°</th><th>Client</th><th>Date décaissement</th><th>Crédit octroyé</th><th>Frais crédit</th><th>Mutuelle crédit</th></tr>
        </thead>
        <tbody>
            @foreach($current['granted_credits']['items'] as $credit)
                <tr>
                    <td class="number">{{ $loop->iteration }}</td>
                    <td>{{ $credit->user->name ?? '' }} {{ $credit->user->postnom ?? '' }} {{ $credit->user->prenom ?? '' }}</td>
                    <td class="number">{{ \Carbon\Carbon::parse($credit->start_date)->format('d/m/Y') }}</td>
                    <td class="number">{{ $money($credit->amount, $credit->currency) }}</td>
                    <td class="number">{{ $money($credit->frais_credit, $credit->currency) }}</td>
                    <td class="number">{{ $money($credit->mutuelle, $credit->currency) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="page-break"></div>

<h4>5. Remboursement des crédits - CDF et USD</h4>
<table>
    <thead>
        <tr><th>Indicateur</th><th>CDF</th><th>USD</th></tr>
    </thead>
    <tbody>
        <tr><td>Nombre de remboursements</td><td class="number">{{ $current['repayments']['count']['CDF'] }}</td><td class="number">{{ $current['repayments']['count']['USD'] }}</td></tr>
        <tr><td>Total remboursé</td><td class="number">{{ $money($current['repayments']['paid_total']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['repayments']['paid_total']['USD'], 'USD') }}</td></tr>
        <tr><td>Capital remboursé</td><td class="number">{{ $money($current['repayments']['principal_total']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['repayments']['principal_total']['USD'], 'USD') }}</td></tr>
        <tr><td>Intérêts perçus</td><td class="number">{{ $money($current['repayments']['interest_total']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['repayments']['interest_total']['USD'], 'USD') }}</td></tr>
        <tr><td>Pénalités perçues</td><td class="number">{{ $money($current['repayments']['penalty_total']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['repayments']['penalty_total']['USD'], 'USD') }}</td></tr>
    </tbody>
</table>

<h4>6. Commission sur adhésion des membres</h4>
<table>
    <thead>
        <tr><th>Intervalle de temps</th><th>En CDF</th><th>En USD</th></tr>
    </thead>
    <tbody>
        <tr><td>{{ $report['comparison_period']['label'] }}</td><td class="number">{{ $money($previous['adhesion_member']['CDF'], 'CDF') }}</td><td class="number">{{ $money($previous['adhesion_member']['USD'], 'USD') }}</td></tr>
        <tr><td>{{ $report['period']['label'] }}</td><td class="number">{{ $money($current['adhesion_member']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['adhesion_member']['USD'], 'USD') }}</td></tr>
        <tr class="total-row"><td>Total</td><td class="number">{{ $money($previous['adhesion_member']['CDF'] + $current['adhesion_member']['CDF'], 'CDF') }}</td><td class="number">{{ $money($previous['adhesion_member']['USD'] + $current['adhesion_member']['USD'], 'USD') }}</td></tr>
    </tbody>
</table>

<h4>7. Charges - CDF et USD</h4>
<table>
    <thead>
        <tr><th>Intervalle de temps</th><th>En CDF</th><th>En USD</th></tr>
    </thead>
    <tbody>
        <tr><td>{{ $report['comparison_period']['label'] }}</td><td class="number">{{ $money($previous['charges']['CDF'], 'CDF') }}</td><td class="number">{{ $money($previous['charges']['USD'], 'USD') }}</td></tr>
        <tr><td>{{ $report['period']['label'] }}</td><td class="number">{{ $money($current['charges']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['charges']['USD'], 'USD') }}</td></tr>
        <tr class="total-row"><td>Total</td><td class="number">{{ $money($previous['charges']['CDF'] + $current['charges']['CDF'], 'CDF') }}</td><td class="number">{{ $money($previous['charges']['USD'] + $current['charges']['USD'], 'USD') }}</td></tr>
    </tbody>
</table>

</body>
</html>
