<div>
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

            <button type="submit" wire:loading.attr="disabled" class="btn btn-primary">
                <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                Convertir
            </button>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Historique des conversions</h5>
        <button wire:click="exportConversionsPdf" wire:loading.attr="disabled" class="btn btn-primary mb-2">
            <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
            📄 Exporter PDF
        </button>
    </div>

    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Effectué par</th>
                    <th>Devise</th>
                    <th>Montant converti</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse ($conversions as $conversion)
                    <tr>
                    <td>{{ $conversion->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $conversion->user->name }}</td>
                    <td>{{ $conversion->currency }}</td>
                    <td>-{{ number_format($conversion->amount, 2) }}</td>
                    <td>{{ $conversion->description }}</td>
                </tr>
                @if($conversion->paired_entry)
                    <tr>
                        <td>{{ $conversion->paired_entry->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $conversion->paired_entry->user->name }}</td>
                        <td>{{ $conversion->paired_entry->currency }}</td>
                        <td>+{{ number_format($conversion->paired_entry->amount, 2) }}</td>
                        <td>{{ $conversion->paired_entry->description }}</td>
                    </tr>
                @endif
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Aucune conversion enregistrée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        {{ $conversions->links() }}
    </div>
</div>


</div>
