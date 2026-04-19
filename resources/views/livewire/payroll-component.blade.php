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
                                        <input type="search" wire:model.live.debounce.300ms="search"
                                            class="form-control" placeholder="Rechercher Agent....."
                                            aria-label="Rechercher Agent....." aria-describedby="basic-addon-search31">
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
                            <input type="number" step="0.01" class="form-control" wire:model="salary_amount"
                                placeholder="Montant du salaire">
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
                        <h5 class="mb-0 text-primary"><i class="bx bx-list-ul me-2"></i> Liste des Salaires Fixes des
                            Agents</h5>
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
                                            <td>{{ $salary->user->name . ' ' . $salary->user->postnom }}</td>
                                            <td>{{ number_format($salary->amount, 2) }} {{ $salary->currency }}</td>
                                            <td>
                                                @can('modifier-salaire', App\Models\User::class)
                                                    <button class="btn btn-sm btn-outline-primary me-2"
                                                        wire:click="editSalary({{ $salary->id }})" wire:loading.attr="disabled">
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

                    <div
                        class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
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
                <div class="col-md-3">
                    <div class="position-relative">
                        <label class="form-label fw-bold">Agent (Bénéficiaire)</label>
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
                            <span class="text-danger small">{{ $message }}</span>
                        @enderror

                    </div>
                </div>

                {{-- Caisse / Caissier --}}
                <div class="col-md-3">
                    <div class="position-relative">
                        <label class="form-label fw-bold">Caisse / Caissier (Retrait)</label>
                        <div class="table-search-input">
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="icon-base bx bx-search"></i></span>
                                <input type="search" wire:model.live.debounce.300ms="caisseSearch" class="form-control"
                                    placeholder="Rechercher Caissier.....">
                            </div>
                        </div>

                        @if (!empty($resultsCaisse))
                            <ul class="list-group w-100" style="z-index: 1000;">
                                @foreach ($resultsCaisse as $caisse)
                                    <li class="list-group-item list-group-item-action"
                                        wire:click="selectResultCaisse({{ $caisse['id'] }})">
                                        {{ "{$caisse['code']} {$caisse['name']} {$caisse['postnom']}" }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                {{-- Devise --}}
                <div class="col-md-1">
                    <label class="form-label fw-bold">Devise</label>
                    <select class="form-select" wire:model="currency">
                        <option value="CDF">CDF</option>
                        <option value="USD">USD</option>
                    </select>
                </div>

                {{-- Période --}}
                <div class="col-md-2">
                    <label class="form-label fw-bold">Période</label>
                    <input type="month" class="form-control" wire:model="period">
                </div>

                {{-- Retenu Salaire % --}}
                <div class="col-md-2">
                    <label class="form-label fw-bold">Retenu Salaire (%)</label>
                    <div class="input-group">
                        <input type="number" step="0.1" min="0" max="100"
                            class="form-control" wire:model.live="retenuRate"
                            placeholder="10">
                        <span class="input-group-text">%</span>
                    </div>
                </div>

                {{-- Bouton --}}
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" wire:click="confirmPayment({{ $user_id ?? 'null' }})"
                        wire:loading.attr="disabled" @if(!$user_id || !$caisse_id) disabled @endif>
                        <span wire:loading wire:target="confirmPayment"
                            class="spinner-border spinner-border-sm me-2"></span>
                        Payer Salaire
                    </button>
                </div>
            </div>

            {{-- MODAL DE CONFIRMATION DE PAIEMENT --}}
            @if($showingConfirmationModal)
                <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);"
                    aria-modal="true" role="dialog">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title text-white"><i class="bx bx-check-circle me-2"></i>Confirmation de
                                    Paiement</h5>
                                <button type="button" class="btn-close btn-close-white"
                                    wire:click="closeConfirmationModal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center mb-4">
                                    <i class="bx bx-help-circle text-warning" style="font-size: 4rem;"></i>
                                    <h4 class="mt-2">Êtes-vous sûr ?</h4>
                                </div>
                                <p>Vous êtes sur le point d'effectuer un paiement de salaire :</p>
                                <ul class="list-group list-group-flush mb-3">
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span>Agent :</span>
                                        <span class="fw-bold">{{ $selectedUserName }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span>Caisse de retrait :</span>
                                        <span class="fw-bold text-info">{{ $caisseSearch }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span>Période :</span>
                                        <span class="fw-bold">{{ $period ?? now()->format('Y-m') }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span>Montant Brut :</span>
                                        <span class="text-primary fw-bold">{{ number_format($selectedSalaryAmount, 2) }}
                                            {{ $currency }}</span>
                                    </li>
                                    @php
                                        $retenuSalaire = round($selectedSalaryAmount * ($retenuRate / 100), 2);
                                    @endphp
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span>Retenu Salaire ({{ $retenuRate }}%) :</span>
                                        <span class="text-warning fw-bold">{{ number_format($retenuSalaire, 2) }}
                                            {{ $currency }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span>Montant Net :</span>
                                        <span class="text-success fw-bold">{{ number_format($selectedSalaryAmount - $retenuSalaire, 2) }}
                                            {{ $currency }}</span>
                                    </li>
                                </ul>
                                <div class="alert alert-info py-2">
                                    <small><i class="bx bx-info-circle me-1"></i>Cette action débitera la caisse centrale et
                                        créditera le compte de l'agent.</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary"
                                    wire:click="closeConfirmationModal">Annuler</button>
                                <button type="button" class="btn btn-primary" wire:click="paySalary({{ $user_id }})"
                                    wire:loading.attr="disabled">
                                    <span wire:loading class="spinner-border spinner-border-sm me-1"></span>
                                    Confirmer le Paiement
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endcan

    {{-- HISTORIQUE DES PAIEMENTS --}}
    <div class="card has-actions has-filter">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 text-primary"><i class="bx bx-history me-2"></i> Historique des Salaires Payés</h5>
            
            <div class="d-flex align-items-center gap-2">
                <select wire:model.live="filterType" class="form-select form-select-sm" style="width: auto;">
                    <option value="day">Aujourd'hui</option>
                    <option value="week">Cette semaine</option>
                    <option value="month">Ce mois</option>
                    <option value="range">Intervalle personnalisé</option>
                </select>

                @if ($filterType === 'range')
                    <input type="date" wire:model.live="startDate" class="form-control form-control-sm" style="width: auto;">
                    <span class="small">au</span>
                    <input type="date" wire:model.live="endDate" class="form-control form-control-sm" style="width: auto;">
                @endif

                <div class="table-search-input">
                    <input type="search" wire:model.live.debounce.300ms="search" class="form-control form-control-sm"
                        placeholder="Rechercher un agent...">
                </div>
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
                            <th>Caisse</th>
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
                                <td>{{ $payroll->user->name . ' ' . $payroll->user->postnom }}</td>
                                <td>
                                    @if($payroll->agent)
                                        <span class="badge bg-label-info">
                                            <i class="bx bx-user-circle me-1"></i>{{ $payroll->agent->name }} {{ $payroll->agent->postnom }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $payroll->period }}</td>
                                <td>{{ $payroll->currency }}</td>
                                <td>{{ number_format($payroll->amount, 2) }}</td>
                                <td>
                                    @if($payroll->status === 'paid')
                                        <span class="badge bg-label-success">Payé</span>
                                    @elseif($payroll->status === 'cancelled')
                                        <span class="badge bg-label-danger">Annulé</span>
                                    @else
                                        <span class="badge bg-label-secondary">{{ ucfirst($payroll->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-dark me-1"
                                        wire:click="exportPayslip({{ $payroll->id }})" wire:loading.attr="disabled">
                                        <span wire:loading class="spinner-border spinner-border-sm me-2"></span>
                                        📄 Bulletin
                                    </button>
                                    @if($payroll->status === 'paid')
                                        @can('annuler-paye', App\Models\User::class)
                                            <button class="btn btn-sm btn-outline-danger"
                                                wire:click="confirmCancellation({{ $payroll->id }})" wire:loading.attr="disabled">
                                                <i class="bx bx-x-circle me-1"></i> Annuler
                                            </button>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">
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

    {{-- MODAL DE CONFIRMATION D'ANNULATION --}}
    @if($showingCancellationModal)
        <div class="modal fade show" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white"><i class="bx bx-error me-2"></i>Annulation de Paiement</h5>
                        <button type="button" class="btn-close btn-close-white" wire:click="closeConfirmationModal"></button>
                    </div>
                    <div class="modal-body text-center p-4">
                        <div class="mb-3">
                            <i class="bx bx-trash text-danger" style="font-size: 5rem;"></i>
                        </div>
                        <h4>Voulez-vous vraiment annuler ce paiement ?</h4>
                        <p class="text-muted">
                            Cette action est irréversible et inversera tous les mouvements financiers associés (Caisse centrale, compte agent, etc.).
                        </p>
                        @php
                            $p = App\Models\Payroll::find($selectedPayrollId);
                        @endphp
                        @if($p)
                            <div class="alert alert-light border border-danger text-start">
                                <ul class="list-unstyled mb-0">
                                    <li><strong>Agent :</strong> {{ $p->user->name }} {{ $p->user->postnom }}</li>
                                    <li><strong>Montant :</strong> {{ number_format($p->amount, 2) }} {{ $p->currency }}</li>
                                    <li><strong>Période :</strong> {{ $p->period }}</li>
                                </ul>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-label-secondary" wire:click="closeConfirmationModal">Non, Garder</button>
                        <button type="button" class="btn btn-danger" wire:click="cancelPayment">
                            <span wire:loading wire:target="cancelPayment" class="spinner-border spinner-border-sm me-1"></span>
                            Oui, Annuler le Paiement
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
