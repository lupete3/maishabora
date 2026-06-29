<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dossier Credit #{{ $loan->id }} - Fiche d'analyse remplie</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #333; font-size: 9px; line-height: 1.3; margin: 0; padding: 20px; background: #f9f9f9; }
        .container { max-width: 820px; margin: 0 auto; background: #fff; padding: 28px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,.05); }
        .no-print-bar { max-width: 820px; margin: 0 auto 20px; padding: 10px 20px; border-radius: 6px; background: #1e293b; color: #fff; display: flex; justify-content: space-between; align-items: center; }
        .btn-back { color: #cbd5e1; text-decoration: none; font-size: 11px; }
        .btn-print { background: #eab308; color: #1e293b; border: 0; padding: 6px 15px; font-weight: bold; border-radius: 4px; cursor: pointer; font-size: 10px; text-transform: uppercase; }
        .header-table, .table-custom { width: 100%; border-collapse: collapse; }
        .header-table td { border: 0 !important; padding: 0 !important; }
        .header-logo { width: 80px; height: auto; }
        .header-title { text-align: center; }
        .header-title h2 { margin: 0; font-size: 16px; color: #bd8a12; letter-spacing: .5px; }
        .header-title p { margin: 3px 0 0; font-size: 9px; color: #666; }
        .header-meta { text-align: right; font-size: 9px; color: #555; }
        .divider { border: 0; border-bottom: 2px solid #eab308; margin: 10px 0 15px; }
        .document-title-container { background: #bd8a12; color: #fff; text-align: center; padding: 8px 15px; border-radius: 4px; margin-bottom: 15px; }
        .document-title { margin: 0; font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .section-title { font-size: 10px; font-weight: bold; color: #bd8a12; text-transform: uppercase; border-bottom: 1px solid #bd8a12; padding-bottom: 3px; margin: 16px 0 8px; letter-spacing: .5px; }
        .subsection-title { font-size: 9px; font-weight: bold; color: #333; text-transform: uppercase; margin: 10px 0 5px; }
        .row-grid { display: flex; flex-wrap: wrap; margin-bottom: 8px; }
        .col-3 { width: 25%; box-sizing: border-box; padding-right: 10px; }
        .col-4 { width: 33.33%; box-sizing: border-box; padding-right: 10px; }
        .col-6 { width: 50%; box-sizing: border-box; padding-right: 10px; }
        .col-12 { width: 100%; box-sizing: border-box; }
        .field-box { margin-bottom: 7px; }
        .field-label { font-weight: bold; color: #555; margin-bottom: 3px; font-size: 8px; text-transform: uppercase; }
        .field-value { border: 1px solid #bbb; min-height: 20px; border-radius: 2px; background: #fafafa; padding: 3px 6px; font-size: 9px; }
        .field-value.tall { min-height: 42px; }
        .table-custom { margin-bottom: 11px; }
        .table-custom th, .table-custom td { border: 1px solid #aaa; padding: 4px 6px; text-align: left; vertical-align: top; }
        .table-custom th { background: #f3f4f6; color: #0a802e; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .total-row td { background: #f3f4f6; font-weight: bold; }
        .two-columns { display: flex; gap: 12px; align-items: flex-start; }
        .two-columns > div { width: 50%; }
        .warning { background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; padding: 8px; border-radius: 4px; margin-bottom: 12px; }
        .signatures-container { display: flex; margin-top: 22px; justify-content: space-between; }
        .signature-box { width: 30%; border: 1px dashed #999; border-radius: 4px; padding: 10px; text-align: center; min-height: 75px; box-sizing: border-box; }
        .signature-title { font-weight: bold; font-size: 9px; color: #555; text-transform: uppercase; margin-bottom: 38px; }
        .page-break { page-break-before: always; }
        @media print {
            body { background: #fff; padding: 0; }
            .container { box-shadow: none; padding: 0; max-width: 100%; }
            .no-print-bar { display: none !important; }
            .document-title-container, .table-custom th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    @php
        $visit = $loan->fieldVisit;
        $profile = $loan->businessProfile;
        $cashflow = $loan->cashflowAnalysis;
        $legacyCashflow = $loan->cashflow;
        $balance = $loan->balanceSheetDetail;
        $legacyBalance = $loan->balance;
        $proposal = $loan->agentProposal;
        $ratios = $loan->ratios;
        $businessExpenses = $loan->expenseLines->where('section', 'business');
        $householdExpenses = $loan->expenseLines->where('section', 'household');
        $stockItems = $loan->inventoryItems->where('section', 'stock');
        $fixedAssets = $loan->inventoryItems->where('section', 'fixed_asset');
        $camvItems = $loan->inventoryItems->where('section', 'camv');
        $productionItems = $loan->inventoryItems->where('section', 'production_cost');
        $offBalanceItems = $loan->inventoryItems->where('section', 'off_balance_investment');
        $required = [
            'Date de visite' => filled($visit?->visit_date),
            'Taux USD/CDF si CDF' => $loan->currency !== 'CDF' || (($visit?->usd_cdf_rate ?? 0) > 0),
            'Activite' => filled($profile?->activity),
            'Adresse entreprise' => filled($profile?->full_address),
            'Ventes retenues' => (($cashflow?->retained_sales ?? 0) > 0),
            'Achats retenus' => $cashflow !== null && $cashflow->retained_purchases >= 0,
            'Charges entreprise' => $cashflow !== null && $cashflow->business_expenses_total >= 0,
            'Depenses menage' => $cashflow !== null && $cashflow->household_expenses_total >= 0,
            'Cash' => $balance !== null && $balance->cash >= 0,
            'Stock' => $balance !== null && $balance->stock >= 0,
            'Fonds propres' => (($balance?->equity ?? 0) > 0),
            'Conclusion agent' => filled($proposal?->final_conclusions),
            'Montant propose' => (($proposal?->proposed_amount ?? 0) > 0),
            'Taux propose' => (($proposal?->proposed_rate ?? 0) > 0),
            'Maturite proposee' => (($proposal?->proposed_maturity_months ?? 0) > 0),
        ];
        $missing = collect($required)->filter(fn ($ok) => !$ok)->keys();
        $fmt = fn ($value) => number_format((float) ($value ?? 0), 2) . ' ' . $loan->currency;
    @endphp

    <div class="no-print-bar">
        <a href="{{ route('credit.applications.show', $loan->id) }}" class="btn-back">&larr; Retour au dossier</a>
        <div style="font-weight: bold; font-size: 12px;">Fiche d'analyse remplie - #{{ $loan->id }}</div>
        <button class="btn-print" onclick="window.print()">Imprimer</button>
    </div>

    <div class="container">
        <table class="header-table">
            <tr>
                <td style="width: 15%;">
                    @php
                        $logoPath = public_path('assets/img/logo.jpg');
                        $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
                    @endphp
                    @if($logoData)
                        <img src="data:image/png;base64,{{ $logoData }}" class="header-logo" alt="Logo">
                    @else
                        <div style="font-weight: bold; font-size: 16px; color: #a2840b;">MAISHA BORA</div>
                    @endif
                </td>
                <td style="width: 65%;" class="header-title">
                    <h2>{{ strtoupper($company->name ?? config('app.name', 'MAISHA BORA')) }}</h2>
                    <p>Adresse : {{ $company->address ?? env('APP_ADRESS', 'Goma, RDC') }}</p>
                    <p>Tel : {{ $company->phone ?? env('APP_PHONE', '+243...') }} | Email : {{ $company->email ?? env('APP_EMAIL', 'info@maishabora.com') }}</p>
                    <p>RCCM : {{ $company->rccm ?? env('APP_RCCM', 'RDC/GOM/...') }}</p>
                </td>
                <td style="width: 20%;" class="header-meta">
                    <strong>Dossier :</strong> #{{ $loan->id }}<br>
                    <strong>Date imp. :</strong> {{ now()->format('d/m/Y') }}<br>
                    <strong>Agent :</strong> {{ $loan->agent->name ?? 'N/A' }} {{ $loan->agent->postnom ?? '' }}
                </td>
            </tr>
        </table>

        <hr class="divider">

        <div class="document-title-container">
            <h3 class="document-title">Formulaire d'analyse de credit MPME - rempli</h3>
        </div>

        @if($missing->isNotEmpty())
            <div class="warning">
                <strong>Attention :</strong> cette fiche n'est pas complete pour une analyse fiable.
                Champs requis manquants : {{ $missing->join(', ') }}.
            </div>
        @endif

        <div class="row-grid">
            <div class="col-3"><div class="field-box"><div class="field-label">Date de la visite</div><div class="field-value">{{ optional($visit?->visit_date)->format('d/m/Y') ?? 'Non renseigne' }}</div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Agent de credit</div><div class="field-value">{{ $loan->agent->name ?? 'N/A' }} {{ $loan->agent->postnom ?? '' }}</div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Taux 1 USD = CDF</div><div class="field-value">{{ $visit?->usd_cdf_rate ?? 'N/A' }}</div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">N credit / N client</div><div class="field-value">{{ $visit?->credit_number ?? $loan->id }} / {{ $loan->user->code ?? 'N/A' }}</div></div></div>
            <div class="col-6"><div class="field-box"><div class="field-label">Nom du client</div><div class="field-value">{{ $loan->user->name ?? '' }} {{ $loan->user->postnom ?? '' }} {{ $loan->user->prenom ?? '' }}</div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Monnaie analyse</div><div class="field-value">{{ $loan->currency }}</div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Montant sollicite</div><div class="field-value">{{ $fmt($loan->montant_demande) }}</div></div></div>
        </div>

        <div class="section-title">I. Resume de l'analyse</div>
        <div class="two-columns">
            <div>
                <div class="subsection-title">A. Revenu</div>
                <table class="table-custom">
                    <tbody>
                        <tr><td>Activite</td><td>{{ $profile?->activity ?? $loan->business?->type_activite ?? 'N/A' }}</td></tr>
                        <tr><td>Marge de l'entreprise (%)</td><td>{{ $profile?->business_margin_percent ?? 'N/A' }}</td></tr>
                        <tr><td>Nombre d'employes</td><td>{{ $profile?->employees_count ?? 'N/A' }}</td></tr>
                        <tr><td>Ventes cash</td><td>{{ $fmt($cashflow?->cash_sales) }}</td></tr>
                        <tr><td>Ventes a credit</td><td>{{ $fmt($cashflow?->credit_sales) }}</td></tr>
                        <tr><td>Ventes retenues</td><td>{{ $fmt($cashflow?->retained_sales ?? $legacyCashflow?->chiffre_affaires_mensuel_estime) }}</td></tr>
                        <tr><td>Cout marchandise vendue</td><td>{{ $fmt($cashflow?->retained_purchases ?? $legacyCashflow?->camv_ou_achats_mensuels) }}</td></tr>
                        <tr><td>Charges entreprise</td><td>{{ $fmt($cashflow?->business_expenses_total ?? $legacyCashflow?->charges_activite_mensuelles) }}</td></tr>
                        <tr><td>Benefice / marge brute</td><td>{{ $fmt($cashflow?->gross_margin) }}</td></tr>
                        <tr><td>Revenus du menage</td><td>{{ $fmt($cashflow?->household_income ?? $legacyCashflow?->autres_revenus_mensuels) }}</td></tr>
                        <tr><td>Depenses menage</td><td>{{ $fmt($cashflow?->household_expenses_total) }}</td></tr>
                        <tr class="total-row"><td>Revenu disponible</td><td>{{ $fmt($cashflow?->available_income ?? $legacyCashflow?->revenu_disponible_mensuel) }}</td></tr>
                        <tr class="total-row"><td>Capacite remboursement</td><td>{{ $fmt($cashflow?->repayment_capacity ?? $legacyCashflow?->capacite_remboursement_mensuelle) }}</td></tr>
                    </tbody>
                </table>
            </div>
            <div>
                <div class="subsection-title">B. Bilan</div>
                <table class="table-custom">
                    <tbody>
                        <tr><td>Cash</td><td>{{ $fmt($balance?->cash ?? $legacyBalance?->cash) }}</td></tr>
                        <tr><td>Banque</td><td>{{ $fmt($balance?->bank) }}</td></tr>
                        <tr><td>Epargne</td><td>{{ $fmt($balance?->savings) }}</td></tr>
                        <tr><td>Creances</td><td>{{ $fmt($balance?->receivables ?? $legacyBalance?->creances) }}</td></tr>
                        <tr><td>Avances fournisseurs</td><td>{{ $fmt($balance?->supplier_advances) }}</td></tr>
                        <tr><td>Stock</td><td>{{ $fmt($balance?->stock ?? $legacyBalance?->stock) }}</td></tr>
                        <tr><td>Actifs immobilises</td><td>{{ $fmt(($balance?->machines_tools ?? 0) + ($balance?->transport_assets ?? 0) + ($balance?->buildings_land ?? 0) ?: $legacyBalance?->actifs_immobilises) }}</td></tr>
                        <tr class="total-row"><td>Total actif</td><td>{{ $fmt($balance?->total_assets ?? $legacyBalance?->total_actif) }}</td></tr>
                        <tr><td>Dettes fournisseurs</td><td>{{ $fmt($balance?->supplier_debts) }}</td></tr>
                        <tr><td>Credit client en cours</td><td>{{ $fmt($balance?->current_customer_credit) }}</td></tr>
                        <tr><td>Dettes court terme</td><td>{{ $fmt($balance?->short_term_debt ?? $legacyBalance?->dettes_formelles_ct) }}</td></tr>
                        <tr><td>Dettes long terme</td><td>{{ $fmt($balance?->long_term_debt ?? $legacyBalance?->dettes_formelles_lt) }}</td></tr>
                        <tr><td>Fonds propres</td><td>{{ $fmt($balance?->equity ?? $legacyBalance?->fonds_propres) }}</td></tr>
                        <tr class="total-row"><td>Total passif</td><td>{{ $fmt($balance?->total_liabilities_equity ?? $legacyBalance?->total_passif) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="subsection-title">C. Destination du credit et plan d'investissement</div>
        <table class="table-custom">
            <thead><tr><th>Destination</th><th>Montant</th><th>Debut</th><th>Fin</th><th>Part client</th><th>Part MAISHA BORA</th><th>Part tiers</th></tr></thead>
            <tbody>
                @forelse($loan->investmentPlanItems as $item)
                    <tr>
                        <td>{{ $item->destination }}</td>
                        <td>{{ $fmt($item->amount) }}</td>
                        <td>{{ optional($item->starts_on)->format('d/m/Y') }}</td>
                        <td>{{ optional($item->ends_on)->format('d/m/Y') }}</td>
                        <td>{{ $fmt($item->client_share) }}</td>
                        <td>{{ $fmt($item->institution_share) }}</td>
                        <td>{{ $fmt($item->third_party_share) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">Aucun plan d'investissement renseigne.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="subsection-title">D. Ratios financiers (calculés par le système)</div>
        <table class="table-custom" style="width: 100%;">
            <thead>
                <tr>
                    <th>Fonds de roulement</th>
                    <th>Liquidité générale</th>
                    <th>Solvabilité</th>
                    <th>Indépendance financière</th>
                    <th>Profitabilité nette</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $ratios?->fonds_roulement ? number_format($ratios->fonds_roulement, 2) . ' ' . $loan->currency : 'N/A' }}</td>
                    <td>{{ $ratios?->liquidite_generale ? number_format($ratios->liquidite_generale, 3) : 'N/A' }}</td>
                    <td>{{ $ratios?->solvabilite ? number_format($ratios->solvabilite, 3) : 'N/A' }}</td>
                    <td>{{ $ratios?->independance_financiere ? number_format($ratios->independance_financiere * 100, 2) . ' %' : 'N/A' }}</td>
                    <td>{{ $ratios?->profitabilite_nette ? number_format($ratios->profitabilite_nette, 2) . ' %' : 'N/A' }}</td>
                </tr>
            </tbody>
        </table>

        <div class="page-break"></div>

        <div class="section-title">II. Informations sur le menage</div>
        <div class="row-grid">
            <div class="col-4"><div class="field-box"><div class="field-label">Nom</div><div class="field-value">{{ $loan->user->name ?? '' }}</div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Post-nom</div><div class="field-value">{{ $loan->user->postnom ?? '' }}</div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Prenom</div><div class="field-value">{{ $loan->user->prenom ?? '' }}</div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Province origine</div><div class="field-value">{{ $visit?->origin_province ?? 'N/A' }}</div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Niveau education</div><div class="field-value">{{ $visit?->education_level ?? 'N/A' }}</div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Religion</div><div class="field-value">{{ $visit?->religion ?? 'N/A' }}</div></div></div>
            <div class="col-12"><div class="field-box"><div class="field-label">Biographie rapide</div><div class="field-value tall">{{ $visit?->quick_biography ?? 'N/A' }}</div></div></div>
        </div>

        <div class="subsection-title">Structure familiale</div>
        <table class="table-custom">
            <thead><tr><th>Nom</th><th>Lien</th><th>Occupation</th><th>Observations</th></tr></thead>
            <tbody>
                @forelse($loan->familyMembers as $member)
                    <tr><td>{{ $member->name }}</td><td>{{ $member->relationship }}</td><td>{{ $member->occupation }}</td><td>{{ $member->observations }}</td></tr>
                @empty
                    <tr><td colspan="4">Structure familiale non renseignee.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="subsection-title">Habitation</div>
        <div class="row-grid">
            <div class="col-3"><div class="field-box"><div class="field-label">Type habitation</div><div class="field-value">{{ $visit?->housing_type ?? 'N/A' }}</div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Valeur</div><div class="field-value">{{ $fmt($visit?->housing_value) }}</div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Residence</div><div class="field-value">{{ $visit?->residence_duration ?? 'N/A' }}</div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Loyer</div><div class="field-value">{{ $fmt($visit?->monthly_rent) }}</div></div></div>
            <div class="col-6"><div class="field-box"><div class="field-label">Explications domicile</div><div class="field-value tall">{{ $visit?->home_directions ?? 'N/A' }}</div></div></div>
            <div class="col-6"><div class="field-box"><div class="field-label">Impressions menage</div><div class="field-value tall">{{ $visit?->household_impressions ?? 'N/A' }}</div></div></div>
        </div>

        <div class="section-title">III. References du menage</div>
        <table class="table-custom">
            <thead><tr><th>Nom</th><th>Adresse</th><th>Type</th><th>Telephone</th></tr></thead>
            <tbody>
                @forelse($loan->householdReferences as $reference)
                    <tr><td>{{ $reference->name }}</td><td>{{ $reference->address }}</td><td>{{ ucfirst($reference->reference_type) }}</td><td>{{ $reference->phone }}</td></tr>
                @empty
                    <tr><td colspan="4">Aucune reference menage renseignee.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">IV. Informations sur l'activite</div>
        <table class="table-custom">
            <tbody>
                <tr><td style="width: 25%;">Nom</td><td>{{ $profile?->business_name ?? $loan->business?->type_activite ?? 'N/A' }}</td></tr>
                <tr><td>Activite</td><td>{{ $profile?->activity ?? $loan->business?->type_activite ?? 'N/A' }}</td></tr>
                <tr><td>Adresse complete</td><td>{{ $profile?->full_address ?? $loan->business?->localisation ?? 'N/A' }}</td></tr>
                <tr><td>Depuis</td><td>{{ optional($profile?->started_at)->format('d/m/Y') ?? 'N/A' }}</td></tr>
                <tr><td>Historique</td><td>{{ $profile?->business_history ?? 'N/A' }}</td></tr>
                <tr><td>Observations qualitatives</td><td>{{ $profile?->qualitative_observations ?? 'N/A' }}</td></tr>
                <tr><td>Commentaires achat/credit/concurrence</td><td>{{ $profile?->purchase_sales_competition_comments ?? 'N/A' }}</td></tr>
            </tbody>
        </table>

        <div class="subsection-title">Historique de credit</div>
        <table class="table-custom">
            <thead><tr><th>Institution</th><th>Montant</th><th>Statut</th><th>Observations</th></tr></thead>
            <tbody>
                @forelse($loan->creditHistories as $history)
                    <tr><td>{{ $history->institution }}</td><td>{{ $fmt($history->amount) }}</td><td>{{ $history->status }}</td><td>{{ $history->observations }}</td></tr>
                @empty
                    <tr><td colspan="4">Aucun historique de credit renseigne.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="page-break"></div>

        <div class="section-title">V. Details du bilan</div>
        <div class="two-columns">
            <div>
                <div class="subsection-title">Details stock</div>
                <table class="table-custom">
                    <thead><tr><th>Description</th><th>Qte</th><th>Montant</th><th>Obs.</th></tr></thead>
                    <tbody>
                        @forelse($stockItems as $item)
                            <tr><td>{{ $item->description }}</td><td>{{ $item->quantity }}</td><td>{{ $fmt($item->amount) }}</td><td>{{ $item->observations }}</td></tr>
                        @empty
                            <tr><td colspan="4">Aucun detail stock.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>
                <div class="subsection-title">Immobilises</div>
                <table class="table-custom">
                    <thead><tr><th>Description</th><th>Qte</th><th>Montant</th><th>Obs.</th></tr></thead>
                    <tbody>
                        @forelse($fixedAssets as $item)
                            <tr><td>{{ $item->description }}</td><td>{{ $item->quantity }}</td><td>{{ $fmt($item->amount) }}</td><td>{{ $item->observations }}</td></tr>
                        @empty
                            <tr><td colspan="4">Aucun immobilise detaille.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="subsection-title">Investissement non inclus dans le bilan</div>
        <table class="table-custom">
            <thead><tr><th>Description</th><th>Montant</th><th>Observations</th></tr></thead>
            <tbody>
                @forelse($offBalanceItems as $item)
                    <tr><td>{{ $item->description }}</td><td>{{ $fmt($item->amount) }}</td><td>{{ $item->observations }}</td></tr>
                @empty
                    <tr><td colspan="3">Aucun investissement hors bilan.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">VI. TFR detaille</div>
        <div class="subsection-title">Calcul CAMV</div>
        <table class="table-custom">
            <thead><tr><th>Article</th><th>PA</th><th>PV</th><th>Qte</th><th>Montant</th><th>Observations</th></tr></thead>
            <tbody>
                @forelse($camvItems as $item)
                    <tr><td>{{ $item->description }}</td><td>{{ $fmt($item->purchase_price) }}</td><td>{{ $fmt($item->sale_price) }}</td><td>{{ $item->quantity }}</td><td>{{ $fmt($item->amount) }}</td><td>{{ $item->observations }}</td></tr>
                @empty
                    <tr><td colspan="6">Aucun detail CAMV renseigne.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">VII. Charges et revenu du menage</div>
        <div class="two-columns">
            <div>
                <div class="subsection-title">Charges entreprise</div>
                <table class="table-custom">
                    <tbody>
                        @forelse($businessExpenses as $line)
                            <tr><td>{{ $line->label }}</td><td>{{ $fmt($line->amount) }}</td></tr>
                        @empty
                            <tr><td colspan="2">Charges detaillees non renseignees.</td></tr>
                        @endforelse
                        <tr class="total-row"><td>Total charges entreprise</td><td>{{ $fmt($cashflow?->business_expenses_total) }}</td></tr>
                    </tbody>
                </table>
            </div>
            <div>
                <div class="subsection-title">Depenses menage</div>
                <table class="table-custom">
                    <tbody>
                        @forelse($householdExpenses as $line)
                            <tr><td>{{ $line->label }}</td><td>{{ $fmt($line->amount) }}</td></tr>
                        @empty
                            <tr><td colspan="2">Depenses detaillees non renseignees.</td></tr>
                        @endforelse
                        <tr><td>Marge securite 10%</td><td>{{ $fmt($cashflow?->household_safety_margin) }}</td></tr>
                        <tr class="total-row"><td>Total depenses menage</td><td>{{ $fmt($cashflow?->household_expenses_total) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row-grid">
            <div class="col-4"><div class="field-box"><div class="field-label">Source revenu menage</div><div class="field-value">{{ $cashflow?->household_income_source ?? 'N/A' }}</div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Montant</div><div class="field-value">{{ $fmt($cashflow?->household_income) }}</div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Periodicite</div><div class="field-value">{{ $cashflow?->household_income_periodicity ?? 'N/A' }}</div></div></div>
            <div class="col-12"><div class="field-box"><div class="field-label">Commentaires</div><div class="field-value tall">{{ $cashflow?->comments ?? 'N/A' }}</div></div></div>
        </div>

        <div class="subsection-title">Calcul du cout de production</div>
        <table class="table-custom">
            <thead><tr><th>Produit</th><th>PA / cout</th><th>PV</th><th>Qte</th><th>Montant</th><th>Observations</th></tr></thead>
            <tbody>
                @forelse($productionItems as $item)
                    <tr><td>{{ $item->description }}</td><td>{{ $fmt($item->purchase_price) }}</td><td>{{ $fmt($item->sale_price) }}</td><td>{{ $item->quantity }}</td><td>{{ $fmt($item->amount) }}</td><td>{{ $item->observations }}</td></tr>
                @empty
                    <tr><td colspan="6">Aucun cout de production renseigne.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">VIII. Detail des garanties</div>
        <table class="table-custom">
            <thead><tr><th>Nature</th><th>Description</th><th>Valeur</th><th>Proprietaire</th></tr></thead>
            <tbody>
                @forelse($loan->securities as $security)
                    <tr><td>{{ $security->nature_bien ?? $security->type }}</td><td>{{ $security->description }}</td><td>{{ $fmt($security->valeur_estimee) }}</td><td>{{ $security->proprietaire }}</td></tr>
                @empty
                    <tr><td colspan="4">Aucune garantie enregistree.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="row-grid">
            <div class="col-4"><div class="field-box"><div class="field-label">Proprietaire immeuble</div><div class="field-value">{{ $loan->collateralProperty?->owner_name ?? 'N/A' }}</div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Type document</div><div class="field-value">{{ $loan->collateralProperty?->document_type ?? 'N/A' }}</div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Valeur marchande</div><div class="field-value">{{ $fmt($loan->collateralProperty?->market_value) }}</div></div></div>
            <div class="col-12"><div class="field-box"><div class="field-label">References / adresse</div><div class="field-value tall">{{ $loan->collateralProperty?->address_references ?? 'N/A' }}</div></div></div>
        </div>

        <div class="subsection-title">Informations sur le codebiteur</div>
        <div class="row-grid">
            <div class="col-4"><div class="field-box"><div class="field-label">Nom</div><div class="field-value">{{ $loan->coBorrower?->name ?? 'N/A' }}</div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Post-nom / Prenom</div><div class="field-value">{{ $loan->coBorrower?->postnom_prenom ?? 'N/A' }}</div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Telephone</div><div class="field-value">{{ $loan->coBorrower?->phone ?? 'N/A' }}</div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Piece identite</div><div class="field-value">{{ $loan->coBorrower?->identity_document ?? 'N/A' }}</div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Occupation</div><div class="field-value">{{ $loan->coBorrower?->occupation ?? 'N/A' }}</div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Revenu</div><div class="field-value">{{ $fmt($loan->coBorrower?->income) }}</div></div></div>
            <div class="col-6"><div class="field-box"><div class="field-label">Adresse</div><div class="field-value">{{ $loan->coBorrower?->address ?? 'N/A' }}</div></div></div>
            <div class="col-6"><div class="field-box"><div class="field-label">Relation</div><div class="field-value">{{ $loan->coBorrower?->relationship ?? 'N/A' }}</div></div></div>
        </div>

        <div class="section-title">IX. Proposition de l'agent de credit</div>
        <div class="field-box"><div class="field-label">Conclusions finales</div><div class="field-value tall">{{ $proposal?->final_conclusions ?? 'N/A' }}</div></div>
        <div class="row-grid">
            <div class="col-3"><div class="field-box"><div class="field-label">Montant du credit</div><div class="field-value">{{ $fmt($proposal?->proposed_amount) }}</div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Taux</div><div class="field-value">{{ $proposal?->proposed_rate ?? 'N/A' }}%</div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Maturite</div><div class="field-value">{{ $proposal?->proposed_maturity_months ?? 'N/A' }} mois</div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Periode grace</div><div class="field-value">{{ $proposal?->grace_period_months ?? 0 }} mois</div></div></div>
            <div class="col-6"><div class="field-box"><div class="field-label">Modalites remboursement</div><div class="field-value">{{ $proposal?->repayment_modality ?? 'N/A' }}</div></div></div>
            <div class="col-6"><div class="field-box"><div class="field-label">Paiement irregulier</div><div class="field-value">{{ $proposal?->irregular_payment_explanation ?? 'N/A' }}</div></div></div>
        </div>

        @php
            $proposedEmi = 0;
            $tauxEffort = null;
            if ($proposal && $proposal->proposed_maturity_months > 0) {
                $principal = $proposal->proposed_amount;
                $months = $proposal->proposed_maturity_months;
                $rate = $proposal->proposed_rate;
                if ($rate <= 0) {
                    $proposedEmi = $principal / $months;
                } else {
                    $monthlyRate = ($rate / 100) / 12;
                    $denominator = pow(1 + $monthlyRate, $months) - 1;
                    if ($denominator > 0) {
                        $proposedEmi = ($principal * $monthlyRate * pow(1 + $monthlyRate, $months)) / $denominator;
                    }
                }

                $capacity = $cashflow?->repayment_capacity ?? $legacyCashflow?->capacite_remboursement_mensuelle ?? 0;
                if ($capacity > 0) {
                    $tauxEffort = ($proposedEmi / $capacity) * 100;
                }
            }

            $totalSecurities = $loan->securities->sum('valeur_estimee') ?: 0;
            $coverageRatio = null;
            if ($proposal && $proposal->proposed_amount > 0) {
                $coverageRatio = ($totalSecurities / $proposal->proposed_amount) * 100;
            }
        @endphp
        <div class="subsection-title" style="color: #14532d; margin-top: 10px;">Indicateurs d'aide à la décision</div>
        <div class="row-grid" style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 8px; border-radius: 4px;">
            <div class="col-4"><div class="field-box"><div class="field-label" style="color: #14532d;">Mensualité simulée (EMI)</div><div class="field-value" style="font-weight: bold; background: #fff;">{{ $fmt($proposedEmi) }}</div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label" style="color: #14532d;">Taux d'effort (Mensualité / Capacité)</div><div class="field-value" style="font-weight: bold; background: #fff; color: {{ $tauxEffort > 80 ? '#b91c1c' : '#14532d' }};">{{ $tauxEffort ? number_format($tauxEffort, 2) . ' %' : 'N/A' }}</div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label" style="color: #14532d;">Couverture des garanties</div><div class="field-value" style="font-weight: bold; background: #fff;">{{ $coverageRatio ? number_format($coverageRatio, 2) . ' %' : 'N/A' }}</div></div></div>
        </div>

        <p style="font-size: 9px; margin-top: 18px;">
            Le soussigne certifie avoir verifie les documents, evaluations et garanties associes au dossier.
        </p>

        <div class="signatures-container">
            <div class="signature-box"><div class="signature-title">L'agent de credit</div><div style="font-size: 8px; color: #666;">Nom, signature et date</div></div>
            <div class="signature-box"><div class="signature-title">L'emprunteur</div><div style="font-size: 8px; color: #666;">Signature</div></div>
            <div class="signature-box"><div class="signature-title">Le comite de credit</div><div style="font-size: 8px; color: #666;">Avis et visa</div></div>
        </div>
    </div>
</body>
</html>
