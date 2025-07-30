<!-- resources/views/livewire/grant-credit.blade.php -->
<div class="mt-0">
    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <h3>Gestion des crédits</h3>

    <div class="card" wire:ignore.self>
        <div class="card-header bg-primary text-white">Octroyer un Crédit</div>
        <div class="card-body">
            <form wire:submit.prevent="submit">
                <div class="row mt-3">
                    <div class="col-md-6 mb-3">
                        <div class="position-relative">
                            <label>Membre</label>
                            <div class="table-search-input">
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text" id="basic-addon-search31"><i
                                            class="icon-base bx bx-search"></i></span>
                                    <input type="search" wire:model.live="search" class="form-control"
                                        placeholder="Rechercher Membre....." aria-label="Rechercher Membre....."
                                        aria-describedby="basic-addon-search31">
                                </div>
                            </div>

                            @if (!empty($results))
                                <ul class="list-group w-100" style="z-index: 1000;">
                                    @foreach ($results as $user)
                                        <li class="list-group-item list-group-item-action"
                                            wire:click="selectResult({{ $user['id'] }})">
                                            {{ "{$user['code']} {$user['name']} {$user['postnom']}" }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            @error('member_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror

                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label>Devise</label>
                        <select wire:model="currency" class="form-control">
                            <option value="USD">USD</option>
                            <option value="CDF">CDF</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label>Montant</label>
                        <input type="number" step="0.01" wire:model="amount" class="form-control" />
                        @error('amount')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Taux d'intérêt (%)</label>
                        <input type="number" step="0.01" wire:model="interest_rate" class="form-control" />
                        @error('interest_rate')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Fréquence des échéances</label>
                        <select wire:model="frequency" class="form-control">
                            <option value="daily">Quotidienne</option>
                            <option value="weekly">Hebdomadaire</option> <!-- AJOUT -->
                            <option value="monthly">Mensuelle</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Nombre d'échéances</label>
                        <input type="number" wire:model="installments" class="form-control" />
                        @error('installments')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Date de début</label>
                        <input type="date" wire:model="start_date" class="form-control" />
                        @error('start_date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-8 mb-3">
                        <label>Description (facultatif)</label>
                        <input type="text" wire:model="description" class="form-control" />
                    </div>

                    <div class="col-md-12">
                        <button type="submit" class="btn btn-success float-end">
                            <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Valider le Crédit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>
