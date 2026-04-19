<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Analyse Financière</h5>
        <button wire:click="analyzeWithAI" wire:loading.attr="disabled" class="btn btn-primary">
            <span wire:loading wire:target="analyzeWithAI" class="spinner-border spinner-border-sm me-1"
                role="status"></span>
            ✨ Analyse IA
        </button>
    </div>
    <div class="card-body">

        <!-- Résumé du Dossier -->
        <div class="row mb-4">
            <div class="col-md-3">
                <small class="text-muted d-block">Montant Demandé</small>
                <h5 class="fw-bold mb-0 text-primary">{{ number_format($analysis['loan']['montant_demande'] ?? 0, 2) }}
                    {{ $analysis['loan']['currency'] ?? '' }}
                </h5>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Durée</small>
                <h5 class="fw-bold mb-0">{{ $analysis['loan']['duree_mois'] ?? 0 }} mois</h5>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Business</small>
                <h5 class="fw-bold mb-0 text-truncate">{{ $analysis['loan']['business']['type_activite'] ?? 'N/A' }}
                </h5>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Statut Actuel</small>
                <span class="badge bg-label-secondary">{{ ucfirst($analysis['loan']['statut'] ?? 'N/A') }}</span>
            </div>
        </div>

        @if($aiAnalysis)
            <div class="alert alert-secondary border-primary mb-4">
                <h6 class="text-primary"><i class="bx bxs-magic-wand me-1"></i> Résumé Analyse IA</h6>
                <div class="white-space-pre-wrap">
                    {!! nl2br(e($aiAnalysis)) !!}
                </div>
            </div>
        @endif

        <!-- Indicateurs Clés -->
        <div class="row mb-3">
            <div class="col-md-6">
                <h6>Indicateurs Clés</h6>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Revenu Disponible Mensuel
                        <span class="badge bg-primary rounded-pill">
                            {{ number_format($analysis['cashflow']['revenu_disponible_mensuel'] ?? 0, 2) }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Capacité de Remboursement
                        <span class="badge bg-success rounded-pill">
                            {{ number_format($analysis['cashflow']['capacite_remboursement_mensuelle'] ?? 0, 2) }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Total Garanties
                        <span class="badge bg-info rounded-pill">
                            {{ number_format($analysis['securities_total'] ?? 0, 2) }}
                        </span>
                    </li>
                </ul>
            </div>

            <!-- Simulation EMI -->
            <div class="col-md-6">
                <h6>Simulation Échéance</h6>
                <div class="input-group mb-3">
                    <input type="number" class="form-control" placeholder="Taux Annuel %"
                        wire:keydown.enter="simulateEMI($event.target.value)">
                    <button class="btn btn-outline-secondary" type="button"
                        wire:click="simulateEMI({{ $annual_rate ?? 10 }})">Calculer</button>
                </div>
                @if(isset($analysis['emi']) && is_numeric($analysis['emi']))
                    <div class="alert alert-warning">
                        Mensualité Estimée : <strong>{{ number_format((float) $analysis['emi'], 2) }}</strong>
                    </div>
                @endif
            </div>
        </div>

        <!-- Bilan -->
        <div class="mt-4">
            <h6>Bilan Simplifié</h6>
            <div class="row">
                <div class="col-md-6">
                    <ul class="list-group mb-3">
                        <li class="list-group-item">Cash / Banque :
                            {{ number_format($analysis['balance']['cash'] ?? 0, 2) }}
                        </li>
                        <li class="list-group-item">Créances Clients :
                            {{ number_format($analysis['balance']['creances'] ?? 0, 2) }}
                        </li>
                        <li class="list-group-item">Stock : {{ number_format($analysis['balance']['stock'] ?? 0, 2) }}
                        </li>
                        <li class="list-group-item">Actifs Immobilisés :
                            {{ number_format($analysis['balance']['actifs_immobilises'] ?? 0, 2) }}
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="list-group mb-3">
                        <li class="list-group-item">Dettes Formelles CT :
                            {{ number_format($analysis['balance']['dettes_formelles_ct'] ?? 0, 2) }}
                        </li>
                        <li class="list-group-item">Dettes Formelles MT :
                            {{ number_format($analysis['balance']['dettes_formelles_mt'] ?? 0, 2) }}
                        </li>
                        <li class="list-group-item">Dettes Formelles LT :
                            {{ number_format($analysis['balance']['dettes_formelles_lt'] ?? 0, 2) }}
                        </li>
                        <li class="list-group-item">Fonds Propres :
                            {{ number_format($analysis['balance']['fonds_propres'] ?? 0, 2) }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Ratios -->
        <div class="mt-4">
            <h6>Ratios Financiers</h6>
            <ul class="list-group">
                @foreach($analysis['ratios'] ?? [] as $key => $value)
                    @if(!in_array($key, ['id', 'loan_application_id', 'created_at', 'updated_at', 'date_calcul']) && is_numeric($value))
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{ ucwords(str_replace('_', ' ', $key)) }}
                            <span class="badge bg-{{ $this->badgeColor($key, $value) }} rounded-pill">
                                {{ number_format((float) $value, 2) }}
                            </span>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>

        <!-- Décision 5C -->
        <div class="mt-4">
            <h6>Décision du Comité (5C)</h6>
            @if(isset($analysis['5C']))
                <ul class="list-group">
                    @foreach($analysis['5C'] as $key => $value)
                        @if(!in_array($key, ['commentaire_global', 'decision_finale']))
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ ucwords($key) }}
                                <span class="badge bg-secondary rounded-pill">{{ $value }}</span>
                            </li>
                        @endif
                    @endforeach
                </ul>
                <div class="mt-2">
                    <strong>Commentaire : </strong>{{ $analysis['5C']['commentaire_global'] ?? '' }}<br>
                    <strong>Décision : </strong>
                    <span
                        class="badge bg-{{ ($analysis['5C']['decision_finale'] ?? '') === 'approuvé' ? 'success' : 'danger' }}">
                        {{ ucfirst($analysis['5C']['decision_finale'] ?? '') }}
                    </span>
                </div>
            @endif
        </div>

    </div>
</div>
