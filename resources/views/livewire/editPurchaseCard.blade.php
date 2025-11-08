<!-- Modal de modification -->
@if ($editModal)
    <div class="modal fade  show d-block " tabindex="-1" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <form wire:submit.prevent="updateCard">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Modifier la Carte d’Adhésion</h5>
                        <button type="button" class="btn-close" wire:click="$set('editModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Code</label>
                            <input type="text" wire:model="edit_code" class="form-control">
                            @error('edit_code')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label>Devise</label>
                            <select wire:model="edit_currency" class="form-select">
                                <option value="USD">USD</option>
                                <option value="CDF">CDF</option>
                            </select>
                            @error('edit_currency')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label>Prix de la carte</label>
                            <input type="number" step="0.01" wire:model="edit_price" class="form-control">
                            @error('edit_price')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label>Montant quotidien</label>
                            <input type="number" step="0.01" wire:model="edit_subscription_amount"
                                class="form-control">
                            @error('edit_subscription_amount')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label>Agent</label>
                            <select wire:model="edit_agent_id" class="form-select">
                                <option value="">-- Aucun --</option>
                                @foreach ($agents as $agent)
                                    <option value="{{ $agent->id }}">{{ $agent->name }} ({{ $agent->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('edit_agent_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            wire:click="$set('editModal', false)">Annuler</button>
                        <button type="submit" class="btn btn-success">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif