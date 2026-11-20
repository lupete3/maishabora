<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Rapport Comptes Membres</title>
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
            font-size: 8px;
            padding: 2px;
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

        th {
            background-color: #f1c206;
        }

        .section-title {
            margin-top: 10px;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }

        .totals p {
            margin: 2px 0;
        }

        .logo {
            width: 80px;
        }
    </style>
</head>

<body>

    @include('partials.pdf-header', ['reportTitle' => 'RAPPORT DES COMPTES MEMBRES'])
    
    @if(isset($accountType) && $accountType !== 'all')
        <div class="text-center bold" style="margin-top: -10px; margin-bottom: 10px;">
            ({{ $accountType === 'current' ? 'COMPTE COURANT' : 'COMPTE ÉPARGNE' }})
        </div>
    @endif
    
    @php
        // Montants au format français « 1 234,56 » ; « — » pour zéro dans le tableau des membres
        $fr = fn ($n, $dash = false) => \App\Support\ClientBalanceRanking::fr($n, $dash);
        $withTotal = ($sortBy ?? 'name') === 'total';
        $colsAvantMontants = (!empty($isBalanceSort) ? 1 : 0) + 2;
    @endphp

    @isset($categoryLabel)
        <p class="text-center" style="margin: 0;">Catégorie : {{ $categoryLabel }} — Titulaires : {{ $natureLabel }}</p>
    @endisset
    @if(isset($alphabetRange) && $alphabetRange !== 'all')
        <p class="text-center" style="margin: 0;">Tranche : {{ $alphabetRange }}</p>
    @endif
    @if(isset($minBalance) && $minBalance > 0)
        <p class="text-center" style="margin: 0;">Solde minimum : {{ $fr($minBalance) }}</p>
    @endif

    {{-- 1. Récapitulatif de la sélection : le lien entre net, négatifs et dû est visible --}}
    @isset($recap)
        <table class="table" border="1" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th>Devise</th>
                    <th>Comptes courants</th>
                    <th>Comptes d'épargne</th>
                    <th>Total net</th>
                    <th>Soldes négatifs</th>
                    <th>Dû aux clients</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recap as $currency => $r)
                    <tr>
                        <td class="text-center"><strong>{{ $currency }}</strong></td>
                        <td class="text-end">{{ $fr($r['courant']) }}</td>
                        <td class="text-end">{{ $fr($r['epargne']) }}</td>
                        <td class="text-end">{{ $fr($r['net']) }}</td>
                        <td class="text-end">{{ $fr($r['negatifs']) }} ({{ $r['nb_negatifs'] }})</td>
                        <td class="text-end"><strong>{{ $fr($r['du']) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p style="margin: 0 0 2px 0; font-size: 8px;">
            Dû aux clients = somme des soldes positifs = total net − soldes négatifs. Les soldes négatifs ne sont pas
            une dette envers le client : ils sont à régulariser (voir l'annexe à la fin du dernier lot).
        </p>
        @isset($liabilities)
            <p style="margin: 0 0 8px 0; font-size: 8px;">
                <strong>Toute l'institution (vrais clients, hors comptes internes et de test) :</strong>
                @foreach ($liabilities as $currency => $l)
                    dû {{ $fr($l['du']) }} {{ $currency }}@if (!$loop->last) ; @endif
                @endforeach
            </p>
        @endisset
    @endisset

    @if (isset($lot))
        <h4 style="margin: 0 0 4px 0;">
            Lot {{ $lot['number'] }} / {{ $lot['count'] }} : membres {{ $lot['from'] }} à {{ $lot['to'] }}
            sur {{ $lot['total'] }}
        </h4>
    @else
        <h4 style="margin: 0 0 10px 0;">Total Membres listés : {{ $balances->count() }}</h4>
    @endif
    @if (!empty($isBalanceSort))
        <p style="margin: 0 0 8px 0;">
            Classement : {{ $sortLabel }}
            @if ($sortBy === 'total' && $rate)
                (CDF converti au taux de {{ number_format($rate, 0, ',', ' ') }} CDF = 1 USD)
            @endif
        </p>
    @endif

    {{-- 2. Membres du lot --}}
    <table class="table" border="1" cellspacing="0" cellpadding="0">
        <thead>
            <tr>
                @if (!empty($isBalanceSort))
                    <th rowspan="2" style="width: 4%;">Rang</th>
                @endif
                <th rowspan="2" style="width: 11%;">Code</th>
                <th rowspan="2" style="width: 25%;">Membre</th>
                <th colspan="3">Solde USD</th>
                <th colspan="3">Solde CDF</th>
                @if ($withTotal)
                    <th rowspan="2">Total équiv. USD</th>
                @endif
                @if (!empty($isBalanceSort))
                    <th rowspan="2">Part</th>
                @endif
            </tr>
            <tr>
                <th style="font-size: 7px;">Courant</th>
                <th style="font-size: 7px;">Épargne</th>
                <th style="font-size: 7px;">Total</th>
                <th style="font-size: 7px;">Courant</th>
                <th style="font-size: 7px;">Épargne</th>
                <th style="font-size: 7px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($balances as $balance)
                <tr>
                    @if (!empty($isBalanceSort))
                        <td class="text-center">{{ $balance['rank'] }}</td>
                    @endif
                    <td class="text-center">{{ $balance['member']->code }}</td>
                    <td>{{ trim($balance['member']->name . ' ' . $balance['member']->postnom . ' ' . $balance['member']->prenom) }}</td>

                    <td class="text-end">{{ $fr($balance['current_usd'], true) }}</td>
                    <td class="text-end">{{ $fr($balance['savings_usd'], true) }}</td>
                    <td class="text-end"><strong>{{ $fr($balance['current_usd'] + $balance['savings_usd'], true) }}</strong></td>

                    <td class="text-end">{{ $fr($balance['current_cdf'], true) }}</td>
                    <td class="text-end">{{ $fr($balance['savings_cdf'], true) }}</td>
                    <td class="text-end"><strong>{{ $fr($balance['current_cdf'] + $balance['savings_cdf'], true) }}</strong></td>
                    @if ($withTotal)
                        <td class="text-end">{{ $fr($balance['total_usd_equiv'], true) }}</td>
                    @endif
                    @if (!empty($isBalanceSort))
                        <td class="text-end">{{ $balance['share'] !== null ? number_format($balance['share'], 2, ',', ' ') . ' %' : '—' }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
        {{-- 3. Sous-total du lot : la somme des lots redonne le total général --}}
        <tfoot>
            <tr style="background: #f5f5f5; font-weight: bold;">
                <td colspan="{{ $colsAvantMontants }}" class="text-end">Sous-total du lot</td>
                <td class="text-end">{{ $fr($balances->sum('current_usd')) }}</td>
                <td class="text-end">{{ $fr($balances->sum('savings_usd')) }}</td>
                <td class="text-end">{{ $fr($balances->sum('current_usd') + $balances->sum('savings_usd')) }}</td>
                <td class="text-end">{{ $fr($balances->sum('current_cdf')) }}</td>
                <td class="text-end">{{ $fr($balances->sum('savings_cdf')) }}</td>
                <td class="text-end">{{ $fr($balances->sum('current_cdf') + $balances->sum('savings_cdf')) }}</td>
                @if ($withTotal)
                    <td class="text-end">{{ $fr($balances->sum('total_usd_equiv')) }}</td>
                @endif
                @if (!empty($isBalanceSort))
                    <td></td>
                @endif
            </tr>
        </tfoot>
    </table>

    {{-- 4. Annexe : comptes à solde négatif (dernier lot seulement) --}}
    @if (!empty($negativeAccounts) && count($negativeAccounts))
        @php $nbNegatifs = collect($recap ?? [])->sum('nb_negatifs'); @endphp
        <h4 style="margin: 14px 0 4px 0;">Annexe — Soldes négatifs à régulariser ({{ $nbNegatifs ?: count($negativeAccounts) }} compte(s))</h4>
        @if ($nbNegatifs > count($negativeAccounts))
            <p style="margin: 0 0 4px 0; font-size: 8px;">
                Les {{ count($negativeAccounts) }} soldes les plus négatifs sont listés. La liste complète est disponible à l'écran
                avec la catégorie « Soldes négatifs à régulariser ».
            </p>
        @endif
        <table class="table" border="1" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Membre</th>
                    <th>Compte</th>
                    <th>Devise</th>
                    <th>Solde</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($negativeAccounts as $acc)
                    <tr>
                        <td class="text-center">{{ $acc->code }}</td>
                        <td>{{ trim($acc->name . ' ' . $acc->postnom . ' ' . $acc->prenom) }}</td>
                        <td class="text-center">{{ ['current' => 'Courant', 'savings' => 'Épargne'][$acc->type] ?? ucfirst($acc->type) }}</td>
                        <td class="text-center">{{ $acc->currency }}</td>
                        <td class="text-end">{{ $fr($acc->balance) }}</td>
                        <td class="text-center">{{ $acc->status }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Rapport généré le {{ now()->format('d/m/Y H:i') }} - {{ $company->name ?? config('app.name') }}
    </div>

</body>

</html>
