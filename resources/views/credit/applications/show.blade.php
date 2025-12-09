@extends('layouts.backend')

@section('title', 'Dossier Crédit #' . $id)

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Crédit /</span> Dossier #{{ $id }}</h4>

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
                            @livewire('credit.loan-application-create', ['loanApplicationId' => $id])
                        </div>
                        <div class="tab-pane fade" id="navs-top-tfr" role="tabpanel">
                            @livewire('credit.loan-cashflow-editor', ['loan_application_id' => $id])
                        </div>
                        <div class="tab-pane fade" id="navs-top-bilan" role="tabpanel">
                            @livewire('credit.loan-balance-editor', ['loan_application_id' => $id])
                        </div>
                        <div class="tab-pane fade" id="navs-top-garanties" role="tabpanel">
                            @livewire('credit.securities-manager', ['loan_application_id' => $id])
                        </div>
                        <div class="tab-pane fade" id="navs-top-analyse" role="tabpanel">
                            @livewire('credit.loan-analysis-viewer', ['loan_application_id' => $id])
                        </div>
                        <div class="tab-pane fade" id="navs-top-decision" role="tabpanel">
                            @livewire('credit.loan-decision-form', ['loan_application_id' => $id])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection