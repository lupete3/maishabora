<div class="container mt-4">
    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <div class="card">
        <div class="card-header bg-danger text-white">Retirer l'épargne d'une carte</div>
        <div class="card-body">
            <form wire:submit.prevent="submit">
                <div class="mb-3">
                    <label>Choisir une carte</label>
                    <select wire:model="card_id" class="form-control">
                        <option value="">Sélectionner une carte</option>
                        @foreach ($cards as $card)
                            <option value="{{ $card->id }}">
                                {{ $card->currency }} - Total épargné : {{ number_format($card->total_saved, 2) }}
                                (Fin: {{ \Carbon\Carbon::parse($card->end_date)->format('d/m/Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-danger">Valider le retrait</button>
            </form>
        </div>
    </div>
</div>
