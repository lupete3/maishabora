<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card shadow-sm border-0 rounded-4">
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
                                    'is_good' => fn($v) => true
                                ],
                            ];
                        @endphp

                        @foreach($ratioDefinitions as $key => $def)
                            @php
                                $val = $ratios[$key] ?? 0;
                                $isGood = isset($def['is_good']) ? $def['is_good']($val) : true;
                                $displayVal = number_format($val, 2);
                                if ($def['type'] === 'percentage') $displayVal .= '%';
                                if ($def['type'] === 'currency') $displayVal .= ' ' . $currency;
                            @endphp
                            <tr>
                                <td class="ps-4 pe-2 fw-semibold">{{ $def['name'] }}</td>
                                <td class="small text-muted">{{ $def['formula'] }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $isGood ? 'bg-label-success' : 'bg-label-danger' }} rounded-pill px-3 py-2">
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
