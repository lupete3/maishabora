<!-- resources/views/livewire/deposit-for-member.blade.php -->
<div class="container mt-4">
    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <div class="card">
        <div class="card-header bg-primary text-white">Effectuer un dépôt</div>
        <div class="card-body">
            <form wire:submit.prevent="submit">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Membre</label>
                        <select wire:model="member_id" class="form-control">
                            <option value="">Sélectionner un membre</option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->email }})</option>
                            @endforeach
                        </select>
                        @error('member_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label>Devise</label>
                        <select wire:model="currency" class="form-control">
                            <option value="">Choisir devise</option>
                            <option value="USD">USD</option>
                            <option value="CDF">CDF</option>
                        </select>
                        @error('currency') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label>Montant</label>
                        <input type="number" step="0.01" wire:model="amount" class="form-control" />
                        @error('amount') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-12 mb-3">
                        <label>Description (facultatif)</label>
                        <input type="text" wire:model="description" class="form-control" />
                    </div>

                    <div class="col-md-12">
                        <button type="submit" class="btn btn-success">Valider le dépôt</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
