<div class="card">
    <div class="card-header bg-label-warning fw-bold">
        Conversion de devises (Caisse Centrale)
    </div>
    <div class="card-body">
        @if (session()->has('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form wire:submit.prevent="convert">
            <div class="row mb-3 mt-3">
                <div class="col-md-6">
                    <label>De (Devise Source)</label>
                    <select class="form-control" wire:model="from_currency">
                        <option value="USD">USD ({{ number_format($balances['USD']->balance ?? 0, 2) }})</option>
                        <option value="CDF">CDF ({{ number_format($balances['CDF']->balance ?? 0, 2) }})</option>
                    </select>
                    @error('from_currency') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="col-md-6">
                    <label>Vers (Devise Cible)</label>
                    <select class="form-control" wire:model="to_currency">
                        <option value="USD">USD</option>
                        <option value="CDF">CDF</option>
                    </select>
                    @error('to_currency') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label>Montant à convertir ({{ $from_currency }})</label>
                <input type="number" step="0.01" wire:model="amount" class="form-control">
                @error('amount') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            @if($exchange_rate)
                <div class="alert alert-info">
                    Taux actuel : 1 {{ $from_currency }} = {{ $exchange_rate }} {{ $to_currency }}
                </div>
            @endif

            <button class="btn btn-primary" type="submit">Convertir</button>
        </form>
    </div>
</div>
