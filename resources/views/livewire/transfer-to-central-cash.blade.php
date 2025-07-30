<!-- resources/views/livewire/transfer-to-central-cash.blade.php -->
<div class="mt-0">
    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <h3>Virément caisse centrale</h3>
    <p>Complérer le formulaire pour transférer de l'argent vers la caisse centrale</p>

    <div class="row">
        <div class="col-md-6">
            <div class="card mt-2">
                <div class="card-header bg-primary text-white">Virement vers la caisse centrale</div>
                <div class="card-body">
                    <form wire:submit.prevent="submit" class="mt-2">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Devise</label>
                                <select wire:model="currency" class="form-control">
                                    <option value="">Choisir devise</option>
                                    @foreach($currencies as $curr)
                                        <option value="{{ $curr }}">{{ $curr }}</option>
                                    @endforeach
                                </select>
                                @error('currency') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Montant</label>
                                <input type="number" step="0.01" wire:model="amount" class="form-control" />
                                @error('amount') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="col-md-12">
                                <button type="submit" class="btn btn-success float-end">
                                    <span wire:loading class="spinner-border spinner-border-sm me-2"
                                    role="status"></span>
                                    Valider le virement</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mt-2">
                <div class="card-header bg-secondary text-white">Soldes actuels</div>
                <div class="card-body">
                    <table class="table table-bordered mt-4">
                        <thead>
                            <tr>
                                <th>Devise</th>
                                <th>Votre caisse</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($agentAccounts as $acc)
                                <tr>
                                    <td>{{ $acc->currency }}</td>
                                    <td>{{ number_format($acc->balance, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
