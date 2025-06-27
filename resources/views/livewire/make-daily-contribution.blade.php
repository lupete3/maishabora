<div class="container mt-4">
    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @elseif(session()->has('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-header bg-info text-white">Effectuer une mise quotidienne</div>
        <div class="card-body">
            <form wire:submit.prevent="contribute">
                <div class="mb-3">
                    <label>Choisir une carte</label>
                    <select wire:model.live="card_id" class="form-control">
                        <option value="">Sélectionner une carte</option>
                        @foreach ($cards as $card)
                            <option value="{{ $card->id }}">
                                {{ $card->member->name }} | Carte #{{ $card->code }}
                                | {{ $card->subscription_amount }}
                                {{ $card->currency }} |
                                Du {{ $card->start_date }} au {{ $card->end_date }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if ($selectedCard)
                    <div class="mb-3">
                        <label>Date de la mise</label>
                        <input type="date" wire:model="contribution_date" class="form-control" />
                        @error('contribution_date') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-3">
                        <label>Montant à verser</label>
                        <input type="number" step="0.01" wire:model="amount" class="form-control" />
                        @error('amount') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Valider la mise</button>
                @endif
            </form>
        </div>
    </div>
</div>
