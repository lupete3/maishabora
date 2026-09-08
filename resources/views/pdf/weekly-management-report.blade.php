<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport hebdomadaire décisionnel</title>
    <style>
        @page { margin: 10px 12px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #263238;
            margin: 0;
            line-height: 1.25;
        }
        h1, h2, h3, p { margin: 0; }
        .official-header {
            margin-bottom: 6px;
        }
        .official-table {
            width: 100%;
            border-collapse: collapse;
        }
        .official-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }
        .logo-cell {
            width: 13%;
        }
        .logo {
            width: 54px;
            max-height: 54px;
        }
        .company-cell {
            width: 62%;
            text-align: center;
            color: #263238;
        }
        .company-name {
            font-size: 11px;
            font-weight: 700;
            color: #a3800c;
            text-transform: uppercase;
        }
        .company-line {
            font-size: 6.8px;
            color: #5b6778;
            margin-top: 1px;
        }
        .meta-cell {
            width: 25%;
            text-align: right;
            font-size: 7px;
            color: #5b6778;
        }
        .official-rule {
            border-bottom: 2px solid #ed8d0f;
            margin-top: 5px;
        }
        .report-header {
            border-bottom: 1px solid #d7dee8;
            padding-bottom: 6px;
            margin-bottom: 7px;
        }
        .brand-row {
            width: 100%;
            border-collapse: collapse;
        }
        .brand-row td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .title {
            font-size: 15px;
            font-weight: 700;
            color: #a3800c;
            text-transform: uppercase;
        }
        .period {
            color: #5b6778;
            font-size: 8px;
            margin-top: 2px;
        }
        .stamp {
            text-align: right;
            font-size: 7px;
            color: #5b6778;
        }
        .grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px;
            margin-left: -5px;
            margin-right: -5px;
        }
        .kpi {
            border: 1px solid #dce3ec;
            border-radius: 4px;
            background: #f8fafc;
            padding: 5px 6px;
            vertical-align: top;
        }
        .kpi .label {
            color: #5b6778;
            font-size: 7px;
            text-transform: uppercase;
            font-weight: 700;
        }
        .kpi .value {
            font-size: 12px;
            color: #172b4d;
            font-weight: 700;
            margin-top: 2px;
        }
        .kpi .sub {
            font-size: 7px;
            color: #5b6778;
            margin-top: 1px;
        }
        .section-title {
            font-size: 9px;
            color: #a3800c;
            font-weight: 700;
            text-transform: uppercase;
            margin: 6px 0 4px;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
        }
        table.data th,
        table.data td {
            border: 1px solid #d7dee8;
            padding: 3px 4px;
            vertical-align: middle;
        }
        table.data th {
            background: #edf3f8;
            color: #a3800c;
            font-size: 7px;
            text-transform: uppercase;
        }
        .number { text-align: right; white-space: nowrap; }
        .strong { font-weight: 700; }
        .positive { color: #157347; }
        .negative { color: #b42318; }
        .total-row td {
            background: #f1f5f9;
            font-weight: 700;
        }
        .profit-row td {
            background: #e8f2ff;
            color: #a3800c;
            font-weight: 700;
        }
        .decision-box {
            border: 1px solid #d7dee8;
            border-left: 4px solid #a3800c;
            padding: 6px 7px;
            background: #fbfcfe;
            min-height: 62px;
        }
        .decision-box ul {
            margin: 3px 0 0 12px;
            padding: 0;
        }
        .decision-box li { margin-bottom: 2px; }
        .two-cols {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-left: -6px;
            margin-right: -6px;
        }
        .two-cols td {
            width: 50%;
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .footer {
            margin-top: 5px;
            padding-top: 4px;
            border-top: 1px solid #d7dee8;
            font-size: 7px;
            color: #6b778c;
            text-align: center;
        }
        .page-break { page-break-before: always; }
        .note {
            border: 1px solid #d7dee8;
            background: #fbfcfe;
            padding: 5px 6px;
            color: #5b6778;
            margin-bottom: 6px;
        }
        .trend-up { color: #157347; font-weight: 700; }
        .trend-down { color: #b42318; font-weight: 700; }
        .trend-flat { color: #5b6778; font-weight: 700; }
    </style>
</head>
<body>
@php
    $money = fn ($value, $currency) => number_format((float) $value, $currency === 'CDF' ? 0 : 2, ',', ' ') . ' ' . $currency;
    $current = $report['current'];
    $previous = $report['previous'];
    $vars = $report['variations'];
    $profit = $current['profitability'];
    $previousProfit = $previous['profitability'];
    $logoPath = public_path('assets/img/logo.jpg');
    $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
    $companyName = $company?->name ?? config('app.name', 'Maisha Bora');
    $companyAddress = $company?->address ?? env('APP_ADRESS');
    $companyPhone = $company?->phone ?? env('APP_PHONE');
    $companyEmail = $company?->email ?? env('APP_EMAIL');
    $companyRccm = $company?->rccm ?? env('APP_RCCM');

    $variation = function ($value) {
        if ($value === null) {
            return 'N/A';
        }

        return ($value > 0 ? '+' : '') . number_format((float) $value, 1, ',', ' ') . ' %';
    };

    $trendClass = function ($value, bool $inverse = false) {
        if ($value === null || (float) $value === 0.0) {
            return 'trend-flat';
        }

        $positive = (float) $value > 0;
        if ($inverse) {
            $positive = ! $positive;
        }

        return $positive ? 'trend-up' : 'trend-down';
    };

    $ratio = function ($part, $total) {
        return (float) $total > 0 ? round(((float) $part / (float) $total) * 100, 1) : 0;
    };

    $products = [
        'Carnets vendus - compte 97' => $profit['products']['membership_cards'],
        'Retenu mise - compte 195' => $profit['products']['retenu_mise'],
        'Mutuelle crédit' => $profit['products']['mutuelle_credit'],
        'Frais dossiers crédits' => $profit['products']['credit_fees'],
        'Commission adhésion - compte 951' => $profit['products']['adhesion_member'],
    ];

    $topProduct = function ($currency) use ($products) {
        $bestLabel = 'Aucun produit';
        $bestValue = 0;

        foreach ($products as $label => $amounts) {
            $amount = (float) ($amounts[$currency] ?? 0);
            if ($amount > $bestValue) {
                $bestValue = $amount;
                $bestLabel = $label;
            }
        }

        return [$bestLabel, $bestValue];
    };

    [$topProductCdfLabel, $topProductCdfValue] = $topProduct('CDF');
    [$topProductUsdLabel, $topProductUsdValue] = $topProduct('USD');
    $chargeRateCdf = $ratio($profit['charges_total']['CDF'], $profit['products_total']['CDF']);
    $chargeRateUsd = $ratio($profit['charges_total']['USD'], $profit['products_total']['USD']);
@endphp

<div class="official-header">
    <table class="official-table">
        <tr>
            <td class="logo-cell">
                @if($logoData)
                    <img src="data:image/jpeg;base64,{{ $logoData }}" class="logo" alt="Logo">
                @endif
            </td>
            <td class="company-cell">
                <div class="company-name">{{ strtoupper($companyName) }}</div>
                @if($companyAddress)
                    <div class="company-line">Adresse : {{ $companyAddress }}</div>
                @endif
                @if($companyPhone || $companyEmail)
                    <div class="company-line">Tel : {{ $companyPhone ?: '-' }} | Email : {{ $companyEmail ?: '-' }}</div>
                @endif
                @if($companyRccm)
                    <div class="company-line">RCCM : {{ $companyRccm }}</div>
                @endif
            </td>
            <td class="meta-cell">
                <strong>Période :</strong><br>
                {{ $report['period']['label'] }}<br>
                <strong>Comparaison :</strong><br>
                {{ $report['comparison_period']['label'] }}
            </td>
        </tr>
    </table>
    <div class="official-rule"></div>
</div>

<div class="report-header">
    <table class="brand-row">
        <tr>
            <td>
                <h1 class="title">Rapport hebdomadaire décisionnel</h1>
                <div class="period">{{ $report['period']['label'] }} | Comparaison : {{ $report['comparison_period']['label'] }}</div>
            </td>
            <td class="stamp">
                Généré le {{ now()->format('d/m/Y H:i') }}
            </td>
        </tr>
    </table>
</div>

<table class="grid">
    <tr>
        <td class="kpi">
            <div class="label">Nouveaux clients</div>
            <div class="value">{{ $current['new_clients']['total'] }}</div>
            <div class="sub">Objectif : {{ number_format($current['new_clients']['target_rate'], 1, ',', ' ') }} %</div>
        </td>
        <td class="kpi">
            <div class="label">Carnets vendus</div>
            <div class="value">{{ $current['membership_cards']['count']['CDF'] + $current['membership_cards']['count']['USD'] }}</div>
            <div class="sub">CDF {{ $current['membership_cards']['count']['CDF'] }} | USD {{ $current['membership_cards']['count']['USD'] }}</div>
        </td>
        <td class="kpi">
            <div class="label">Crédits octroyés</div>
            <div class="value">{{ $current['granted_credits']['count']['CDF'] + $current['granted_credits']['count']['USD'] }}</div>
            <div class="sub">{{ $money($current['granted_credits']['amount_total']['CDF'], 'CDF') }} | {{ $money($current['granted_credits']['amount_total']['USD'], 'USD') }}</div>
        </td>
        <td class="kpi">
            <div class="label">Remboursements</div>
            <div class="value">{{ $current['repayments']['count']['CDF'] + $current['repayments']['count']['USD'] }}</div>
            <div class="sub">{{ $money($current['repayments']['paid_total']['CDF'], 'CDF') }} | {{ $money($current['repayments']['paid_total']['USD'], 'USD') }}</div>
        </td>
        <td class="kpi">
            <div class="label">Produits</div>
            <div class="value">{{ $money($profit['products_total']['CDF'], 'CDF') }}</div>
            <div class="sub">{{ $money($profit['products_total']['USD'], 'USD') }}</div>
        </td>
        <td class="kpi">
            <div class="label">Bénéfice net</div>
            <div class="value {{ $profit['net_profit']['CDF'] < 0 ? 'negative' : 'positive' }}">{{ $money($profit['net_profit']['CDF'], 'CDF') }}</div>
            <div class="sub {{ $profit['net_profit']['USD'] < 0 ? 'negative' : 'positive' }}">{{ $money($profit['net_profit']['USD'], 'USD') }}</div>
        </td>
    </tr>
</table>

<table class="two-cols">
    <tr>
        <td>
            <div class="section-title">Situation du bénéfice</div>
            <table class="data">
                <thead>
                    <tr><th>Source</th><th>CDF</th><th>USD</th></tr>
                </thead>
                <tbody>
                    @foreach($products as $label => $amounts)
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="number">{{ $money($amounts['CDF'], 'CDF') }}</td>
                            <td class="number">{{ $money($amounts['USD'], 'USD') }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td>Total produits</td>
                        <td class="number">{{ $money($profit['products_total']['CDF'], 'CDF') }}</td>
                        <td class="number">{{ $money($profit['products_total']['USD'], 'USD') }}</td>
                    </tr>
                    <tr>
                        <td>Charges - compte 452</td>
                        <td class="number">{{ $money($profit['charges_total']['CDF'], 'CDF') }}</td>
                        <td class="number">{{ $money($profit['charges_total']['USD'], 'USD') }}</td>
                    </tr>
                    <tr class="profit-row">
                        <td>Bénéfice net</td>
                        <td class="number">{{ $money($profit['net_profit']['CDF'], 'CDF') }}</td>
                        <td class="number">{{ $money($profit['net_profit']['USD'], 'USD') }}</td>
                    </tr>
                </tbody>
            </table>
        </td>
        <td>
            <div class="section-title">Lecture rapide</div>
            <div class="decision-box">
                <div class="strong">Décision financière</div>
                <ul>
                    <li>CDF : résultat {{ $profit['net_profit']['CDF'] >= 0 ? 'positif' : 'négatif' }} de {{ $money($profit['net_profit']['CDF'], 'CDF') }}.</li>
                    <li>USD : résultat {{ $profit['net_profit']['USD'] >= 0 ? 'positif' : 'négatif' }} de {{ $money($profit['net_profit']['USD'], 'USD') }}.</li>
                    <li>Charges : {{ number_format($chargeRateCdf, 1, ',', ' ') }} % des produits CDF et {{ number_format($chargeRateUsd, 1, ',', ' ') }} % des produits USD.</li>
                    <li>Premier produit CDF : {{ $topProductCdfLabel }} ({{ $money($topProductCdfValue, 'CDF') }}).</li>
                    <li>Premier produit USD : {{ $topProductUsdLabel }} ({{ $money($topProductUsdValue, 'USD') }}).</li>
                </ul>
            </div>

            <div class="section-title">Crédits et risques</div>
            <table class="data">
                <thead>
                    <tr><th>Indicateur</th><th>CDF</th><th>USD</th></tr>
                </thead>
                <tbody>
                    <tr><td>Crédits octroyés</td><td class="number">{{ $money($current['granted_credits']['amount_total']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['granted_credits']['amount_total']['USD'], 'USD') }}</td></tr>
                    <tr><td>Remboursements reçus</td><td class="number">{{ $money($current['repayments']['paid_total']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['repayments']['paid_total']['USD'], 'USD') }}</td></tr>
                    <tr><td>Crédits en retard</td><td class="number">{{ $current['overdue_credits']['count']['CDF'] }}</td><td class="number">{{ $current['overdue_credits']['count']['USD'] }}</td></tr>
                    <tr><td>Retards supérieurs à 30 jours</td><td class="number">{{ $current['overdue_credits']['over_30_count']['CDF'] }}</td><td class="number">{{ $current['overdue_credits']['over_30_count']['USD'] }}</td></tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>

<table class="two-cols" style="margin-top: 6px;">
    <tr>
        <td>
            <div class="section-title">Activité clients</div>
            <table class="data">
                <thead>
                    <tr><th>Indicateur</th><th>Période</th><th>Variation</th></tr>
                </thead>
                <tbody>
                    <tr><td>Nouveaux clients</td><td class="number">{{ $current['new_clients']['total'] }}</td><td class="number">{{ $variation($vars['new_clients_total']) }}</td></tr>
                    <tr><td>Dépôts CDF</td><td class="number">{{ $money($current['deposits_withdrawals']['deposits']['CDF'], 'CDF') }}</td><td class="number">{{ $variation($vars['deposits']['CDF']) }}</td></tr>
                    <tr><td>Dépôts USD</td><td class="number">{{ $money($current['deposits_withdrawals']['deposits']['USD'], 'USD') }}</td><td class="number">{{ $variation($vars['deposits']['USD']) }}</td></tr>
                    <tr><td>Retraits CDF</td><td class="number">{{ $money($current['deposits_withdrawals']['withdrawals']['CDF'], 'CDF') }}</td><td class="number">{{ $variation($vars['withdrawals']['CDF']) }}</td></tr>
                    <tr><td>Retraits USD</td><td class="number">{{ $money($current['deposits_withdrawals']['withdrawals']['USD'], 'USD') }}</td><td class="number">{{ $variation($vars['withdrawals']['USD']) }}</td></tr>
                </tbody>
            </table>
        </td>
        <td>
            <div class="section-title">Comparaison bénéfice</div>
            <table class="data">
                <thead>
                    <tr><th>Indicateur</th><th>CDF</th><th>USD</th></tr>
                </thead>
                <tbody>
                    <tr><td>Produits semaine précédente</td><td class="number">{{ $money($previousProfit['products_total']['CDF'], 'CDF') }}</td><td class="number">{{ $money($previousProfit['products_total']['USD'], 'USD') }}</td></tr>
                    <tr><td>Produits semaine actuelle</td><td class="number">{{ $money($profit['products_total']['CDF'], 'CDF') }}</td><td class="number">{{ $money($profit['products_total']['USD'], 'USD') }}</td></tr>
                    <tr><td>Variation produits</td><td class="number">{{ $variation($vars['profitability_products']['CDF']) }}</td><td class="number">{{ $variation($vars['profitability_products']['USD']) }}</td></tr>
                    <tr><td>Variation charges</td><td class="number">{{ $variation($vars['profitability_charges']['CDF']) }}</td><td class="number">{{ $variation($vars['profitability_charges']['USD']) }}</td></tr>
                    <tr class="profit-row"><td>Variation bénéfice net</td><td class="number">{{ $variation($vars['profitability_net_profit']['CDF']) }}</td><td class="number">{{ $variation($vars['profitability_net_profit']['USD']) }}</td></tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>

<div class="footer">
    Page 1/2 - Synthèse décisionnelle. Les montants CDF et USD sont analysés séparément, sans conversion de devise.
</div>

<div class="page-break"></div>

<div class="official-header">
    <table class="official-table">
        <tr>
            <td class="logo-cell">
                @if($logoData)
                    <img src="data:image/jpeg;base64,{{ $logoData }}" class="logo" alt="Logo">
                @endif
            </td>
            <td class="company-cell">
                <div class="company-name">{{ strtoupper($companyName) }}</div>
                @if($companyAddress)
                    <div class="company-line">Adresse : {{ $companyAddress }}</div>
                @endif
                @if($companyPhone || $companyEmail)
                    <div class="company-line">Tel : {{ $companyPhone ?: '-' }} | Email : {{ $companyEmail ?: '-' }}</div>
                @endif
                @if($companyRccm)
                    <div class="company-line">RCCM : {{ $companyRccm }}</div>
                @endif
            </td>
            <td class="meta-cell">
                <strong>Période :</strong><br>
                {{ $report['period']['label'] }}<br>
                <strong>Comparaison :</strong><br>
                {{ $report['comparison_period']['label'] }}
            </td>
        </tr>
    </table>
    <div class="official-rule"></div>
</div>

<div class="report-header">
    <table class="brand-row">
        <tr>
            <td>
                <h1 class="title">Détail des variations hebdomadaires</h1>
                <div class="period">{{ $report['period']['label'] }} | Référence : {{ $report['comparison_period']['label'] }}</div>
            </td>
            <td class="stamp">
                Généré le {{ now()->format('d/m/Y H:i') }}
            </td>
        </tr>
    </table>
</div>

<div class="note">
    Cette page détaille les écarts entre la semaine analysée et la semaine précédente. Les variations sont calculées par devise quand l'indicateur est financier.
</div>

<table class="two-cols">
    <tr>
        <td>
            <div class="section-title">1. Clients et adhésions</div>
            <table class="data">
                <thead>
                    <tr><th>Indicateur</th><th>Précédent</th><th>Actuel</th><th>Variation</th></tr>
                </thead>
                <tbody>
                    <tr><td>Nouveaux clients hommes</td><td class="number">{{ $previous['new_clients']['men'] }}</td><td class="number">{{ $current['new_clients']['men'] }}</td><td class="number {{ $trendClass($vars['new_clients_men']) }}">{{ $variation($vars['new_clients_men']) }}</td></tr>
                    <tr><td>Nouveaux clients femmes</td><td class="number">{{ $previous['new_clients']['women'] }}</td><td class="number">{{ $current['new_clients']['women'] }}</td><td class="number {{ $trendClass($vars['new_clients_women']) }}">{{ $variation($vars['new_clients_women']) }}</td></tr>
                    <tr class="total-row"><td>Total nouveaux clients</td><td class="number">{{ $previous['new_clients']['total'] }}</td><td class="number">{{ $current['new_clients']['total'] }}</td><td class="number {{ $trendClass($vars['new_clients_total']) }}">{{ $variation($vars['new_clients_total']) }}</td></tr>
                    <tr><td>Carnets vendus CDF</td><td class="number">{{ $previous['membership_cards']['count']['CDF'] }}</td><td class="number">{{ $current['membership_cards']['count']['CDF'] }}</td><td class="number {{ $trendClass($vars['membership_cards_count']['CDF']) }}">{{ $variation($vars['membership_cards_count']['CDF']) }}</td></tr>
                    <tr><td>Carnets vendus USD</td><td class="number">{{ $previous['membership_cards']['count']['USD'] }}</td><td class="number">{{ $current['membership_cards']['count']['USD'] }}</td><td class="number {{ $trendClass($vars['membership_cards_count']['USD']) }}">{{ $variation($vars['membership_cards_count']['USD']) }}</td></tr>
                    <tr><td>Commission adhésion CDF - compte 951</td><td class="number">{{ $money($previous['adhesion_member']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['adhesion_member']['CDF'], 'CDF') }}</td><td class="number {{ $trendClass($vars['adhesion_member']['CDF']) }}">{{ $variation($vars['adhesion_member']['CDF']) }}</td></tr>
                    <tr><td>Commission adhésion USD - compte 951</td><td class="number">{{ $money($previous['adhesion_member']['USD'], 'USD') }}</td><td class="number">{{ $money($current['adhesion_member']['USD'], 'USD') }}</td><td class="number {{ $trendClass($vars['adhesion_member']['USD']) }}">{{ $variation($vars['adhesion_member']['USD']) }}</td></tr>
                </tbody>
            </table>

            <div class="section-title">2. Dépôts et retraits clients</div>
            <table class="data">
                <thead>
                    <tr><th>Indicateur</th><th>Précédent</th><th>Actuel</th><th>Variation</th></tr>
                </thead>
                <tbody>
                    <tr><td>Dépôts CDF</td><td class="number">{{ $money($previous['deposits_withdrawals']['deposits']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['deposits_withdrawals']['deposits']['CDF'], 'CDF') }}</td><td class="number {{ $trendClass($vars['deposits']['CDF']) }}">{{ $variation($vars['deposits']['CDF']) }}</td></tr>
                    <tr><td>Dépôts USD</td><td class="number">{{ $money($previous['deposits_withdrawals']['deposits']['USD'], 'USD') }}</td><td class="number">{{ $money($current['deposits_withdrawals']['deposits']['USD'], 'USD') }}</td><td class="number {{ $trendClass($vars['deposits']['USD']) }}">{{ $variation($vars['deposits']['USD']) }}</td></tr>
                    <tr><td>Retraits CDF</td><td class="number">{{ $money($previous['deposits_withdrawals']['withdrawals']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['deposits_withdrawals']['withdrawals']['CDF'], 'CDF') }}</td><td class="number {{ $trendClass($vars['withdrawals']['CDF'], true) }}">{{ $variation($vars['withdrawals']['CDF']) }}</td></tr>
                    <tr><td>Retraits USD</td><td class="number">{{ $money($previous['deposits_withdrawals']['withdrawals']['USD'], 'USD') }}</td><td class="number">{{ $money($current['deposits_withdrawals']['withdrawals']['USD'], 'USD') }}</td><td class="number {{ $trendClass($vars['withdrawals']['USD'], true) }}">{{ $variation($vars['withdrawals']['USD']) }}</td></tr>
                    <tr class="total-row"><td>Reste net CDF</td><td class="number">{{ $money($previous['deposits_withdrawals']['net']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['deposits_withdrawals']['net']['CDF'], 'CDF') }}</td><td class="number {{ $trendClass($vars['net']['CDF']) }}">{{ $variation($vars['net']['CDF']) }}</td></tr>
                    <tr class="total-row"><td>Reste net USD</td><td class="number">{{ $money($previous['deposits_withdrawals']['net']['USD'], 'USD') }}</td><td class="number">{{ $money($current['deposits_withdrawals']['net']['USD'], 'USD') }}</td><td class="number {{ $trendClass($vars['net']['USD']) }}">{{ $variation($vars['net']['USD']) }}</td></tr>
                </tbody>
            </table>
        </td>
        <td>
            <div class="section-title">3. Crédits, frais et mutuelle</div>
            <table class="data">
                <thead>
                    <tr><th>Indicateur</th><th>Précédent</th><th>Actuel</th><th>Variation</th></tr>
                </thead>
                <tbody>
                    <tr><td>Crédits octroyés CDF</td><td class="number">{{ $money($previous['granted_credits']['amount_total']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['granted_credits']['amount_total']['CDF'], 'CDF') }}</td><td class="number {{ $trendClass($vars['granted_credits']['CDF']) }}">{{ $variation($vars['granted_credits']['CDF']) }}</td></tr>
                    <tr><td>Crédits octroyés USD</td><td class="number">{{ $money($previous['granted_credits']['amount_total']['USD'], 'USD') }}</td><td class="number">{{ $money($current['granted_credits']['amount_total']['USD'], 'USD') }}</td><td class="number {{ $trendClass($vars['granted_credits']['USD']) }}">{{ $variation($vars['granted_credits']['USD']) }}</td></tr>
                    <tr><td>Frais dossiers CDF</td><td class="number">{{ $money($previous['granted_credits']['fees_total']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['granted_credits']['fees_total']['CDF'], 'CDF') }}</td><td class="number {{ $trendClass($vars['credit_fees']['CDF']) }}">{{ $variation($vars['credit_fees']['CDF']) }}</td></tr>
                    <tr><td>Frais dossiers USD</td><td class="number">{{ $money($previous['granted_credits']['fees_total']['USD'], 'USD') }}</td><td class="number">{{ $money($current['granted_credits']['fees_total']['USD'], 'USD') }}</td><td class="number {{ $trendClass($vars['credit_fees']['USD']) }}">{{ $variation($vars['credit_fees']['USD']) }}</td></tr>
                    <tr><td>Mutuelle crédit CDF</td><td class="number">{{ $money($previous['granted_credits']['mutuelle_total']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['granted_credits']['mutuelle_total']['CDF'], 'CDF') }}</td><td class="number {{ $trendClass($vars['mutuelle_credit']['CDF']) }}">{{ $variation($vars['mutuelle_credit']['CDF']) }}</td></tr>
                    <tr><td>Mutuelle crédit USD</td><td class="number">{{ $money($previous['granted_credits']['mutuelle_total']['USD'], 'USD') }}</td><td class="number">{{ $money($current['granted_credits']['mutuelle_total']['USD'], 'USD') }}</td><td class="number {{ $trendClass($vars['mutuelle_credit']['USD']) }}">{{ $variation($vars['mutuelle_credit']['USD']) }}</td></tr>
                    <tr><td>Remboursements CDF</td><td class="number">{{ $money($previous['repayments']['paid_total']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['repayments']['paid_total']['CDF'], 'CDF') }}</td><td class="number {{ $trendClass($vars['repayments']['CDF']) }}">{{ $variation($vars['repayments']['CDF']) }}</td></tr>
                    <tr><td>Remboursements USD</td><td class="number">{{ $money($previous['repayments']['paid_total']['USD'], 'USD') }}</td><td class="number">{{ $money($current['repayments']['paid_total']['USD'], 'USD') }}</td><td class="number {{ $trendClass($vars['repayments']['USD']) }}">{{ $variation($vars['repayments']['USD']) }}</td></tr>
                </tbody>
            </table>

            <div class="section-title">4. Résultat et charges</div>
            <table class="data">
                <thead>
                    <tr><th>Indicateur</th><th>Précédent</th><th>Actuel</th><th>Variation</th></tr>
                </thead>
                <tbody>
                    <tr><td>Total produits CDF</td><td class="number">{{ $money($previousProfit['products_total']['CDF'], 'CDF') }}</td><td class="number">{{ $money($profit['products_total']['CDF'], 'CDF') }}</td><td class="number {{ $trendClass($vars['profitability_products']['CDF']) }}">{{ $variation($vars['profitability_products']['CDF']) }}</td></tr>
                    <tr><td>Total produits USD</td><td class="number">{{ $money($previousProfit['products_total']['USD'], 'USD') }}</td><td class="number">{{ $money($profit['products_total']['USD'], 'USD') }}</td><td class="number {{ $trendClass($vars['profitability_products']['USD']) }}">{{ $variation($vars['profitability_products']['USD']) }}</td></tr>
                    <tr><td>Charges CDF - compte 452</td><td class="number">{{ $money($previous['charges']['CDF'], 'CDF') }}</td><td class="number">{{ $money($current['charges']['CDF'], 'CDF') }}</td><td class="number {{ $trendClass($vars['charges']['CDF'], true) }}">{{ $variation($vars['charges']['CDF']) }}</td></tr>
                    <tr><td>Charges USD - compte 452</td><td class="number">{{ $money($previous['charges']['USD'], 'USD') }}</td><td class="number">{{ $money($current['charges']['USD'], 'USD') }}</td><td class="number {{ $trendClass($vars['charges']['USD'], true) }}">{{ $variation($vars['charges']['USD']) }}</td></tr>
                    <tr class="profit-row"><td>Bénéfice net CDF</td><td class="number">{{ $money($previousProfit['net_profit']['CDF'], 'CDF') }}</td><td class="number">{{ $money($profit['net_profit']['CDF'], 'CDF') }}</td><td class="number {{ $trendClass($vars['profitability_net_profit']['CDF']) }}">{{ $variation($vars['profitability_net_profit']['CDF']) }}</td></tr>
                    <tr class="profit-row"><td>Bénéfice net USD</td><td class="number">{{ $money($previousProfit['net_profit']['USD'], 'USD') }}</td><td class="number">{{ $money($profit['net_profit']['USD'], 'USD') }}</td><td class="number {{ $trendClass($vars['profitability_net_profit']['USD']) }}">{{ $variation($vars['profitability_net_profit']['USD']) }}</td></tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>

<div class="footer">
    Page 2/2 - Détail des variations. Les hausses de charges et de retraits sont signalées comme points de vigilance.
</div>
</body>
</html>
