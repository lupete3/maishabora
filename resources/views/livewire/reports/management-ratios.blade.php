<div class="row g-4">
    @php
        $ratioDefinitions = [
            'solvency' => [
                'name' => 'Ratio de solvabilité',
                'formula' => 'Fonds propres / Total actif pondéré',
                'threshold' => '≥10%',
                'interpretation' => 'Capacité à couvrir ses risques',
                'type' => 'percentage',
                'is_good' => fn($v) => $v >= 10
            ],
            'liquidity' => [
                'name' => 'Ratio de liquidité',
                'formula' => 'Actifs liquides / Dépôts à court terme',
                'threshold' => '≥20%',
                'interpretation' => 'Capacité à faire face aux retraits',
                'type' => 'percentage',
                'is_good' => fn($v) => $v >= 20
            ],
            'fixed_asset_coverage' => [
                'name' => 'Couverture des immobilisations',
                'formula' => 'Immobilisations nettes / Fonds propres',
                'threshold' => '≤50%',
                'interpretation' => 'Évite l’immobilisation excessive',
                'type' => 'percentage',
                'is_good' => fn($v) => $v <= 50
            ],
            'fixed_asset_ratio' => [
                'name' => 'Taux d’immobilisation',
                'formula' => 'Immobilisations nettes / Total actif',
                'threshold' => '≤10%',
                'interpretation' => 'Poids des actifs non productifs',
                'type' => 'percentage',
                'is_good' => fn($v) => $v <= 10
            ],
            'long_term_coverage' => [
                'name' => 'Couverture des emplois MLT',
                'formula' => 'Ressources stables / Crédits MLT',
                'threshold' => '≥100%',
                'interpretation' => 'Financement des crédits longs',
                'type' => 'percentage',
                'is_good' => fn($v) => $v >= 100
            ],
            'credit_to_asset_ratio' => [
                'name' => 'Taux d’encours de crédit',
                'formula' => 'Encours total / Total actif',
                'threshold' => '≥70%',
                'interpretation' => 'Utilisation productive des ressources',
                'type' => 'percentage',
                'is_good' => fn($v) => $v >= 70
            ],
            'idle_cash_ratio' => [
                'name' => 'Taux d’encaisse oisive',
                'formula' => 'Trésorerie / Total actif',
                'threshold' => '≤20%',
                'interpretation' => 'Liquidité excessive / rentabilité',
                'type' => 'percentage',
                'is_good' => fn($v) => $v <= 20
            ],
            'par30' => [
                'name' => 'Portefeuille à risque (PAR30)',
                'formula' => 'Retards >30j / Encours total',
                'threshold' => '≤5%',
                'interpretation' => 'Qualité du portefeuille',
                'type' => 'percentage',
                'is_good' => fn($v) => $v <= 5
            ],
            'repayment_rate' => [
                'name' => 'Taux de remboursement',
                'formula' => 'Remboursé / Exigible',
                'threshold' => '≥95%',
                'interpretation' => 'Capacité de remboursement clients',
                'type' => 'percentage',
                'is_good' => fn($v) => $v >= 95
            ],
            'provisioning_rate' => [
                'name' => 'Taux de provisionnement',
                'formula' => 'Provisions / Crédits en retard',
                'threshold' => '≥100%',
                'interpretation' => 'Couverture des pertes potentielles',
                'type' => 'percentage',
                'is_good' => fn($v) => $v >= 100
            ],
            'operational_self_sufficiency' => [
                'name' => 'Autosuffisance opérationnelle',
                'formula' => 'Produits / Charges d’exploitation',
                'threshold' => '≥120%',
                'interpretation' => 'Revenus couvrent les charges',
                'type' => 'percentage',
                'is_good' => fn($v) => $v >= 120
            ],
            'portfolio_yield' => [
                'name' => 'Rendement du portefeuille',
                'formula' => 'Produits fin / Encours moyen',
                'threshold' => '≥15%',
                'interpretation' => 'Rentabilité des crédits',
                'type' => 'percentage',
                'is_good' => fn($v) => $v >= 15
            ],
            'roa' => [
                'name' => 'Rentabilité des actifs (ROA)',
                'formula' => 'Résultat net / Total actif',
                'threshold' => '≥3%',
                'interpretation' => 'Performance globale',
                'type' => 'percentage',
                'is_good' => fn($v) => $v >= 3
            ],
            'roe' => [
                'name' => 'Rentabilité des fonds propres (ROE)',
                'formula' => 'Résultat net / Fonds propres',
                'threshold' => '≥15%',
                'interpretation' => 'Rentabilité pour investisseurs',
                'type' => 'percentage',
                'is_good' => fn($v) => $v >= 15
            ],
            'agent_productivity' => [
                'name' => 'Productivité agents crédit',
                'formula' => 'Emprunteurs / Nb Agents',
                'threshold' => '200 à 300',
                'interpretation' => 'Efficacité du personnel de terrain',
                'type' => 'number',
                'is_good' => fn($v) => $v >= 200
            ],
            'operational_cost' => [
                'name' => 'Coût opérationnel',
                'formula' => 'Charges / Encours moyen',
                'threshold' => '≤15-20%',
                'interpretation' => 'Efficacité opérationnelle',
                'type' => 'percentage',
                'is_good' => fn($v) => $v <= 20
            ],
            'cost_per_borrower' => [
                'name' => 'Coût par emprunteur',
                'formula' => 'Charges / Nb Emprunteurs',
                'threshold' => 'Minimal',
                'interpretation' => 'Coût unitaire par client',
                'type' => 'currency',
                'is_good' => null
            ],
        ];

        $totalEvaluated = 0;
        $goodCount = 0;

        foreach ($ratioDefinitions as $key => $def) {
            if ($def['is_good'] !== null) {
                $totalEvaluated++;
                $val = $ratios[$key] ?? 0;
                if ($def['is_good']($val)) {
                    $goodCount++;
                }
            }
        }

        $healthScore = $totalEvaluated > 0 ? ($goodCount / $totalEvaluated) * 100 : 0;
        
        if ($healthScore >= 80) {
            $healthClass = 'bg-label-success';
            $healthIcon = 'bx-check-shield';
            $healthText = 'Excellente Santé Financière';
            $healthDesc = 'La majorité des indicateurs respectent ou dépassent les normes recommandées. L\'institution est solide.';
        } elseif ($healthScore >= 50) {
            $healthClass = 'bg-label-warning';
            $healthIcon = 'bx-error-circle';
            $healthText = 'Santé Financière Moyenne';
            $healthDesc = 'Plusieurs indicateurs sont en deçà des normes. Une attention particulière est requise sur certains points.';
        } else {
            $healthClass = 'bg-label-danger';
            $healthIcon = 'bx-x-circle';
            $healthText = 'Situation Critique';
            $healthDesc = 'La majorité des ratios sont alarmants. Des mesures correctives urgentes doivent être prises.';
        }
    @endphp

    <!-- Health Summary Card -->
    <div class="col-12 mb-2">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md">
                        <span class="avatar-initial rounded-circle {{ $healthClass }} fs-4">
                            <i class="bx {{ $healthIcon }}"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="mb-1 fw-bold text-dark">Diagnostic Global : {{ $healthText }}</h5>
                        <p class="mb-0 text-muted small">{{ $healthDesc }}</p>
                    </div>
                </div>
                
                <div class="text-end">
                    <h3 class="mb-0 fw-bold {{ str_replace('bg-label-', 'text-', $healthClass) }}">{{ number_format($healthScore, 0) }}%</h3>
                    <span class="small text-muted text-uppercase fw-semibold">Score de Conformité ({{ $goodCount }}/{{ $totalEvaluated }})</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="col-12 col-xl-9">
        <div class="card shadow-sm border-0 rounded-4 h-100">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center rounded-top-4">
                <h5 class="mb-0 fw-bold"><i class="bx bx-bar-chart-alt-2 me-2"></i>Tableau des Ratios de Gestion d'une IMF</h5>
                <div class="d-flex gap-2">
                    <select wire:model.live="currency" class="form-select form-select-sm border-0 shadow-sm" style="width: 100px;">
                        @foreach($currencies as $curr)
                            <option value="{{ $curr }}">{{ $curr }}</option>
                        @endforeach
                    </select>
                    <input type="date" wire:model.live="dateReference" class="form-control form-control-sm border-0 shadow-sm" style="width: 150px;">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4 py-3">Ratio</th>
                                <th class="py-3">Formule de Calcul</th>
                                <th class="py-3 text-center">Valeur Actuelle</th>
                                <th class="py-3 text-center">Seuil / Norme</th>
                                <th class="py-3 pe-4">Interprétation</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ratioDefinitions as $key => $def)
                                @php
                                    $val = $ratios[$key] ?? 0;
                                    $isGood = $def['is_good'] ? $def['is_good']($val) : null;
                                    $displayVal = number_format($val, 2);
                                    if ($def['type'] === 'percentage') $displayVal .= '%';
                                    if ($def['type'] === 'currency') $displayVal .= ' ' . $currency;
                                    
                                    $badgeClass = 'bg-label-secondary'; // default/neutral
                                    if ($isGood === true) $badgeClass = 'bg-label-success';
                                    if ($isGood === false) $badgeClass = 'bg-label-danger';
                                @endphp
                                <tr>
                                    <td class="ps-4 pe-2 fw-semibold">{{ $def['name'] }}</td>
                                    <td class="small text-muted">{{ $def['formula'] }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $badgeClass }} rounded-pill px-3 py-2 fw-bold">
                                            @if($isGood === true) <i class="bx bx-check me-1"></i> 
                                            @elseif($isGood === false) <i class="bx bx-x me-1"></i> 
                                            @endif
                                            {{ $displayVal }}
                                        </span>
                                    </td>
                                    <td class="text-center small fw-semibold text-primary">{{ $def['threshold'] }}</td>
                                    <td class="pe-4 small text-muted">{{ $def['interpretation'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light border-0 py-3 rounded-bottom-4">
                <div class="row g-3 text-center">
                    <div class="col-md-3">
                        <p class="mb-0 small text-muted text-uppercase fw-bold">Total Actif</p>
                        <h5 class="mb-0 fw-bold">{{ number_format($totals['actif'] ?? 0, 2) }} {{ $currency }}</h5>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-0 small text-muted text-uppercase fw-bold">Fonds Propres</p>
                        <h5 class="mb-0 fw-bold text-success">{{ number_format($totals['equity'] ?? 0, 2) }} {{ $currency }}</h5>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-0 small text-muted text-uppercase fw-bold">Encours Crédit</p>
                        <h5 class="mb-0 fw-bold text-primary">{{ number_format($totals['total_portfolio'] ?? 0, 2) }} {{ $currency }}</h5>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-0 small text-muted text-uppercase fw-bold">Résultat Net</p>
                        <h5 class="mb-0 fw-bold {{ ($totals['net_income'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($totals['net_income'] ?? 0, 2) }} {{ $currency }}
                        </h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Sidebar for Legend -->
    <div class="col-12 col-xl-3">
        <div class="card shadow-sm border-0 rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-dark"><i class="bx bx-info-circle text-primary me-2"></i>Légende des couleurs</h6>
            </div>
            <div class="card-body py-4">
                <ul class="list-unstyled mb-0 d-flex flex-column gap-3">
                    <li class="d-flex align-items-center gap-3">
                        <span class="badge bg-label-success p-2 rounded-circle"><i class="bx bx-check"></i></span>
                        <div>
                            <h6 class="mb-0 fw-semibold text-success">Dans les normes</h6>
                            <small class="text-muted text-wrap">L'indicateur respecte la norme recommandée. Situation saine.</small>
                        </div>
                    </li>
                    <li class="d-flex align-items-center gap-3">
                        <span class="badge bg-label-danger p-2 rounded-circle"><i class="bx bx-x"></i></span>
                        <div>
                            <h6 class="mb-0 fw-semibold text-danger">Hors normes (Alerte)</h6>
                            <small class="text-muted text-wrap">L'indicateur ne respecte pas le seuil. Un risque est identifié.</small>
                        </div>
                    </li>
                    <li class="d-flex align-items-center gap-3">
                        <span class="badge bg-label-secondary p-2 rounded-circle"><i class="bx bx-minus"></i></span>
                        <div>
                            <h6 class="mb-0 fw-semibold text-secondary">Informatif</h6>
                            <small class="text-muted text-wrap">L'indicateur n'a pas de norme stricte absolue. À évaluer selon le contexte.</small>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card shadow-sm border-0 rounded-4 bg-primary text-white">
            <div class="card-body">
                <h6 class="fw-bold mb-3 text-white"><i class="bx bxs-bulb me-2 text-warning"></i>À Savoir</h6>
                <p class="small mb-2" style="opacity: 0.85;">Ces indicateurs sont basés sur les standards internationaux des Institutions de Microfinance (CGAP/MIX).</p>
                <p class="small mb-0" style="opacity: 0.85;">Le diagnostic global fournit une image instantanée basée sur des états financiers arrêtés à la date sélectionnée. Les données non saisies en comptabilité ne sont pas prises en compte.</p>
            </div>
        </div>
    </div>
</div>
