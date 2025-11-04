<div class="mt-0">
    <h3>Virement vers la caisse centrale</h3>
    <p>Complétez le formulaire ci-dessous pour transférer de l'argent vers la caisse centrale.</p>

    <div class="row">
        <div class="col-md-6">
            <div class="card mt-2">
                <div class="card-header bg-primary text-white">Formulaire de virement</div>
                <div class="card-body">
                    <form wire:submit.prevent="submit">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Devise</label>
                                <select wire:model.live="currency" class="form-select">
                                    <option value="">Choisir une devise</option>
                                    @foreach($currencies as $curr)
                                        <option value="{{ $curr }}">{{ $curr }}</option>
                                    @endforeach
                                </select>
                                @error('currency') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Montant</label>
                                <input type="number" step="0.01" wire:model.live.debounce.500ms="amount" class="form-control" placeholder="Ex: 100.00" />
                                @error('amount') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-12">
                                <button type="submit" class="btn btn-success float-end" wire:loading.attr="disabled">
                                    <span wire:loading class="spinner-border spinner-border-sm me-2"></span>
                                    Prévisualiser le virement
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mt-2">
                <div class="card-header bg-secondary text-white">Vos soldes actuels</div>
                <div class="card-body">
                    <table class="table table-bordered mt-4">
                        <thead>
                            <tr>
                                <th>Devise</th>
                                <th>Solde</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($agentAccounts as $acc)
                                <tr>
                                    <td>{{ $acc->currency }}</td>
                                    <td>{{ number_format($acc->balance, 2) }}</td>
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
                                    <td>{{ Auth::user()->name.' '.Auth::user()->postnom.' '.Auth::user()->prenom }}</td>
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
