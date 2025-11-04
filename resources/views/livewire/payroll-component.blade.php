<div class="mt-0">
    <div class="row mb-4">
        <div class="col">
            <h3 class="text-primary"><i class="bx bx-wallet me-2"></i>Gestion de la Paie</h3>
            <p class="mb-0">Attribuer et payer les salaires fixes aux agents.</p>
        </div>
    </div>

    <div class="row mb-4">
    {{-- ATTRIBUTION DU SALAIRE FIXE --}}
    <div class="col-md-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-primary">
                    <i class="bx bx-wallet me-2"></i>
                    Attribution de Salaire à un Agent
                </h5>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    {{-- Agent --}}

                    <div class="col-md-12">
                        <div class="position-relative">
                            <label class="form-label fw-bold">Agent</label>
                            <div class="table-search-input">
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text" id="basic-addon-search31"><i
                                            class="icon-base bx bx-search"></i></span>
                                    <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                                        placeholder="Rechercher Agent....." aria-label="Rechercher Agent....."
                                        aria-describedby="basic-addon-search31">
                                </div>
                            </div>

                            @if (!empty($results))
                                <ul class="list-group w-100" style="z-index: 1000;">
                                    @foreach ($results as $user)
                                        <li class="list-group-item list-group-item-action"
                                            wire:click="selectResult({{ $user['id'] }})">
                                            {{ "{$user['code']} {$user['name']} {$user['postnom']}" }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            @error('user_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror

                        </div>
                    </div>

                    {{-- Devise --}}
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Devise</label>
                        <select class="form-select" wire:model="currency">
                            <option value="CDF">CDF</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>

                    {{-- Montant --}}
                    <div class="col-md-13">
                        <label class="form-label fw-bold">Montant</label>
                        <input type="number" step="0.01" class="form-control" wire:model="salary_amount" placeholder="Montant du salaire">
                        @error('salary_amount') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Bouton --}}
                    <div class="col-md-6 d-flex align-items-end">
                        @can('ajouter-salaire', App\Models\User::class)
                            <button class="btn btn-success w-100" wire:click="setSalary" wire:loading.attr="disabled">
                                <span wire:loading class="spinner-border spinner-border-sm me-2"></span>
                                Enregistrer Salaire
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="table-wrapper">
            <div class="card has-actions has-filter mb-4">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="mb-0 text-primary"><i class="bx bx-list-ul me-2"></i> Liste des Salaires Fixes des Agents</h5>
                </div>

                <div class="card-table">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Matricule</th>
                                    <th>Agent</th>
                                    <th>Montant</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($salarries as $salary)
                                    <tr>
                                        <td>{{ $salary->user->code }}</td>
                                        <td>{{ $salary->user->name.' '.$salary->user->postnom }}</td>
                                        <td>{{ number_format($salary->amount, 2) }} {{ $salary->currency }}</td>
                                        <td>
                                            @can('modifier-salaire', App\Models\User::class)
                                                <button class="btn btn-sm btn-outline-primary me-2"
                                                        wire:click="editSalary({{ $salary->id }})"
                                                        wire:loading.attr="disabled">
                                                    <span wire:loading class="spinner-border spinner-border-sm me-2"></span>
                                                    ✏️ Éditer
                                                </button>
                                            @endcan
                                            @can('supprimer-salaire', App\Models\User::class)
                                                <button class="btn btn-sm btn-outline-danger"
                                                        wire:click="removeSalary({{ $salary->id }})"
                                                        wire:loading.attr="disabled"
                                                        onclick="return confirm('Confirmer la suppression du salaire fixe ?')">
                                                    <span wire:loading class="spinner-border spinner-border-sm me-2"></span>
                                                    🗑️ Supprimer
                                                </button>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <div class="alert alert-warning mb-0">Aucun salaire fixe trouvé.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                    <div>
                        <label>
                            <select wire:model.lazy="perPageSalary" class="form-select form-select-sm">
                                <option value="3">3</option>
                                <option value="10">10</option>
                                <option value="30">30</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="999999">Tous</option>
                            </select>
                        </label>
                        <span class="text-muted ms-2">
                            Affichage de {{ $salarries->firstItem() }} à {{ $salarries->lastItem() }}
                            sur <span class="badge bg-primary">{{ $salarries->total() }}</span> salaires
                        </span>
                    </div>
                    <div class="d-flex justify-content-center">
                        {{ $salarries->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>

    </div>


    {{-- PAIEMENT DU SALAIRE --}}
    @can('ajouter-paye', App\Models\User::class)
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-primary">
                <i class="bx bx-money me-2"></i>
                Paiement du Salaire
            </h5>
        </div>

        <div class="card-body row g-3">
            {{-- Agent --}}
            <div class="col-md-4">
                    <div class="position-relative">
                        <label class="form-label fw-bold">Agent</label>
                        <div class="table-search-input">
                            <div class="input-group input-group-merge">
                                <span class="input-group-text" id="basic-addon-search31"><i
                                        class="icon-base bx bx-search"></i></span>
                                <input type="search" wire:model.live.debounce.300ms="searchAgent" class="form-control"
                                    placeholder="Rechercher Agent....." aria-label="Rechercher Agent....."
                                    aria-describedby="basic-addon-search31">
                            </div>
                        </div>

                        @if (!empty($resultsAgent))
                            <ul class="list-group w-100" style="z-index: 1000;">
                                @foreach ($resultsAgent as $user)
                                    <li class="list-group-item list-group-item-action"
                                        wire:click="selectResultAgent({{ $user['id'] }})">
                                        {{ "{$user['code']} {$user['name']} {$user['postnom']}" }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @error('user_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror

                    </div>
                </div>

            {{-- Devise --}}
            <div class="col-md-2">
                <label class="form-label fw-bold">Devise</label>
                <select class="form-select" wire:model="currency">
                    <option value="CDF">CDF</option>
                    <option value="USD">USD</option>
                </select>
            </div>

            {{-- Période --}}
            <div class="col-md-3">
                <label class="form-label fw-bold">Période</label>
                <input type="month" class="form-control" wire:model="period">
            </div>

            {{-- Bouton --}}
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-primary w-100" wire:click="paySalary({{ $user_id ?? 'null' }})" wire:loading.attr="disabled" @if(!$user_id) disabled @endif>
                    <span wire:loading class="spinner-border spinner-border-sm me-2"></span>
                    Payer Salaire
                </button>

            </div>
        </div>
    </div>
    @endcan

    {{-- HISTORIQUE DES PAIEMENTS --}}
    <div class="card has-actions has-filter">
        <div class="card-header d-flex justify-content-between">
            <h5 class="mb-0 text-primary"><i class="bx bx-history me-2"></i> Historique des Salaires Payés</h5>
            <div class="table-search-input">
                <input type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher un agent...">
            </div>
        </div>

        <div class="card-table">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Matricule</th>
                            <th>Agent</th>
                            <th>Période</th>
                            <th>Devise</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payrolls as $payroll)
                            <tr>
                                <td>{{ $payroll->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $payroll->user->code }}</td>
                                <td>{{ $payroll->user->name.' '.$payroll->user->postnom }}</td>
                                <td>{{ $payroll->period }}</td>
                                <td>{{ $payroll->currency }}</td>
                                <td>{{ number_format($payroll->amount, 2) }}</td>
                                <td>
                                    <span class="badge bg-label-success">{{ ucfirst($payroll->status) }}</span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-dark"
                                            wire:click="exportPayslip({{ $payroll->id }})"
                                            wire:loading.attr="disabled">
                                        <span wire:loading class="spinner-border spinner-border-sm me-2"></span>
                                        📄 Bulletin
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="alert alert-warning mb-0">Aucun salaire payé trouvé.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <div>
                <label>
                    <select wire:model.lazy="perPage" class="form-select form-select-sm">
                        <option value="10">10</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="999999">Tous</option>
                    </select>
                </label>
                <span class="text-muted ms-2">
                    Affichage de {{ $payrolls->firstItem() }} à {{ $payrolls->lastItem() }}
                    sur <span class="badge bg-primary">{{ $payrolls->total() }}</span> paiements
                </span>
            </div>

            <div class="d-flex justify-content-center">
                {{ $payrolls->links() }}
            </div>
        </div>
    </div>
</div>
