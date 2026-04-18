<div class="mt-0">
    <h3>Virement vers la caisse centrale</h3>
    <p>Complétez le formulaire ci-dessous pour transférer de l'argent vers la caisse centrale.</p>

    <div class="row">
        <div class="col-md-5">
            <div class="card mt-2 shadow-sm border-0">
                <div class="card-header bg-secondary text-white fw-bold">
                    <i class="bx bx-wallet me-2"></i>Vos soldes actuels
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">Cliquez sur un solde pour préparer le virement</p>
                    <table class="table table-hover border">
                        <thead>
                            <tr class="table-light">
                                <th>Devise</th>
                                <th class="text-end">Solde</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($agentAccounts as $acc)
                                <tr wire:click="setFillAmount('{{ $acc->currency }}', {{ $acc->balance }})"
                                    style="cursor: pointer" class="hover-shadow"
                                    title="Cliquez pour transférer tout le solde">
                                    <td class="fw-bold text-primary">{{ $acc->currency }}</td>
                                    <td class="text-end">
                                        <span class="fw-bold">{{ number_format($acc->balance, 2) }}</span>
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">Cliquez pour
                                            remplir</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted">Aucun compte disponible</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card mt-2 shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bx bx-transfer me-2"></i>Formulaire de virement
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="submit">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Devise</label>
                                <select wire:model.live="currency" class="form-select">
                                    <option value="">Choisir une devise</option>
                                    @foreach ($currencies as $curr)
                                        <option value="{{ $curr }}">{{ $curr }}</option>
                                    @endforeach
                                </select>
                                @error('currency')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Montant à transférer</label>
                                <input type="number" step="0.01" wire:model.live.debounce.500ms="amount"
                                    class="form-control" placeholder="0.00" />
                                @error('amount')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary w-100" wire:loading.attr="disabled">
                                    <span wire:loading class="spinner-border spinner-border-sm me-2"></span>
                                    <i class="bx bx-check-circle me-1"></i>Prévisualiser le virement
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Historique des transferts -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center fw-bold">
                    <span><i class="bx bx-history me-2"></i>Historique de vos virements récents</span>

                    <div class="d-flex align-items-center gap-2">
                        <select wire:model.live="filterType" class="form-select form-select-sm" style="width: auto;">
                            <option value="day">Aujourd'hui</option>
                            <option value="week">Cette semaine</option>
                            <option value="month">Ce mois</option>
                            <option value="range">Intervalle personnalisé</option>
                        </select>

                        @if ($filterType === 'range')
                            <input type="date" wire:model.live="startDate" class="form-control form-control-sm"
                                style="width: auto;">
                            <span class="text-white small">au</span>
                            <input type="date" wire:model.live="endDate" class="form-control form-control-sm"
                                style="width: auto;">
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Référence</th>
                                    @if ($isAdminOrFinance)
                                        <th>Agent</th>
                                    @endif
                                    <th>Devise</th>
                                    <th class="text-end">Montant</th>
                                    <th class="text-center">Statut</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transfers as $trans)
                                    <tr>
                                        <td>{{ $trans->created_at->format('d/m/Y H:i') }}</td>
                                        <td><span class="badge bg-label-secondary">#REF{{ $trans->id }}</span></td>
                                        @if ($isAdminOrFinance)
                                            <td>
                                                <small
                                                    class="fw-bold">{{ $trans->fromAgentAccount->user->name ?? 'N/A' }}
                                                    {{ $trans->fromAgentAccount->user->postnom ?? 'N/A' }} </small>
                                            </td>
                                        @endif
                                        <td class="fw-bold">{{ $trans->currency }}</td>
                                        <td class="text-end fw-bold">{{ number_format($trans->amount, 2) }}</td>
                                        <td class="text-center">
                                            @if ($trans->status === 'pending')
                                                <span class="badge bg-label-warning">En attente</span>
                                            @elseif($trans->status === 'validated')
                                                <span class="badge bg-label-success">Validé</span>
                                                <small class="d-block text-muted" style="font-size: 0.65rem;">
                                                    Par: {{ $trans->processedBy->name ?? 'Admin' }}
                                                </small>
                                            @elseif($trans->status === 'cancelled')
                                                <span class="badge bg-label-danger">Annulé</span>
                                                <small class="d-block text-muted" style="font-size: 0.65rem;">
                                                    Par: {{ $trans->processedBy->name ?? 'Admin' }}
                                                    {{ $trans->processedBy->postnom ?? 'N/A' }}
                                                </small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                @if ($trans->status === 'validated')
                                                    <a href="{{ route('transfer.receipt.generate', ['id' => $trans->id]) }}"
                                                        target="_blank" class="btn btn-sm btn-icon btn-outline-danger"
                                                        title="Reçu PDF">
                                                        <i class="bx bxs-file-pdf"></i>
                                                    </a>
                                                @endif

                                                @if ($trans->status === 'pending')
                                                    @can('valider-transfert-caisse')
                                                        <button wire:click="validateTransfer({{ $trans->id }})"
                                                            wire:confirm="Voulez-vous vraiment valider ce virement ?"
                                                            class="btn btn-sm btn-icon btn-outline-success"
                                                            title="Valider">
                                                            <i class="bx bx-check"></i>
                                                        </button>
                                                    @endcan

                                                    @can('annuler-transfert-caisse')
                                                        <button wire:click="cancelTransfer({{ $trans->id }})"
                                                            wire:confirm="Voulez-vous vraiment annuler ce virement ?"
                                                            class="btn btn-sm btn-icon btn-outline-warning"
                                                            title="Annuler">
                                                            <i class="bx bx-x"></i>
                                                        </button>
                                                    @endcan
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Aucun virement
                                            enregistré
                                            récemment</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $transfers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ Modal pure Livewire -->
    @if ($showConfirmation)
        <div class="modal-backdrop fade show"></div>
        <div class="modal d-block" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-3 shadow-lg border-0">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">Prévisualisation du virement</h5>
                        <button type="button" class="btn-close"
                            wire:click="$set('showConfirmation', false)"></button>
                    </div>
                    <div class="modal-body">
                        <div class="p-3 bg-light rounded">
                            <table class="table table-sm mb-0">
                                <tr>
                                    <th>Agent</th>
                                    <td>{{ Auth::user()->name . ' ' . Auth::user()->postnom . ' ' . Auth::user()->prenom }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Devise</th>
                                    <td>{{ $currency }}</td>
                                </tr>
                                <tr>
                                    <th>Montant</th>
                                    <td>{{ number_format($amount ?: 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Date</th>
                                    <td>{{ now()->format('d/m/Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                        <p class="mt-3 text-muted small">
                            Confirmez pour valider le virement vers la caisse centrale.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary"
                            wire:click="$set('showConfirmation', false)">Annuler</button>
                        <button wire:click="confirmSubmit" class="btn btn-success">
                            <span wire:loading.class="spinner-border spinner-border-sm me-2"></span>
                            Confirmer le virement
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
