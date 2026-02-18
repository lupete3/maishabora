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
                                    @foreach($currencies as $curr)
                                        <option value="{{ $curr }}">{{ $curr }}</option>
                                    @endforeach
                                </select>
                                @error('currency') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Montant à transférer</label>
                                <input type="number" step="0.01" wire:model.live.debounce.500ms="amount"
                                    class="form-control" placeholder="0.00" />
                                @error('amount') <span class="text-danger small">{{ $message }}</span> @enderror
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
                <div class="card-header bg-dark text-white fw-bold">
                    <i class="bx bx-history me-2"></i>Historique de vos virements récents
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Référence</th>
                                    <th>Devise</th>
                                    <th class="text-end">Montant</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transfers as $trans)
                                    <tr>
                                        <td>{{ $trans->created_at->format('d/m/Y H:i') }}</td>
                                        <td><span class="badge bg-label-secondary">#REF{{ $trans->id }}</span></td>
                                        <td class="fw-bold">{{ $trans->currency }}</td>
                                        <td class="text-end fw-bold">{{ number_format($trans->amount, 2) }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('transfer.receipt.generate', ['id' => $trans->id]) }}"
                                                target="_blank" class="btn btn-sm btn-outline-danger">
                                                <i class="bx bxs-file-pdf me-1"></i>Reçu
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Aucun virement enregistré
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
    @if($showConfirmation)
        <div class="modal-backdrop fade show"></div>
        <div class="modal d-block" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-3 shadow-lg border-0">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">Prévisualisation du virement</h5>
                        <button type="button" class="btn-close" wire:click="$set('showConfirmation', false)"></button>
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
                        <button class="btn btn-secondary" wire:click="$set('showConfirmation', false)">Annuler</button>
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