<div class="mt-4">
<div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-primary">
            <i class="bx bx-transfer-alt me-2"></i>
            Effectuer un virement depuis la caisse centrale
        </h5>
    </div>

    <div class="card-body">
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Type de virement --}}
        <div class="mb-3">
            <label class="form-label fw-bold">Type de bénéficiaire</label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" id="agentRadio" value="agent" wire:model.lazy="transfer_type">
                <label class="form-check-label" for="agentRadio">Agent</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" id="memberRadio" value="member" wire:model.lazy="transfer_type">
                <label class="form-check-label" for="memberRadio">Membre</label>
            </div>
        </div>

        {{-- Devise --}}
        <div class="mb-3">
            <label class="form-label fw-bold">Devise</label>
            <select class="form-select" wire:model="currency">
                <option value="CDF">Franc Congolais (CDF)</option>
                <option value="USD">Dollar Américain (USD)</option>
            </select>
        </div>

        {{-- Choix du bénéficiaire --}}
        <div class="mb-3">
            <label class="form-label fw-bold">Bénéficiaire</label>
            <select class="form-select" wire:model="recipient_id">
                <option value="">-- Choisissez un bénéficiaire --</option>
                @foreach($recipients as $recipient)
                    <option value="{{ $recipient->user->id }}">
                        {{ $recipient->user->name.' '.$recipient->user->postnom.' '.$recipient->user->prenom ?? 'Non défini' }} (ID: {{ $recipient->id }})
                    </option>
                @endforeach
            </select>
            @error('recipient_id') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        {{-- Montant --}}
        <div class="mb-3">
            <label class="form-label fw-bold">Montant</label>
            <input type="number" step="0.01" class="form-control" wire:model="amount" placeholder="Saisissez le montant">
            @error('amount') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        {{-- Description facultative --}}
        <div class="mb-3">
            <label class="form-label fw-bold">Description (facultatif)</label>
            <textarea class="form-control" rows="2" wire:model="description" placeholder="Motif ou remarque du virement"></textarea>
        </div>

        {{-- Bouton soumettre --}}
        <div class="d-grid">
            <button wire:click="submitTransfer" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                <i class="bx bx-send me-1"></i> Effectuer le virement
            </button>
        </div>
    </div>
</div>

<div class="table-wrapper mt-4">
    <div class="card has-actions has-filter">
        <div class="card-header">
            <div class="w-100 justify-content-between d-flex flex-wrap align-items-center gap-1">
                <div class="d-flex flex-wrap flex-md-nowrap align-items-center gap-1">
                    <button class="btn btn-show-table-options" type="button">Rechercher</button>
                    <div class="table-search-input">
                        <label>
                            <input type="search" wire:model.live="search" class="form-control input-sm"
                                   placeholder="Rechercher..." style="min-width: 120px">
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-table">
            <div class="table-responsive table-has-actions table-has-filter">
                <table class="table card-table table-vcenter table-striped table-hover dataTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Devise</th>
                            <th>Montant</th>
                            <th>Solde après</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if ($transaction->type === 'virement_caisse_entrant')
                                        <span class="badge bg-label-success me-1">Entrant</span>
                                    @elseif ($transaction->type === 'virement_caisse_sortant')
                                        <span class="badge bg-label-danger me-1">Sortant</span>
                                    @else
                                        <span class="badge bg-label-secondary me-1">{{ ucfirst($transaction->type) }}</span>
                                    @endif
                                </td>
                                <td>{{ $transaction->currency }}</td>
                                <td>{{ number_format($transaction->amount, 2) }}</td>
                                <td>{{ number_format($transaction->balance_after, 2) }}</td>
                                <td>{{ $transaction->description }}</td>
                                <td>
                                    @if ($transaction->type === 'virement_caisse_entrant')
                                        <button wire:click="exportReceipt({{ $transaction->id }})" wire:loading.attr="disabled"
                                             class="btn btn-sm btn-outline-dark">
                                            <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>

                                            📄 Reçu
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">
                                    <div class="alert alert-warning" role="alert">
                                        Aucune opération de virement trouvée.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <div class="d-flex justify-content-between align-items-center gap-3">
                <label>
                    <select wire:model.lazy="perPage" class="form-select form-select-sm">
                        <option value="10">10</option>
                        <option value="30">30</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="999999">Tous</option>
                    </select>
                </label>
                <div class="text-muted">
                    Affichage de {{ $this->transactions->firstItem() }} à {{ $this->transactions->lastItem() }}
                    sur <span class="badge bg-primary">{{ $this->transactions->total() }}</span> opérations
                </div>
            </div>

            <div class="d-flex justify-content-center">
                {{ $this->transactions->links() }}
            </div>
        </div>
    </div>
</div>

</div>