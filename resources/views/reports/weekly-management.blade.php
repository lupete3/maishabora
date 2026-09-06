@extends('layouts.backend')

@section('title', 'Rapport hebdomadaire')

@section('content')
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

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Rapport hebdomadaire global</h4>
            <div class="text-muted">{{ $report['period']['label'] }} | Comparaison : {{ $report['comparison_period']['label'] }}</div>
        </div>
        <a href="{{ route('reports.weekly-management.export-pdf', ['start_date' => $startDate, 'end_date' => $endDate]) }}" target="_blank" class="btn btn-danger">
            <i class="bx bx-file"></i> Exporter PDF
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.weekly-management') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Date début</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date fin</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="bx bx-filter"></i> Actualiser
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Nouveaux clients</div>
                    <h3 class="mb-1">{{ $current['new_clients']['total'] }}</h3>
                    <div class="small">Objectif : {{ $current['new_clients']['target_rate'] }} %</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Carnets vendus</div>
                    <h3 class="mb-1">{{ $current['membership_cards']['count']['CDF'] + $current['membership_cards']['count']['USD'] }}</h3>
                    <div class="small">CDF : {{ $current['membership_cards']['count']['CDF'] }} | USD : {{ $current['membership_cards']['count']['USD'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Crédits octroyés</div>
                    <h3 class="mb-1">{{ $current['granted_credits']['count']['CDF'] + $current['granted_credits']['count']['USD'] }}</h3>
                    <div class="small">{{ $money($current['granted_credits']['amount_total']['CDF'], 'CDF') }} | {{ $money($current['granted_credits']['amount_total']['USD'], 'USD') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Crédits en retard</div>
                    <h3 class="mb-1">{{ $current['overdue_credits']['count']['CDF'] + $current['overdue_credits']['count']['USD'] }}</h3>
                    <div class="small">&gt; 30 jours : {{ $current['overdue_credits']['over_30_count']['CDF'] + $current['overdue_credits']['over_30_count']['USD'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header fw-bold">1. Effectif nouveaux clients</div>
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead><tr><th>Période</th><th>Hommes</th><th>Femmes</th><th>Total</th><th>Objectif</th><th>Objectif en %</th></tr></thead>
                <tbody>
                    <tr><td>{{ $report['comparison_period']['label'] }}</td><td>{{ $previous['new_clients']['men'] }}</td><td>{{ $previous['new_clients']['women'] }}</td><td>{{ $previous['new_clients']['total'] }}</td><td>{{ $previous['new_clients']['target'] }}</td><td>{{ $previous['new_clients']['target_rate'] }} %</td></tr>
                    <tr><td>{{ $report['period']['label'] }}</td><td>{{ $current['new_clients']['men'] }}</td><td>{{ $current['new_clients']['women'] }}</td><td>{{ $current['new_clients']['total'] }}</td><td>{{ $current['new_clients']['target'] }}</td><td>{{ $current['new_clients']['target_rate'] }} %</td></tr>
                    <tr class="table-light fw-bold"><td>Variation</td><td>{{ $variation($vars['new_clients_men']) }}</td><td>{{ $variation($vars['new_clients_women']) }}</td><td>{{ $variation($vars['new_clients_total']) }}</td><td colspan="2"></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header fw-bold">2. Ventes des carnets et commissions</div>
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead><tr><th>Indicateur</th><th>CDF</th><th>USD</th><th>Variation CDF</th><th>Variation USD</th></tr></thead>
                <tbody>
                    <tr><td>Nombre de carnets vendus</td><td>{{ $current['membership_cards']['count']['CDF'] }}</td><td>{{ $current['membership_cards']['count']['USD'] }}</td><td>{{ $variation($vars['membership_cards_count']['CDF']) }}</td><td>{{ $variation($vars['membership_cards_count']['USD']) }}</td></tr>
                    <tr><td>Montant des carnets vendus - compte 97</td><td>{{ $money($current['membership_cards']['price_total']['CDF'], 'CDF') }}</td><td>{{ $money($current['membership_cards']['price_total']['USD'], 'USD') }}</td><td>{{ $variation($vars['membership_cards_price_total']['CDF']) }}</td><td>{{ $variation($vars['membership_cards_price_total']['USD']) }}</td></tr>
                    <tr><td>Retenu mise - compte 195</td><td>{{ $money($current['retenu_mise']['CDF'], 'CDF') }}</td><td>{{ $money($current['retenu_mise']['USD'], 'USD') }}</td><td>{{ $variation($vars['retenu_mise']['CDF']) }}</td><td>{{ $variation($vars['retenu_mise']['USD']) }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header fw-bold">3. Situation des dépôts et retraits des membres</div>
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead><tr><th>Indicateur</th><th>CDF</th><th>USD</th><th>Variation CDF</th><th>Variation USD</th></tr></thead>
                <tbody>
                    <tr><td>Dépôts</td><td>{{ $money($current['deposits_withdrawals']['deposits']['CDF'], 'CDF') }}</td><td>{{ $money($current['deposits_withdrawals']['deposits']['USD'], 'USD') }}</td><td>{{ $variation($vars['deposits']['CDF']) }}</td><td>{{ $variation($vars['deposits']['USD']) }}</td></tr>
                    <tr><td>Retraits</td><td>{{ $money($current['deposits_withdrawals']['withdrawals']['CDF'], 'CDF') }}</td><td>{{ $money($current['deposits_withdrawals']['withdrawals']['USD'], 'USD') }}</td><td>{{ $variation($vars['withdrawals']['CDF']) }}</td><td>{{ $variation($vars['withdrawals']['USD']) }}</td></tr>
                    <tr class="table-light fw-bold"><td>Reste</td><td>{{ $money($current['deposits_withdrawals']['net']['CDF'], 'CDF') }}</td><td>{{ $money($current['deposits_withdrawals']['net']['USD'], 'USD') }}</td><td>{{ $variation($vars['net']['CDF']) }}</td><td>{{ $variation($vars['net']['USD']) }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header fw-bold">4. Situation des crédits</div>
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead><tr><th>Indicateur</th><th>CDF</th><th>USD</th></tr></thead>
                <tbody>
                    <tr><td>Crédits octroyés</td><td>{{ $money($current['granted_credits']['amount_total']['CDF'], 'CDF') }}</td><td>{{ $money($current['granted_credits']['amount_total']['USD'], 'USD') }}</td></tr>
                    <tr><td>Frais dossiers crédits</td><td>{{ $money($current['granted_credits']['fees_total']['CDF'], 'CDF') }}</td><td>{{ $money($current['granted_credits']['fees_total']['USD'], 'USD') }}</td></tr>
                    <tr><td>Mutuelle crédit</td><td>{{ $money($current['granted_credits']['mutuelle_total']['CDF'], 'CDF') }}</td><td>{{ $money($current['granted_credits']['mutuelle_total']['USD'], 'USD') }}</td></tr>
                    <tr><td>Crédits en retard</td><td>{{ $current['overdue_credits']['count']['CDF'] }}</td><td>{{ $current['overdue_credits']['count']['USD'] }}</td></tr>
                    <tr><td>Retards supérieurs à 30 jours</td><td>{{ $current['overdue_credits']['over_30_count']['CDF'] }}</td><td>{{ $current['overdue_credits']['over_30_count']['USD'] }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header fw-bold">5. Remboursements, adhésions et charges</div>
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead><tr><th>Indicateur</th><th>CDF</th><th>USD</th></tr></thead>
                <tbody>
                    <tr><td>Remboursements crédits</td><td>{{ $money($current['repayments']['paid_total']['CDF'], 'CDF') }}</td><td>{{ $money($current['repayments']['paid_total']['USD'], 'USD') }}</td></tr>
                    <tr><td>Commission adhésion membre - compte 734</td><td>{{ $money($current['adhesion_member']['CDF'], 'CDF') }}</td><td>{{ $money($current['adhesion_member']['USD'], 'USD') }}</td></tr>
                    <tr><td>Charges - compte 452</td><td>{{ $money($current['charges']['CDF'], 'CDF') }}</td><td>{{ $money($current['charges']['USD'], 'USD') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
