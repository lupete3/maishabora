@php
    $di = $analysis['decision_indicators'] ?? [];
    $ratios = $analysis['ratios'] ?? [];
    $rec = $di['recommendation'] ?? ['label' => 'N/A', 'color' => 'secondary', 'icon' => 'bxs-info-circle'];
    $alertes = $di['alertes'] ?? [];
    $tauxEffort = $di['taux_effort'] ?? null;
    $couverture = $di['couverture_garanties'] ?? null;
    $score = $di['score_decision'] ?? 0;

    $effortColor = 'success';
    if ($tauxEffort !== null) {
        if ($tauxEffort > 70) $effortColor = 'danger';
        elseif ($tauxEffort > 50) $effortColor = 'warning';
    }

    $couvertureColor = 'danger';
    if ($couverture !== null) {
        if ($couverture >= 150) $couvertureColor = 'success';
        elseif ($couverture >= 100) $couvertureColor = 'info';
        elseif ($couverture >= 80) $couvertureColor = 'warning';
    }

    // Instructions pour chaque ratio
    $ratioLabels = [
        'liquidite_generale' => [
            'label'    => 'Liquidité générale',
            'seuil'    => '≥ 1,5',
            'ok'       => fn($v) => $v >= 1.5,
            'info'     => 'Mesure la capacité à couvrir les dettes court terme avec les actifs circulants.',
            'si_ok'    => 'Bonne couverture des dettes à court terme. L\'entreprise peut faire face à ses engagements immédiats.',
            'si_ko'    => 'Augmenter le stock ou les créances clients, ou réduire les dettes fournisseurs CT. Objectif : actifs circulants ≥ 1,5× les dettes CT.',
        ],
        'fonds_roulement' => [
            'label'    => 'Fonds de roulement',
            'seuil'    => '> 0',
            'ok'       => fn($v) => $v > 0,
            'info'     => 'Excédent des capitaux permanents sur les actifs immobilisés. Un FR positif indique une marge de sécurité.',
            'si_ok'    => 'L\'entreprise dispose d\'une marge de sécurité financière suffisante.',
            'si_ko'    => 'Réduire les immobilisations ou renforcer les fonds propres (apport personnel, bénéfices réinvestis). Le FR doit être positif.',
        ],
        'solvabilite' => [
            'label'    => 'Solvabilité',
            'seuil'    => '≥ 1',
            'ok'       => fn($v) => $v >= 1,
            'info'     => 'Rapport actif total / dettes totales. Indique si l\'entreprise peut rembourser toutes ses dettes.',
            'si_ok'    => 'L\'entreprise peut couvrir l\'ensemble de ses dettes avec ses actifs.',
            'si_ko'    => 'Réduire l\'endettement total ou augmenter les actifs productifs. Actif total doit être ≥ total des dettes.',
        ],
        'independance_financiere' => [
            'label'    => 'Indépendance financière',
            'seuil'    => '≥ 70%',
            'ok'       => fn($v) => $v >= 0.7,
            'info'     => 'Ratio fonds propres / total actif. Plus il est élevé, moins l\'entreprise dépend des créanciers.',
            'si_ok'    => 'L\'entrepreneur finance principalement son activité avec ses propres ressources.',
            'si_ko'    => 'Augmenter les apports personnels ou réinvestir les bénéfices. Objectif : fonds propres ≥ 70% du total actif.',
        ],
        'profitabilite_nette' => [
            'label'    => 'Profitabilité nette',
            'seuil'    => '≥ 10%',
            'ok'       => fn($v) => $v >= 10,
            'info'     => 'Bénéfice net / chiffre d\'affaires × 100. Mesure la rentabilité réelle de l\'activité.',
            'si_ok'    => 'L\'activité génère suffisamment de bénéfices par rapport au chiffre d\'affaires.',
            'si_ko'    => 'Réduire les charges d\'exploitation ou augmenter les prix de vente. Objectif : marge nette ≥ 10% du CA.',
        ],
        'endettement' => [
            'label'    => "Taux d'endettement",
            'seuil'    => '≤ 0,5',
            'ok'       => fn($v) => $v <= 0.5,
            'info'     => 'Dettes totales / fonds propres. Mesure le niveau de levier financier.',
            'si_ok'    => 'L\'endettement est maîtrisé par rapport aux fonds propres.',
            'si_ko'    => 'Réduire les emprunts existants ou augmenter les fonds propres. Le rapport dettes/FP doit rester ≤ 0,5.',
        ],
    ];
