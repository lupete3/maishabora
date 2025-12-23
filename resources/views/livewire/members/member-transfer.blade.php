<div class="">
    @if ($step === 1)
        <form wire:submit.prevent="nextStep">
            <div class="mb-3">
                <label for="sourceAccount" class="form-label">Choisir votre compte</label>
                <select id="sourceAccount" class="form-select @error('selectedAccountId') is-invalid @enderror"
                    wire:model.live="selectedAccountId">
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">
                            {{ $account->currency }} - Solde : {{ number_format($account->balance, 2) }}
                        </option>
                    @endforeach
                </select>
                @error('selectedAccountId') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="receiverCode" class="form-label">Code du bénéficiaire</label>
                <input type="text" class="form-control @error('receiverCode') is-invalid @enderror" id="receiverCode"
                    wire:model.live.debounce.500ms="receiverCode" placeholder="Ex: 0321">
                @if($receiverName)
                    <div class="form-text text-success">
                        <i class="fas fa-check-circle"></i> Bénéficiaire : <strong>{{ $receiverName }}</strong>
                    </div>
                @endif
                @error('receiverCode') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="amount" class="form-label">Montant</label>
                <input type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror" id="amount"
                    wire:model="amount">
                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Motif (Optionnel)</label>
                <input type="text" class="form-control @error('description') is-invalid @enderror" id="description"
                    wire:model="description">
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled"> <span wire:loading wire:target="nextStep" class="spinner-border spinner-border-sm me-2"
                            role="status"></span> Suivant</button>
        </form>
    @elseif ($step === 2)
        <div class="text-center">
            <h5>Confirmation du virement</h5>
            <p>Voulez-vous vraiment effectuer ce virement ?</p>

            <ul class="list-group text-start mb-4 mx-auto" style="max-width: 400px;">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    Bénéficiaire
                    <span class="fw-bold">{{ $receiverName }} ({{ $receiverCode }})</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    Montant
                    <span class="fw-bold">{{ number_format($amount, 2) }}
                        {{ $accounts->find($selectedAccountId)->currency }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    Motif
                    <span class="text-muted">{{ $description ?: 'Aucun' }}</span>
                </li>
            </ul>

            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-secondary" wire:click="previousStep">Retour</button>
                <button type="button" class="btn btn-success" wire:click="executeTransfer" wire:loading.attr="disabled"> <span wire:loading wire:target="executeTransfer" class="spinner-border spinner-border-sm me-2"
                            role="status"></span> Confirmer le virement</button>
            </div>
        </div>
    @endif
</div>