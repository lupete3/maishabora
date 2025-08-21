<div class="mt-0">
    @can('ajouter-carnet')
    <div class="card">
        <div class="card-header bg-primary text-white">Achat de Carte d'Adhésion</div>
        <div class="card-body">
            <form wire:submit.prevent="submit">
                <div class="row mt-3">

                    <div class="col-md-6 mb-3">
                        <div class="position-relative">
                            <label>Membre</label>
                            <div class="table-search-input">
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text" id="basic-addon-search31">
                                        <i class="icon-base bx bx-search"></i></span>
                                    <input type="search" wire:model.live="search" class="form-control" 
                                        placeholder="Rechercher un membre"
                                        autocomplete="off" aria-label="Rechercher un membre" 
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
                            @error('member_id') <span class="text-danger">{{ $message }}</span> @enderror

                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label>Code de la carte</label>
                        <input type="text" wire:model="code" class="form-control" />
                        @error('code') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label>Devise</label>
                        <select wire:model="currency" class="form-control">
                            <option value="USD">USD</option>
                            <option value="CDF">CDF</option>
                        </select>
                        @error('card_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-3 mb-3">
                        <label>Prix de la carte</label>
                        <input type="number" step="0.01" wire:model="price" class="form-control" />
                        @error('card_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label>Montant quotidien à épargner</label>
                        <input type="number" step="0.01" wire:model="subscription_amount" class="form-control" />
                        @error('card_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-success">
                    <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Valider l'achat de carte</button>
            </form>
        </div>
    </div>
    @endcan

    <!-- resources/views/livewire/card-history.blade.php -->
    <div class=" mt-4">
        <div class="card">
            <div class="card-header bg-light d-flex justify-content-between">
                <div>
                    <h5>Historique des Cartes d'Adhésion</h4>
                </div>
                                <!-- Barre de recherche -->
                <div>
                    <input type="text" wire:model.live="searchCard" class="form-control" placeholder="Rechercher une carte...">
                </div>
            </div>

            <div class="card-body">
                <!-- Tableau des cartes -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Membre</th>
                                <th>Prix de la carte</th>
                                <th>Montant quotidien</th>
                                <th>Devise</th>
                                <th>Date de début</th>
                                <th>Date de fin</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cards as $index => $card)
                                <tr>
                                    <td>{{ $card->code }}</td>
                                    <td>{{ optional($card->member)->code ?? 'N/A' }} {{ optional($card->member)->name ?? 'N/A' }}
                                        {{ optional($card->member)->postnom ?? 'N/A' }} {{ optional($card->member)->prenom ?? 'N/A' }}
                                    </td>
                                    <td>{{ number_format($card->price, 2) }} {{ $card->currency }}</td>
                                    <td>{{ number_format($card->subscription_amount, 2) }} {{ $card->currency }}</td>
                                    <td>{{ $card->currency }}</td>
                                    <td>{{ \Carbon\Carbon::parse($card->start_date)->format('d/m/Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($card->end_date)->format('d/m/Y') }}</td>
                                    <td>
                                        @if ($card->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Terminée</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center">Aucune carte trouvée.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Affichage de {{ $cards->firstItem() }} à {{ $cards->lastItem() }}
                        sur {{ $cards->total() }} cartes
                    </div>
                    <div>
                        {{ $cards->links() }}
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