@endphp

{{-- ================================================================
     STYLES inline pour les tooltips d'instruction
================================================================ --}}
<style>
.indicator-tip {
    cursor: help;
    border-bottom: 1px dashed #aaa;
}
.guide-badge {
    font-size: 0.68rem;
    padding: 3px 7px;
    border-radius: 20px;
    white-space: normal;
    line-height: 1.4;
}
.instruction-box {
    font-size: 0.78rem;
    border-left: 3px solid;
    padding: 6px 10px;
    border-radius: 4px;
    background: rgba(0,0,0,0.03);
}
.instruction-box.ok  { border-color: #28a745; color: #155724; }
.instruction-box.ko  { border-color: #dc3545; color: #721c24; }
.instruction-box.warn{ border-color: #ffc107; color: #856404; }
</style>

<div>

    {{-- ============================================================
         BLOC 1 : TABLEAU DE BORD DÉCISIONNEL
    ============================================================ --}}
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center py-3"
             style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);">
            <div class="d-flex align-items-center gap-2">
                <i class="bx bxs-dashboard text-warning fs-4"></i>
                <h5 class="mb-0 text-white">Tableau de bord — Aide à la décision</h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning text-dark"
                      data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="left"
                      data-bs-content="Ce tableau synthétise automatiquement tous les indicateurs de risque et donne une recommandation basée sur le taux d'effort, la couverture des garanties, la liquidité et l'indépendance financière."
                      title="Comment fonctionne le score ?">
                    <i class="bx bxs-info-circle me-1"></i>Comment lire ce tableau ?
                </span>
                <button wire:click="analyzeWithAI" wire:loading.attr="disabled" class="btn btn-sm btn-outline-warning">
                    <span wire:loading wire:target="analyzeWithAI" class="spinner-border spinner-border-sm me-1"></span>
                    <i class="bx bxs-magic-wand me-1"></i> Analyse IA
                </button>
            </div>
        </div>

        <div class="card-body p-0">

            {{-- Bandeau recommandation --}}
            @if(!empty($di))
            <div class="alert alert-{{ $rec['color'] }} rounded-0 mb-0 d-flex align-items-center justify-content-between px-4 py-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bx {{ $rec['icon'] }} fs-2"></i>
                    <div>
                        <div class="fw-bold fs-5">Recommandation : {{ $rec['label'] }}</div>
                        <small>
                            Score de décision : <strong>{{ $score }} / 100</strong> —
                            @if($score >= 80) Tous les indicateurs sont dans la bonne marge. Octroi possible.
                            @elseif($score >= 50) Certains indicateurs sont hors seuil. Analyser les alertes avant décision.
                            @else Plusieurs indicateurs critiques sont hors marge. Rejet ou restructuration recommandée.
                            @endif
                        </small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <small class="text-muted">Score global</small>
                    <div class="progress" style="width:120px; height:10px;">
                        <div class="progress-bar bg-{{ $rec['color'] }}" style="width:{{ $score }}%"></div>
                    </div>
                    <strong>{{ $score }}%</strong>
                </div>
            </div>

            {{-- Alertes avec instructions --}}
            @if(count($alertes) > 0)
            <div class="px-4 py-2 bg-light border-bottom">
                <small class="text-muted fw-bold d-block mb-1"><i class="bx bxs-error-circle text-danger me-1"></i>Points d'attention :</small>
                @foreach($alertes as $alerte)
                    <span class="badge bg-danger me-2 mb-1"><i class="bx bx-x-circle me-1"></i>{{ $alerte }}</span>
                @endforeach
            </div>
            @endif

            {{-- Guide de lecture rapide --}}
            <div class="px-4 py-2 border-bottom bg-light d-flex flex-wrap gap-2 align-items-center">
                <small class="text-muted fw-semibold me-2">Légende :</small>
                <span class="badge bg-success guide-badge"><i class="bx bx-check me-1"></i>Dans la marge — Indicateur sain</span>
                <span class="badge bg-warning text-dark guide-badge"><i class="bx bx-error me-1"></i>À surveiller — Hors seuil acceptable</span>
                <span class="badge bg-danger guide-badge"><i class="bx bx-x me-1"></i>Critique — Action corrective requise</span>
                <span class="badge bg-secondary guide-badge"><i class="bx bx-minus me-1"></i>N/A — Donnée non renseignée</span>
            </div>

            {{-- 3 cartes indicateurs principaux --}}
            <div class="row g-0 border-bottom">

                {{-- Mensualité vs Capacité --}}
                <div class="col-md-4 border-end p-4">
                    <div class="d-flex align-items-center gap-1 mb-2">
                        <small class="text-muted indicator-tip"
                               data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                               title="Mensualité vs Capacité de remboursement"
                               data-bs-content="La mensualité proposée par l'agent doit être inférieure à la capacité de remboursement mensuelle du client. Si ce n'est pas le cas, réduire le montant, allonger la durée ou revoir le taux.">
                            <i class="bx bxs-calendar text-primary me-1"></i>Mensualité proposée
                            <i class="bx bxs-help-circle text-muted ms-1" style="font-size:0.8rem;"></i>
                        </small>
                    </div>
                    <h3 class="fw-bold text-primary mb-0 text-center">
                        {{ $di['emi_from_proposal'] > 0 ? number_format($di['emi_from_proposal'], 2) : '—' }}
                    </h3>
                    <div class="text-center"><small class="text-muted">{{ $analysis['loan']['currency'] ?? 'CDF' }}</small></div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">Capacité mensuelle :</small>
                        <span class="fw-semibold {{ ($di['repayment_capacity'] ?? 0) >= ($di['emi_from_proposal'] ?? 0) ? 'text-success' : 'text-danger' }}">
                            {{ number_format($di['repayment_capacity'] ?? 0, 2) }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <small class="text-muted">Solde après remb. :</small>
                        @php $solde = ($di['repayment_capacity'] ?? 0) - ($di['emi_from_proposal'] ?? 0); @endphp
                        <span class="fw-bold {{ $solde >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($solde, 2) }}
                        </span>
                    </div>
                    <div class="mt-2">
                        @if($solde >= 0)
                            <div class="instruction-box ok"><i class="bx bx-check-circle me-1"></i>La capacité couvre la mensualité. Conditions favorables.</div>
                        @else
                            <div class="instruction-box ko"><i class="bx bx-x-circle me-1"></i>Capacité insuffisante. Réduire le montant du crédit ou allonger la durée pour diminuer la mensualité.</div>
                        @endif
                    </div>
                </div>

                {{-- Taux d'effort --}}
                <div class="col-md-4 border-end p-4">
                    <div class="d-flex align-items-center gap-1 mb-2">
                        <small class="text-muted indicator-tip"
                               data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                               title="Taux d'effort"
                               data-bs-content="Le taux d'effort = Mensualité ÷ Capacité de remboursement. Seuils : ≤ 50% = acceptable, 50–70% = élevé (à surveiller), > 70% = critique (refus recommandé). Pour améliorer : réduire la mensualité ou augmenter les revenus nets déclarés.">
                            <i class="bx bxs-tachometer text-warning me-1"></i>Taux d'effort
                            <i class="bx bxs-help-circle text-muted ms-1" style="font-size:0.8rem;"></i>
                        </small>
                    </div>
                    @if($tauxEffort !== null)
                        <h3 class="fw-bold text-{{ $effortColor }} mb-0 text-center">{{ $tauxEffort }} %</h3>
                        <div class="progress my-2" style="height:10px;" title="Taux d'effort : {{ $tauxEffort }}%">
                            <div class="progress-bar bg-{{ $effortColor }}" style="width:{{ min($tauxEffort, 100) }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-success">0%</small>
                            <small class="text-success fw-bold">50%</small>
                            <small class="text-warning fw-bold">70%</small>
                            <small class="text-danger fw-bold">100%</small>
                        </div>
                        @if($tauxEffort <= 50)
                            <div class="instruction-box ok"><i class="bx bx-check-circle me-1"></i>Taux d'effort acceptable (≤ 50%). Le client peut rembourser sans difficultés.</div>
                        @elseif($tauxEffort <= 70)
                            <div class="instruction-box warn"><i class="bx bx-error me-1"></i>Taux élevé (50–70%). Envisager de réduire le montant ou allonger la durée. Surveiller de près.</div>
                        @else
                            <div class="instruction-box ko"><i class="bx bx-x-circle me-1"></i>Taux critique (> 70%). Réduire la mensualité en allongeant la durée ou en diminuant le montant accordé.</div>
                        @endif
                    @else
                        <h3 class="fw-bold text-muted mb-0 text-center">—</h3>
                        <div class="instruction-box warn mt-2"><i class="bx bx-error me-1"></i>Renseigner la proposition de l'agent (montant, taux, durée) et le cashflow (capacité de remboursement) pour calculer ce ratio.</div>
                    @endif
                </div>

                {{-- Couverture garanties --}}
                <div class="col-md-4 p-4">
                    <div class="d-flex align-items-center gap-1 mb-2">
                        <small class="text-muted indicator-tip"
                               data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                               title="Couverture des garanties"
                               data-bs-content="Couverture = Valeur totale des garanties ÷ Montant du crédit proposé × 100. Seuils : ≥ 150% = excellente, 100–149% = suffisante, 80–99% = limite, < 80% = insuffisante. Pour améliorer : ajouter des garanties supplémentaires (hypothèque, nantissement, caution).">
                            <i class="bx bxs-shield text-info me-1"></i>Couverture des garanties
                            <i class="bx bxs-help-circle text-muted ms-1" style="font-size:0.8rem;"></i>
                        </small>
                    </div>
                    @if($couverture !== null)
                        <h3 class="fw-bold text-{{ $couvertureColor }} mb-0 text-center">{{ $couverture }} %</h3>
                        <div class="progress my-2" style="height:10px;">
                            <div class="progress-bar bg-{{ $couvertureColor }}" style="width:{{ min($couverture, 100) }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-danger fw-bold">0%</small>
                            <small class="text-warning fw-bold">80%</small>
                            <small class="text-info fw-bold">100%</small>
                            <small class="text-success fw-bold">150%+</small>
                        </div>
                        @if($couverture >= 150)
                            <div class="instruction-box ok"><i class="bx bx-check-circle me-1"></i>Excellente couverture (≥ 150%). Les garanties couvrent largement le risque.</div>
                        @elseif($couverture >= 100)
                            <div class="instruction-box ok"><i class="bx bx-check-circle me-1"></i>Couverture suffisante (≥ 100%). Le crédit est correctement sécurisé.</div>
                        @elseif($couverture >= 80)
                            <div class="instruction-box warn"><i class="bx bx-error me-1"></i>Couverture limite (80–99%). Demander une garantie complémentaire pour sécuriser davantage.</div>
                        @else
                            <div class="instruction-box ko"><i class="bx bx-x-circle me-1"></i>Couverture insuffisante (< 80%). Ajouter des garanties : hypothèque, nantissement de stock, caution solidaire.</div>
                        @endif
                    @else
                        <h3 class="fw-bold text-muted mb-0 text-center">—</h3>
                        <div class="instruction-box warn mt-2"><i class="bx bx-error me-1"></i>Renseigner les garanties dans l'onglet "Garanties" pour calculer ce taux.</div>
                    @endif
                </div>
            </div>

            {{-- Synthèse du crédit proposé --}}
            <div class="row g-0 border-bottom bg-light">
                <div class="col-12 px-4 py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <h6 class="text-muted text-uppercase mb-0" style="font-size:0.75rem; letter-spacing:1px;">
                            <i class="bx bxs-bank me-1"></i>Synthèse du crédit proposé
                        </h6>
                        <span class="badge bg-label-secondary indicator-tip"
                              data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                              title="Comment lire cette ligne ?"
                              data-bs-content="Le coût du crédit = Total remboursé – Montant accordé. Il représente les intérêts payés sur toute la durée. Un coût élevé indique un taux ou une durée trop longs. Utilisez le simulateur ci-dessous pour tester d'autres conditions.">
                            <i class="bx bxs-help-circle me-1"></i>Aide
                        </span>
                    </div>
                    <div class="row text-center">
                        <div class="col">
                            <div class="text-muted small">Montant proposé</div>
                            <div class="fw-bold">{{ number_format($di['proposed_amount'], 2) }}</div>
                        </div>
                        <div class="col">
                            <div class="text-muted small">Taux annuel</div>
                            <div class="fw-bold">{{ $di['proposed_rate'] > 0 ? $di['proposed_rate'].'%' : 'N/A' }}</div>
                        </div>
                        <div class="col">
                            <div class="text-muted small">Durée</div>
                            <div class="fw-bold">{{ $di['proposed_months'] }} mois</div>
                        </div>
                        <div class="col">
                            <div class="text-muted small">Différé</div>
                            <div class="fw-bold">{{ $di['grace_period'] }} mois</div>
                        </div>
                        <div class="col">
                            <div class="text-muted small">Total remboursable</div>
                            <div class="fw-bold text-danger">{{ number_format($di['total_repayable'], 2) }}</div>
                        </div>
                        <div class="col">
                            <div class="text-muted small indicator-tip"
                                 data-bs-toggle="popover" data-bs-trigger="hover focus"
                                 title="Coût du crédit"
                                 data-bs-content="Intérêts totaux payés = Total remboursé – Montant accordé. Ce montant doit rester raisonnable par rapport au montant demandé. Si trop élevé, négocier un taux inférieur ou réduire la durée.">
                                Coût du crédit <i class="bx bxs-help-circle" style="font-size:0.7rem;"></i>
                            </div>
                            <div class="fw-bold text-warning">{{ number_format($di['cout_credit'], 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if($aiAnalysis)
            <div class="px-4 py-3 border-bottom">
                <h6 class="text-primary mb-2"><i class="bx bxs-magic-wand me-1"></i> Résumé Analyse IA</h6>
                <div class="text-muted" style="white-space:pre-wrap;">{!! nl2br(e($aiAnalysis)) !!}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- ============================================================
         BLOC 2 : RATIOS FINANCIERS AVEC INSTRUCTIONS
    ============================================================ --}}
    <div class="row mb-4">
        <div class="col-md-7">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex align-items-center gap-2">
                    <h6 class="mb-0"><i class="bx bx-bar-chart-alt-2 me-1 text-primary"></i> Ratios financiers</h6>
                    <span class="badge bg-label-primary indicator-tip"
                          data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="right"
                          title="Comment améliorer les ratios ?"
                          data-bs-content="Chaque ratio a un seuil minimal. Survolez le bouton '?' sur chaque ligne pour voir les instructions spécifiques. Les ratios KO doivent être corrigés avant l'octroi ou être justifiés par l'agent.">
                        <i class="bx bxs-help-circle me-1"></i>Instructions
                    </span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Indicateur</th>
                                <th class="text-center">Valeur</th>
                                <th class="text-center">Seuil</th>
                                <th class="text-center">Statut</th>
                                <th class="text-center">Guide</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ratioLabels as $key => $meta)
                                @php $val = isset($ratios[$key]) ? (float) $ratios[$key] : null; @endphp
                                <tr>
                                    <td>
                                        <span class="indicator-tip"
                                              data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                                              title="{{ $meta['label'] }}"
                                              data-bs-content="{{ $meta['info'] }}">
                                            {{ $meta['label'] }}
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold">{{ $val !== null ? number_format($val, 2) : '—' }}</td>
                                    <td class="text-center text-muted small">{{ $meta['seuil'] }}</td>
                                    <td class="text-center">
                                        @if($val !== null)
                                            @if($meta['ok']($val))
                                                <span class="badge bg-label-success"><i class="bx bx-check"></i> OK</span>
                                            @else
                                                <span class="badge bg-label-danger"><i class="bx bx-x"></i> KO</span>
                                            @endif
                                        @else
                                            <span class="badge bg-label-secondary">N/A</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-{{ ($val !== null && $meta['ok']($val)) ? 'success' : ($val !== null ? 'danger' : 'secondary') }}"
                                                style="width:26px;height:26px;padding:0;font-size:0.75rem;"
                                                data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="left"
                                                title="{{ $val !== null ? ($meta['ok']($val) ? '✔ '.$meta['label'] : '✘ '.$meta['label']) : $meta['label'].' — Donnée manquante' }}"
                                                data-bs-content="{{ $val !== null ? ($meta['ok']($val) ? $meta['si_ok'] : $meta['si_ko']) : 'Ce ratio nécessite des données complètes dans le bilan (actif, passif, fonds propres). Compléter le formulaire d\'analyse de terrain.' }}">
                                            <i class="bx bxs-help-circle"></i>
                                        </button>
                                    </td>
                                </tr>
                                {{-- Ligne instruction si KO --}}
                                @if($val !== null && !$meta['ok']($val))
                                <tr class="table-danger" style="font-size:0.78rem;">
                                    <td colspan="5" class="py-1 px-4 text-danger">
                                        <i class="bx bx-right-arrow-alt me-1"></i>{{ $meta['si_ko'] }}
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            {{-- Équilibre du bilan --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom d-flex align-items-center gap-2">
                    <h6 class="mb-0"><i class="bx bx-transfer me-1 text-info"></i> Équilibre du bilan</h6>
                    <span class="badge bg-label-info indicator-tip"
                          data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="right"
                          title="Équilibre du bilan"
                          data-bs-content="L'actif total doit toujours être égal au passif total (Actif = Dettes + Fonds propres). Un écart indique une erreur de saisie dans le bilan. Vérifier toutes les lignes du bilan pour assurer l'équilibre.">
                        <i class="bx bxs-help-circle me-1"></i>Aide
                    </span>
                </div>
                <div class="card-body">
                    @if(!empty($di))
                    <div class="row text-center">
                        <div class="col-5">
                            <div class="text-muted small">ACTIF TOTAL</div>
                            <div class="fw-bold fs-5 text-primary">{{ number_format($di['total_actif'], 2) }}</div>
                        </div>
                        <div class="col-2 d-flex align-items-center justify-content-center">
                            <i class="bx bx-transfer fs-3 text-muted"></i>
                        </div>
                        <div class="col-5">
                            <div class="text-muted small">PASSIF TOTAL</div>
                            <div class="fw-bold fs-5 text-success">{{ number_format($di['total_passif'], 2) }}</div>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center mb-2">
                        @if(abs($di['bilan_ecart']) < 1)
                            <span class="badge bg-success"><i class="bx bx-check-circle me-1"></i>Bilan équilibré</span>
                        @else
                            <span class="badge bg-warning text-dark"><i class="bx bx-info-circle me-1"></i>Écart : {{ number_format($di['bilan_ecart'], 2) }}</span>
                        @endif
                    </div>
                    @if(abs($di['bilan_ecart']) >= 1)
                    <div class="instruction-box ko">
                        <i class="bx bx-x-circle me-1"></i>
                        Le bilan est déséquilibré. Vérifier et corriger les montants dans le formulaire :
                        <ul class="mb-0 ps-3 mt-1">
                            <li>Cash, banque, épargne</li>
                            <li>Créances et stocks</li>
                            <li>Machines, équipements, immeubles</li>
                            <li>Dettes fournisseurs, crédits CT/LT</li>
                            <li>Fonds propres (capital + bénéfices)</li>
                        </ul>
                    </div>
                    @endif
                    @else
                    <p class="text-muted text-center py-2">Données du bilan non disponibles</p>
                    @endif

                    {{-- Champs requis manquants --}}
                    @if(isset($analysis['required_fields']))
                    @php $missing = collect($analysis['required_fields'])->filter(fn($ok) => !$ok)->keys(); @endphp
                    @if($missing->isNotEmpty())
                    <div class="alert alert-warning mt-3 mb-0 py-2">
                        <small>
                            <strong><i class="bx bx-error me-1"></i>Données manquantes à compléter :</strong><br>
                            {{ $missing->map(fn($f) => ucwords(str_replace('_', ' ', $f)))->join(' · ') }}
                        </small>
                    </div>
                    @else
                    <div class="alert alert-success mt-3 mb-0 py-2">
                        <small><i class="bx bx-check-circle me-1"></i>Tous les champs requis sont renseignés</small>
                    </div>
                    @endif
                    @endif
                </div>
            </div>

            {{-- Simulateur --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex align-items-center gap-2">
                    <h6 class="mb-0"><i class="bx bx-calculator me-1 text-warning"></i> Simulateur de mensualité</h6>
                    <span class="badge bg-label-warning indicator-tip"
                          data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                          title="À quoi sert ce simulateur ?"
                          data-bs-content="Entrez un taux annuel pour simuler la mensualité. Comparez le résultat avec la capacité de remboursement. Si la mensualité simulée dépasse la capacité, ajustez le taux, la durée ou le montant accordé.">
                        <i class="bx bxs-help-circle me-1"></i>Aide
                    </span>
                </div>
                <div class="card-body">
                    <div class="input-group">
                        <span class="input-group-text">Taux annuel %</span>
                        <input type="number" class="form-control" placeholder="Ex: 18"
                               wire:keydown.enter="simulateEMI($event.target.value)">
                        <button class="btn btn-warning" type="button"
                                wire:click="simulateEMI({{ $annual_rate ?? 18 }})">
                            Calculer
                        </button>
                    </div>
                    <small class="text-muted d-block mt-1">
                        <i class="bx bxs-info-circle me-1 text-info"></i>
                        Capacité de remboursement : <strong>{{ number_format($di['repayment_capacity'] ?? 0, 2) }} {{ $analysis['loan']['currency'] ?? '' }}</strong>
                    </small>
                    @if(isset($analysis['emi']) && is_numeric($analysis['emi']))
                    @php $simEmi = (float) $analysis['emi']; $capRemb = $di['repayment_capacity'] ?? 0; @endphp
                    <div class="alert alert-{{ $simEmi <= $capRemb ? 'success' : 'danger' }} mt-3 mb-0">
                        <i class="bx bxs-calculator me-1"></i>
                        Mensualité simulée à {{ $annual_rate }}%/an :
                        <strong>{{ number_format($simEmi, 2) }} {{ $analysis['loan']['currency'] ?? '' }}</strong>
                        @if($simEmi <= $capRemb)
                            <div class="mt-1"><small><i class="bx bx-check-circle me-1"></i>Dans la marge — Le client peut rembourser à ce taux.</small></div>
                        @else
                            <div class="mt-1"><small><i class="bx bx-x-circle me-1"></i>Hors marge — Réduire le taux ou le montant accordé.</small></div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         BLOC 3 : CASHFLOW & BILAN DÉTAILLÉ
    ============================================================ --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="bx bx-trending-up me-1 text-success"></i> Flux de trésorerie</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted">Revenu mensuel disponible</td>
                                <td class="text-end fw-bold">{{ number_format($di['revenu_disponible'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Capacité de remboursement</td>
                                <td class="text-end fw-bold text-success">{{ number_format($di['repayment_capacity'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Mensualité proposée</td>
                                <td class="text-end fw-bold text-primary">{{ number_format($di['emi_from_proposal'] ?? 0, 2) }}</td>
                            </tr>
                            @php $solde2 = ($di['repayment_capacity'] ?? 0) - ($di['emi_from_proposal'] ?? 0); @endphp
                            <tr class="table-{{ $solde2 >= 0 ? 'success' : 'danger' }}">
                                <td class="fw-bold">Solde après remboursement</td>
                                <td class="text-end fw-bold">{{ number_format($solde2, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="px-3 py-2">
                        @if($solde2 < 0)
                        <div class="instruction-box ko">
                            <i class="bx bx-x-circle me-1"></i>
                            Solde négatif après remboursement. Actions possibles :
                            <ul class="mb-0 ps-3 mt-1">
                                <li>Réduire le montant accordé</li>
                                <li>Allonger la durée du crédit</li>
                                <li>Négocier un taux plus bas</li>
                                <li>Revoir les charges du foyer déclarées</li>
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="bx bx-building me-1 text-secondary"></i> Bilan simplifié</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr><th>ACTIF</th><th class="text-end">Montant</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-muted">Cash / Banque</td>
                                <td class="text-end">{{ number_format(($analysis['balance_detail']['cash'] ?? 0) + ($analysis['balance_detail']['bank'] ?? 0) + ($analysis['balance_detail']['savings'] ?? 0) ?: ($analysis['balance']['cash'] ?? 0), 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Créances</td>
                                <td class="text-end">{{ number_format($analysis['balance_detail']['receivables'] ?? $analysis['balance']['creances'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Stock</td>
                                <td class="text-end">{{ number_format($analysis['balance_detail']['stock'] ?? $analysis['balance']['stock'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Immobilisations</td>
                                <td class="text-end">{{ number_format(($analysis['balance_detail']['machines_tools'] ?? 0) + ($analysis['balance_detail']['transport_assets'] ?? 0) + ($analysis['balance_detail']['buildings_land'] ?? 0) ?: ($analysis['balance']['actifs_immobilises'] ?? 0), 2) }}</td>
                            </tr>
                        </tbody>
                        <thead class="table-light">
                            <tr><th>PASSIF</th><th class="text-end">Montant</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-muted">Dettes CT</td>
                                <td class="text-end">{{ number_format(($analysis['balance_detail']['supplier_debts'] ?? 0) + ($analysis['balance_detail']['short_term_debt'] ?? 0) ?: ($analysis['balance']['dettes_formelles_ct'] ?? 0), 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Dettes LT</td>
                                <td class="text-end">{{ number_format($analysis['balance_detail']['long_term_debt'] ?? $analysis['balance']['dettes_formelles_lt'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Fonds propres</td>
                                <td class="text-end fw-bold">{{ number_format($analysis['balance_detail']['equity'] ?? $analysis['balance']['fonds_propres'] ?? 0, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Ligne info dossier --}}
    <div class="row mb-2 text-muted">
        <div class="col-md-3">
            <small>Montant demandé</small>
            <div class="fw-bold text-primary">{{ number_format($analysis['loan']['montant_demande'] ?? 0, 2) }} {{ $analysis['loan']['currency'] ?? '' }}</div>
        </div>
        <div class="col-md-3">
            <small>Durée demandée</small>
            <div class="fw-bold">{{ $analysis['loan']['duree_mois'] ?? 0 }} mois</div>
        </div>
        <div class="col-md-3">
            <small>Activité</small>
            <div class="fw-bold text-truncate">{{ $analysis['business_profile']['activity'] ?? $analysis['loan']['business']['type_activite'] ?? 'N/A' }}</div>
        </div>
        <div class="col-md-3">
            <small>Statut dossier</small>
            <span class="badge bg-label-secondary">{{ ucfirst($analysis['loan']['statut'] ?? 'N/A') }}</span>
        </div>
    </div>
</div>

{{-- Activation des popovers Bootstrap --}}
<script>
document.addEventListener('livewire:navigated', () => initPopovers());
document.addEventListener('DOMContentLoaded', () => initPopovers());
function initPopovers() {
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
        if (!el._bsPopover) {
            new bootstrap.Popover(el, { html: false, sanitize: true });
        }
    });
}
</script>
