@extends('layouts.backend')

@section('title', 'Dossier Crédit #' . $loan->id)

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-2"><span class="text-muted fw-light">Crédit /</span> Dossier #{{ $loan->id }}</h4>

        <div class="row mb-3 g-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-border-shadow-primary h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-2">
                                <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-user"></i></span>
                            </div>
                            <h6 class="ms-1 mb-0 text-truncate">{{ $loan->user->name }} {{ $loan->user->postnom }}</h6>
                        </div>
                        <p class="mb-1">Membre</p>
                        <small class="text-muted">{{ $loan->user->code }}</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-border-shadow-success h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-2">
                                <span class="avatar-initial rounded bg-label-success"><i class="bx bx-dollar"></i></span>
                            </div>
                            <h6 class="ms-1 mb-0">{{ number_format($loan->montant_demande, 0) }} {{ $loan->currency }}</h6>
                        </div>
                        <p class="mb-1">Montant Demandé</p>
                        <small class="text-muted">Crédit sollicité</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-border-shadow-info h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-2">
                                <span class="avatar-initial rounded bg-label-info"><i class="bx bx-calendar"></i></span>
                            </div>
                            <h6 class="ms-1 mb-0">{{ $loan->duree_mois }} Mois</h6>
                        </div>
                        <p class="mb-1">Durée</p>
                        <small class="text-muted">Période remboursement</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-border-shadow-warning h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            @php
                                $badges = [
                                    'en_analyse' => 'warning',
                                    'approuve' => 'success',
                                    'rejete' => 'danger',
                                    'debourse' => 'info',
                                    'cloture' => 'secondary',
                                ];
                                $color = $badges[$loan->statut] ?? 'primary';
                            @endphp
                            <div class="avatar me-2">
                                <span class="avatar-initial rounded bg-label-{{ $color }}"><i class="bx bx-check-shield"></i></span>
                            </div>
                            <h6 class="ms-1 mb-0">{{ ucfirst(str_replace('_', ' ', $loan->statut)) }}</h6>
                        </div>
                        <p class="mb-1">Statut Actuel</p>
                        <small class="text-muted">État du dossier</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4 g-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-border-shadow-danger h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-2">
                                <span class="avatar-initial rounded bg-label-danger"><i class="bx bx-trending-up"></i></span>
                            </div>
                            <h6 class="ms-1 mb-0">{{ number_format($loan->cashflow->capacite_remboursement_mensuelle ?? 0, 0) }} {{ $loan->currency }}</h6>
                        </div>
                        <p class="mb-1">Capacité Remb. (TFR)</p>
                        <small class="text-muted">Mensualité max possible</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-border-shadow-secondary h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-2">
                                <span class="avatar-initial rounded bg-label-secondary"><i class="bx bx-briefcase"></i></span>
                            </div>
                            <h6 class="ms-1 mb-0">{{ number_format($loan->balance->fonds_propres ?? 0, 0) }} {{ $loan->currency }}</h6>
                        </div>
                        <p class="mb-1">Fonds Propres (Bilan)</p>
                        <small class="text-muted">Solvabilité membre</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-border-shadow-info h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-2">
                                <span class="avatar-initial rounded bg-label-info"><i class="bx bx-lock-alt"></i></span>
                            </div>
                            <h6 class="ms-1 mb-0">{{ number_format($loan->securities->sum('valeur_estimee'), 0) }} {{ $loan->currency }}</h6>
                        </div>
                        <p class="mb-1">Total Garanties</p>
                        <small class="text-muted">{{ $loan->securities->count() }} élément(s) nanti(s)</small>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-border-shadow-primary h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar me-2">
                                <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-calculator"></i></span>
                            </div>
                            @php
                                $ratio = $loan->ratios->liquidite_generale ?? 0;
                                $ratioColor = $ratio >= 1 ? 'text-success' : 'text-danger';
                            @endphp
                            <h6 class="ms-1 mb-0 {{ $ratioColor }}">{{ number_format($ratio, 2) }}</h6>
                        </div>
                        <p class="mb-1">Ratio Liquidité</p>
                        <small class="text-muted">Indicateur de santé</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="nav-align-top mb-4">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#navs-top-home"
                                role="tab">Infos</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-top-tfr"
                                role="tab">TFR</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-top-bilan"
                                role="tab">Bilan</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-top-garanties"
                                role="tab">Garanties</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-top-analyse"
                                role="tab">Analyse</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#navs-top-decision"
                                role="tab">Décision</button>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="navs-top-home" role="tabpanel">
                            @livewire('credit.loan-application-create', ['loanApplicationId' => $loan->id])
                        </div>
                        <div class="tab-pane fade" id="navs-top-tfr" role="tabpanel">
                            @livewire('credit.loan-cashflow-editor', ['loan_application_id' => $loan->id])
                        </div>
                        <div class="tab-pane fade" id="navs-top-bilan" role="tabpanel">
                            @livewire('credit.loan-balance-editor', ['loan_application_id' => $loan->id])
                        </div>
                        <div class="tab-pane fade" id="navs-top-garanties" role="tabpanel">
                            @livewire('credit.securities-manager', ['loan_application_id' => $loan->id])
                        </div>
                        <div class="tab-pane fade" id="navs-top-analyse" role="tabpanel">
                            @livewire('credit.loan-analysis-viewer', ['loan_application_id' => $loan->id])
                        </div>
                        <div class="tab-pane fade" id="navs-top-decision" role="tabpanel">
                            @livewire('credit.loan-decision-form', ['loan_application_id' => $loan->id])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection