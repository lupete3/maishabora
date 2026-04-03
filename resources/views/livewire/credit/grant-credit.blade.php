<!-- resources/views/livewire/grant-credit.blade.php -->
<div class="mt-0">
    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <h3>Gestion des crédits</h3>

    <div class="card" wire:ignore.self>
        <div class="card-header bg-primary text-white">Octroyer un Crédit</div>
        <div class="card-body">
            <form wire:submit.prevent="submit">
                <div class="row mt-3">
                    <div class="col-md-6 mb-3">
                        <div class="position-relative">
                            <label>Membre</label>
                            <div class="table-search-input">
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text" id="basic-addon-search31"><i
                                            class="icon-base bx bx-search"></i></span>
                                    <input type="search" wire:model.live.debounce.300ms="search" class="form-control"
                                        placeholder="Rechercher Membre....." aria-label="Rechercher Membre....."
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
                            @error('member_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror

                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label>Devise</label>
                        <select wire:model="currency" class="form-select">
                            <option value="USD">USD</option>
                            <option value="CDF">CDF</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label>Montant</label>
                        <input type="number" step="0.01" wire:model="amount" class="form-control" />
                        @error('amount')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Taux d'intérêt (%)</label>
                        <input type="number" step="0.01" wire:model="interest_rate" class="form-control" />
                        @error('interest_rate')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Fréquence des échéances</label>
                        <select wire:model="frequency" class="form-select">
                            <option value="daily">Quotidienne</option>
                            <option value="weekly">Hebdomadaire</option> <!-- AJOUT -->
                            <option value="monthly">Mensuelle</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Nombre d'échéances</label>
                        <input type="number" wire:model="installments" class="form-control" />
                        @error('installments')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Date de début</label>
                        <input type="date" wire:model="start_date" class="form-control" />
                        @error('start_date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Type de remboursement</label>
                        <select wire:model="repayment_type" class="form-select">
                            <option value="degressif">Dégressif</option>
                            <option value="constant">Constant</option>
                        </select>
                    </div>

                    <div class="col-md-2 mb-3">
                        <label>Frais dossier (%) </label>
                        <input type="number" step="0.01" wire:model="creditFrisFix" class="form-control" />
                        @error('creditFrisFix')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-2 mb-3">
                        <label>Mutuelle (%) </label>
                        <input type="number" step="0.01" wire:model="mutuelle_rate" class="form-control" />
                        @error('mutuelle_rate')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <div class="position-relative">
                            <label>Agent Crédit (Gestionnaire)</label>
                            <div class="table-search-input">
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text" id="basic-addon-search31"><i
                                            class="icon-base bx bx-search"></i></span>
                                    <input type="search" wire:model.live.debounce.300ms="agent" class="form-control"
                                        placeholder="Rechercher Agent Crédit....."
                                        aria-label="Rechercher Agent Crédit....."
                                        aria-describedby="basic-addon-search31">
                                </div>
                            </div>

                            @if (!empty($resultsAgent))
                                <ul class="list-group w-100" style="z-index: 1000;">
                                    @foreach ($resultsAgent as $agent)
                                        <li class="list-group-item list-group-item-action"
                                            wire:click="selectResultAgent({{ $agent['id'] }})">
                                            {{ "{$agent['code']} {$agent['name']} {$agent['postnom']}" }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            @error('agent_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror

                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div class="position-relative">
                            <label>Source de financement (Caissier)</label>
                            <div class="table-search-input">
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text"><i class="icon-base bx bx-search"></i></span>
                                    <input type="search" wire:model.live.debounce.300ms="disbursing_agent"
                                        class="form-control" placeholder="Rechercher Caissier..."
                                        aria-label="Rechercher Caissier...">
                                </div>
                            </div>

                            @if (!empty($resultsDisbursingAgent))
                                <ul class="list-group w-100" style="z-index: 1000;">
                                    @foreach ($resultsDisbursingAgent as $agent)
                                        <li class="list-group-item list-group-item-action"
                                            wire:click="selectResultDisbursingAgent({{ $agent['id'] }})">
                                            {{ "{$agent['code']} {$agent['name']} {$agent['postnom']}" }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            @error('disbursing_agent_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Description (facultatif)</label>
                        <input type="text" wire:model="description" class="form-control" />
                    </div>

                    <div class="col-md-12">
                        <button type="button" class="btn btn-success float-end" wire:click="confirmGrant">
                            <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Valider le Crédit
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmation -->
    <div class="modal fade @if($showConfirmModal) show d-block @endif" tabindex="-1"
        style="@if($showConfirmModal)  @else display:none; @endif"
        aria-hidden="{{ $showConfirmModal ? 'false' : 'true' }}">

        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmation de l’octroi de crédit</h5>
                    <button type="button" class="btn-close" wire:click="$set('showConfirmModal', false)"></button>
                </div>

                <div class="modal-body">
                    @if ($hasActiveCredit)
                        <div class="alert alert-danger mb-3">
                            <i class="bx bx-error-circle me-1"></i>
                            <strong>Attention !</strong> Ce membre a déjà un crédit en cours qui n'est pas encore totalement remboursé.
                        </div>
                    @endif
                    <p class="fw-bold text-center mb-3">Merci de vérifier les détails ci-dessous avant de confirmer :
                    </p>
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th>Membre</th>
                                <td>{{ $creditSummary['membre'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <th>Montant du crédit</th>
                                <td>{{ $creditSummary['montant'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <th>Taux d'intérêt</th>
                                <td>{{ $creditSummary['taux'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <th>Frais du dossier</th>
                                <td>{{ $creditSummary['frais'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <th>Frais Mutuelle</th>
                                <td>{{ $creditSummary['mutuelle'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <th>Total à rembourser (approximatif)</th>
                                <td class="fw-bold text-success">{{ $creditSummary['total'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <th>Nombre d'échéances</th>
                                <td>{{ $creditSummary['echeances'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <th>Date de début</th>
                                <td>{{ $creditSummary['debut'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <th>Type de remboursement</th>
                                <td>{{ $creditSummary['type'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <th>Agent Crédit</th>
                                <td>{{ $creditSummary['agent'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <th>Source (Caissier)</th>
                                <td>{{ $creditSummary['disbursing_agent'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>{{ $creditSummary['description'] ?? '' }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="alert alert-warning mt-3">
                        <i class="bx bx-error-circle"></i>
                        Cette opération est irréversible. Vérifiez bien les informations avant de confirmer.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" wire:click="$set('showConfirmModal', false)" class="btn btn-secondary">
                        Annuler
                    </button>
                    <button type="button" wire:click="confirmSubmit" class="btn btn-success">
                        <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Confirmer et Octroyer
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>