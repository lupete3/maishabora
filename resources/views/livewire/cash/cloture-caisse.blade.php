<div class="mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Clôture de caisse - Agent</h5>
        </div>

        <div class="card-body">
            {{-- Agent connecté --}}
            <p><strong>Agent :</strong> {{ auth()->user()->name }} {{ auth()->user()->postnom ?? '' }}</p>
            <p><strong>Date :</strong> {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</p>

            {{-- Soldes --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="alert alert-success">
                        <h6>Solde logique</h6>
                        <p>USD : {{ number_format($logical_usd, 2) }} $</p>
                        <p>CDF : {{ number_format($logical_cdf, 2) }} Fc</p>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="alert alert-warning">
                        <h6>Solde physique</h6>
                        <p>USD : {{ number_format($physical_usd, 2) }} $</p>
                        <p>CDF : {{ number_format($physical_cdf, 2) }} Fc</p>
                    </div>
                </div>
            </div>

            {{-- Billetage USD --}}
            <h6 class="mt-4">Billetage USD</h6>
            <div class="row mb-3">
                @foreach ($denominations_usd as $denomination)
                    <div class="col-md-2 mb-2">
                        <label class="form-label">${{ $denomination }}</label>
                        <input type="number" wire:model.lazy="billetages_usd.{{ $denomination }}" class="form-control" min="0">
                    </div>
                @endforeach
            </div>

            {{-- Billetage CDF --}}
            <h6 class="mt-4">Billetage CDF</h6>
            <div class="row mb-3">
                @foreach ($denominations_cdf as $denomination)
                    <div class="col-md-2 mb-2">
                        <label class="form-label">{{ $denomination }} Fc</label>
                        <input type="number" wire:model.lazy="billetages_cdf.{{ $denomination }}" class="form-control" min="0">
                    </div>
                @endforeach
            </div>

            {{-- Écarts --}}
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="alert alert-danger">
                        <h6>Écart USD</h6>
                        <p>{{ number_format($gap_usd, 2) }} $</p>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="alert alert-danger">
                        <h6>Écart CDF</h6>
                        <p>{{ number_format($gap_cdf, 2) }} Fc</p>
                    </div>
                </div>
            </div>

            {{-- Clôturer --}}
            <div class="text-end mt-3">
                <button wire:click="submitCloture" class="btn btn-success" wire:loading.attr="disabled">
                    <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                    <i class="bx bx-lock"></i> Clôturer la caisse
                </button>
            </div>
        </div>
    </div>

    <livewire:cash.cash-closing-history>
</div>
