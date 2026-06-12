<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Analyse financiere</h5>
        <button wire:click="analyzeWithAI" wire:loading.attr="disabled" class="btn btn-primary">
            <span wire:loading wire:target="analyzeWithAI" class="spinner-border spinner-border-sm me-1" role="status"></span>
            Analyse IA
        </button>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-3">
                <small class="text-muted d-block">Montant demande</small>
                <h5 class="fw-bold mb-0 text-primary">
                    {{ number_format($analysis['loan']['montant_demande'] ?? 0, 2) }} {{ $analysis['loan']['currency'] ?? '' }}
                </h5>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Duree</small>
                <h5 class="fw-bold mb-0">{{ $analysis['loan']['duree_mois'] ?? 0 }} mois</h5>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Activite</small>
                <h5 class="fw-bold mb-0 text-truncate">
                    {{ $analysis['business_profile']['activity'] ?? $analysis['loan']['business']['type_activite'] ?? 'N/A' }}
                </h5>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Statut actuel</small>
                <span class="badge bg-label-secondary">{{ ucfirst($analysis['loan']['statut'] ?? 'N/A') }}</span>
            </div>
        </div>

        @if($aiAnalysis)
            <div class="alert alert-secondary border-primary mb-4">
                <h6 class="text-primary"><i class="bx bxs-magic-wand me-1"></i> Resume analyse IA</h6>
                <div class="white-space-pre-wrap">{!! nl2br(e($aiAnalysis)) !!}</div>
            </div>
        @endif

        @if(isset($analysis['required_fields']))
            @php
                $missingFields = collect($analysis['required_fields'])->filter(fn ($ok) => !$ok)->keys();
            @endphp
            @if($missingFields->isNotEmpty())
                <div class="alert alert-warning">
                    <strong>Analyse incomplete.</strong>
                    Champs requis manquants : {{ $missingFields->map(fn ($field) => ucwords(str_replace('_', ' ', $field)))->join(', ') }}.
                </div>
            @else
                <div class="alert alert-success">
                    Tous les champs requis pour les calculs critiques sont renseignes.
                </div>
            @endif
        @endif

        <div class="row mb-3">
            <div class="col-md-6">
                <h6>Indicateurs cles</h6>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Revenu disponible mensuel
                        <span class="badge bg-primary rounded-pill">
                            {{ number_format($analysis['cashflow_analysis']['available_income'] ?? $analysis['cashflow']['revenu_disponible_mensuel'] ?? 0, 2) }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Capacite de remboursement
                        <span class="badge bg-success rounded-pill">
                            {{ number_format($analysis['cashflow_analysis']['repayment_capacity'] ?? $analysis['cashflow']['capacite_remboursement_mensuelle'] ?? 0, 2) }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Total garanties
                        <span class="badge bg-info rounded-pill">{{ number_format($analysis['securities_total'] ?? 0, 2) }}</span>
                    </li>
                </ul>
            </div>

            <div class="col-md-6">
                <h6>Simulation echeance</h6>
                <div class="input-group mb-3">
                    <input type="number" class="form-control" placeholder="Taux annuel %" wire:keydown.enter="simulateEMI($event.target.value)">
                    <button class="btn btn-outline-secondary" type="button" wire:click="simulateEMI({{ $annual_rate ?? 10 }})">Calculer</button>
                </div>
                @if(isset($analysis['emi']) && is_numeric($analysis['emi']))
                    <div class="alert alert-warning">
                        Mensualite estimee : <strong>{{ number_format((float) $analysis['emi'], 2) }}</strong>
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-4">
            <h6>Bilan</h6>
            <div class="row">
                <div class="col-md-6">
                    <ul class="list-group mb-3">
                        <li class="list-group-item">Cash / Banque :
                            {{ number_format(($analysis['balance_detail']['cash'] ?? 0) + ($analysis['balance_detail']['bank'] ?? 0) + ($analysis['balance_detail']['savings'] ?? 0) ?: ($analysis['balance']['cash'] ?? 0), 2) }}
                        </li>
                        <li class="list-group-item">Creances :
                            {{ number_format($analysis['balance_detail']['receivables'] ?? $analysis['balance']['creances'] ?? 0, 2) }}
                        </li>
                        <li class="list-group-item">Stock :
                            {{ number_format($analysis['balance_detail']['stock'] ?? $analysis['balance']['stock'] ?? 0, 2) }}
                        </li>
                        <li class="list-group-item">Actifs immobilises :
                            {{ number_format(($analysis['balance_detail']['machines_tools'] ?? 0) + ($analysis['balance_detail']['transport_assets'] ?? 0) + ($analysis['balance_detail']['buildings_land'] ?? 0) ?: ($analysis['balance']['actifs_immobilises'] ?? 0), 2) }}
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="list-group mb-3">
                        <li class="list-group-item">Dettes court terme :
                            {{ number_format(($analysis['balance_detail']['supplier_debts'] ?? 0) + ($analysis['balance_detail']['current_customer_credit'] ?? 0) + ($analysis['balance_detail']['short_term_debt'] ?? 0) ?: ($analysis['balance']['dettes_formelles_ct'] ?? 0), 2) }}
                        </li>
                        <li class="list-group-item">Dettes long terme :
                            {{ number_format($analysis['balance_detail']['long_term_debt'] ?? $analysis['balance']['dettes_formelles_lt'] ?? 0, 2) }}
                        </li>
                        <li class="list-group-item">Fonds propres :
                            {{ number_format($analysis['balance_detail']['equity'] ?? $analysis['balance']['fonds_propres'] ?? 0, 2) }}
                        </li>
                        <li class="list-group-item">Total actif :
                            {{ number_format($analysis['balance_detail']['total_assets'] ?? $analysis['balance']['total_actif'] ?? 0, 2) }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <h6>Ratios financiers</h6>
            <ul class="list-group">
                @forelse($analysis['ratios'] ?? [] as $key => $value)
                    @if(!in_array($key, ['id', 'loan_application_id', 'created_at', 'updated_at', 'date_calcul']) && is_numeric($value))
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{ ucwords(str_replace('_', ' ', $key)) }}
                            <span class="badge bg-{{ $this->badgeColor($key, $value) }} rounded-pill">{{ number_format((float) $value, 2) }}</span>
                        </li>
                    @endif
                @empty
                    <li class="list-group-item text-muted">Aucun ratio disponible.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
