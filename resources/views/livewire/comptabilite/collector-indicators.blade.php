<div>

    {{-- FILTRES --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bx bx-filter-alt me-1"></i>
                Filtres des indicateurs
            </h5>
        </div>

        <div class="card-body">

            <div class="row g-3">

                {{-- Agent --}}
                <div class="col-md-4">
                    <label class="form-label">Collecteur</label>

                    <select class="form-select"
                            wire:model.live="agentId">

                        <option value="">
                            Tous les collecteurs
                        </option>

                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}">
                                {{ $agent->name }} {{ $agent->postnom }}
                            </option>
                        @endforeach

                    </select>
                </div>

                {{-- Période --}}
                <div class="col-md-4">
                    <label class="form-label">Période</label>

                    <select class="form-select"
                            wire:model.live="period">

                        <option value="today">Aujourd'hui</option>
                        <option value="week">Cette semaine</option>
                        <option value="month">Ce mois</option>
                        <option value="year">Cette année</option>
                        <option value="custom">Personnalisée</option>

                    </select>
                </div>

                {{-- Date Début --}}
                @if($period === 'custom')
                    <div class="col-md-2">
                        <label class="form-label">
                            Début
                        </label>

                        <input type="date"
                               class="form-control"
                               wire:model.live="startDate">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">
                            Fin
                        </label>

                        <input type="date"
                               class="form-control"
                               wire:model.live="endDate">
                    </div>
                @endif

            </div>

        </div>
    </div>

    {{-- LOADER LIVEWIRE --}}
    <div wire:loading.flex
         class="justify-content-center mb-3">

        <div class="spinner-border text-primary">
        </div>

    </div>


    {{-- CARDS --}}
    <div class="row">

        {{-- TOTAL MEMBRES --}}
        <div class="col-xl-3 col-md-6 col-sm-6 mb-4">

            <div class="card border-start border-primary  h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <span class="fw-medium d-block mb-1">
                                Membres affectés
                            </span>

                            <h3 class="card-title mb-2">
                                {{ number_format($stats['total']) }}
                            </h3>

                        </div>

                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="bx bx-group bx-md"></i>
                            </span>
                        </div>

                    </div>

                    <small class="text-muted">
                        Portefeuille total
                    </small>

                </div>

            </div>

        </div>


        {{-- ACTIFS --}}
        <div class="col-xl-3 col-md-6 col-sm-6 mb-4">

            <div class="card border-start border-success  h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <span class="fw-medium d-block mb-1">
                                Membres actifs

                                <i class="bx bx-info-circle text-muted"
                                    data-bs-toggle="tooltip"
                                    data-bs-placement="top"
                                    title="Membres ayant réalisé au moins une opération durant la période sélectionnée.">
                                </i>
                            </span>

                            <h3 class="card-title text-success mb-2">
                                {{ number_format($stats['active']) }}
                            </h3>

                        </div>

                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="bx bx-user-check bx-md"></i>
                            </span>
                        </div>

                    </div>

                    <small class="text-success fw-semibold">
                        {{ $stats['active_rate'] }} %
                        du portefeuille
                    </small>

                    <div class="progress mt-2">
                        <div class="progress-bar bg-success"
                             style="width: {{ $stats['active_rate'] }}%">
                        </div>
                    </div>

                </div>

            </div>

        </div>


        {{-- A RELANCER --}}
        <div class="col-xl-3 col-md-6 col-sm-6 mb-4">

            <div class="card border-start border-warning  h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <span class="fw-medium d-block mb-1">
                                À relancer
                                <i class="bx bx-info-circle text-muted"
                                    data-bs-toggle="tooltip"
                                    title="Membres sans opération depuis 31 à 90 jours.">
                                </i>
                            </span>

                            <h3 class="card-title text-warning mb-2">
                                {{ number_format($stats['follow']) }}
                            </h3>

                        </div>

                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="bx bx-phone-call bx-md"></i>
                            </span>
                        </div>

                    </div>

                    <small class="text-warning fw-semibold">
                        {{ $stats['follow_rate'] }} %
                        du portefeuille
                    </small>

                    <div class="progress mt-2">
                        <div class="progress-bar bg-warning"
                             style="width: {{ $stats['follow_rate'] }}%">
                        </div>
                    </div>

                </div>

            </div>

        </div>


        {{-- INACTIFS --}}
        <div class="col-xl-3 col-md-6 col-sm-6 mb-4">

            <div class="card border-start border-danger  h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <span class="fw-medium d-block mb-1">
                                Inactifs
                                <i class="bx bx-info-circle text-muted"
                                    data-bs-toggle="tooltip"
                                    title="Membres sans opération depuis plus de 90 jours ou sans aucun mouvement.">
                                </i>
                            </span>

                            <h3 class="card-title text-danger mb-2">
                                {{ number_format($stats['inactive']) }atus
                            </h3>

                        </div>

                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="bx bx-user-x bx-md"></i>
                            </span>
                        </div>

                    </div>

                    <small class="text-danger fw-semibold">
                        {{ $stats['inactive_rate'] }} %
                        du portefeuille
                    </small>

                    <div class="progress mt-2">
                        <div class="progress-bar bg-danger"
                             style="width: {{ $stats['inactive_rate'] }}%">
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- RESUME --}}
    <div class="card">

        <div class="card-header">
            <h5 class="mb-0">
                <i class="bx bx-pie-chart-alt-2 me-1"></i>
                Résumé du portefeuille
            </h5>
        </div>

        <div class="card-body">

            <div class="alert alert-primary mb-0">

                <strong>Synthèse :</strong>

                Sur un total de

                <strong>
                    {{ number_format($stats['total']) }}
                </strong>

                membres,

                <strong class="text-success">
                    {{ $stats['active'] }}
                </strong>

                sont actifs,

                <strong class="text-warning">
                    {{ $stats['follow'] }}
                </strong>

                nécessitent une relance,

                et

                <strong class="text-danger">
                    {{ $stats['inactive'] }}
                </strong>

                sont inactifs.

            </div>

        </div>

    </div>

</div>
