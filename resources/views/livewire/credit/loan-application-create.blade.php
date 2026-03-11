<div class="container-fluid mt-4">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Demande de Crédit</h5>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="mb-3 position-relative">
                    <label class="form-label" for="member_search">Rechercher un Emprunteur (Nom, Postnom ou
                        Code)</label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" id="member_search" wire:model.live="search_member"
                            placeholder="Saisissez au moins 2 caractères..." autocomplete="off">
                        @if($selected_member_name)
                            <button class="btn btn-outline-secondary" type="button" wire:click="clearMember">
                                <i class="bx bx-x"></i>
                            </button>
                        @endif
                    </div>

                    @if(!empty($member_results))
                        <div class="dropdown-menu show shadow-lg border-0 w-100 mt-1 py-0 overflow-hidden"
                            style="display: block; max-height: 300px; overflow-y: auto; z-index: 1050;">
                            <div class="list-group list-group-flush">
                                @foreach($member_results as $member)
                                    <button type="button" class="list-group-item list-group-item-action border-0 py-2"
                                        wire:click="selectMember({{ $member['id'] }}, '{{ $member['name'] }} {{ $member['postnom'] }}', '{{ $member['code'] }}')">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-bold text-primary">{{ $member['name'] }} {{ $member['postnom'] }}
                                                </div>
                                                <small class="text-muted">{{ $member['prenom'] }}</small>
                                            </div>
                                            <span class="badge bg-label-info">{{ $member['code'] }}</span>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @elseif(strlen($search_member) >= 2)
                        <div class="dropdown-menu show shadow-lg border-0 w-100 mt-1 p-3 text-center"
                            style="display: block;">
                            <small class="text-muted">Aucun membre trouvé pour "{{ $search_member }}"</small>
                        </div>
                    @endif

                    @if($selected_member_name)
                        <div class="mt-2 text-end">
                            <span class="badge bg-label-success">
                                <i class="bx bx-check-circle me-1"></i> [{{ $selected_member_code }}]
                                {{ $selected_member_name }}
                            </span>
                        </div>
                    @endif

                    <input type="hidden" wire:model="user_id">
                    @error('user_id') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0" for="business_id">Business / Activité</label>
                        @if($user_id && !$is_creating_business)
                            <button type="button" class="btn btn-xs btn-outline-primary"
                                wire:click="toggleBusinessCreation">
                                <i class="bx bx-plus me-1"></i> Nouveau Business
                            </button>
                        @endif
                    </div>

                    @if($is_creating_business)
                        <div class="card bg-label-primary border-primary p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 text-primary fw-bold">Nouveau Business</h6>
                                <button type="button" class="btn btn-sm btn-icon btn-link"
                                    wire:click="toggleBusinessCreation">
                                    <i class="bx bx-x text-primary"></i>
                                </button>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Type d'Activité</label>
                                    <input type="text" class="form-control form-control-sm" wire:model="new_business_type"
                                        placeholder="Ex: Boutique">
                                    @error('new_business_type') <span class="text-danger x-small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Secteur</label>
                                    <input type="text" class="form-control form-control-sm" wire:model="new_business_sector"
                                        placeholder="Ex: Commerce">
                                    @error('new_business_sector') <span class="text-danger x-small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Localisation</label>
                                    <input type="text" class="form-control form-control-sm"
                                        wire:model="new_business_location" placeholder="Ex: Marché Central">
                                    @error('new_business_location') <span class="text-danger x-small">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-12 text-end">
                                    <button type="button" class="btn btn-primary btn-sm" wire:click="createBusiness">
                                        Créer & Sélectionner
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <select class="form-select @error('business_id') is-invalid @enderror" id="business_id"
                            wire:model.live="business_id">
                            <option value="">Sélectionner un business</option>
                            @forelse($businesses as $business)
                                <option value="{{ $business->id }}">{{ $business->type_activite }} - {{ $business->secteur }}
                                    ({{ $business->localisation }})
                                </option>
                            @empty
                                @if($user_id)
                                    <option disabled>Aucun business enregistré pour ce membre</option>
                                @else
                                    <option disabled>Veuillez d'abord sélectionner un membre</option>
                                @endif
                            @endforelse
                        </select>
                        @if (session()->has('business_message'))
                            <div class="text-success x-small mt-1"><i
                                    class="bx bx-check me-1"></i>{{ session('business_message') }}</div>
                        @endif
                        @error('business_id') <span class="text-danger small">{{ $message }}</span> @enderror
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label">Devise</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input wire:model.live="currency" class="form-check-input" type="radio" value="USD"
                                id="currencyUSD">
                            <label class="form-check-label" for="currencyUSD">USD</label>
                        </div>
                        <div class="form-check">
                            <input wire:model.live="currency" class="form-check-input" type="radio" value="CDF"
                                id="currencyCDF">
                            <label class="form-check-label" for="currencyCDF">CDF</label>
                        </div>
                    </div>
                    @error('currency') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="montant_demande">Montant Demandé</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="montant_demande" wire:model="montant_demande"
                            placeholder="Montant">
                        <span class="input-group-text">{{ $currency }}</span>
                    </div>
                    @error('montant_demande') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="duree_mois">Durée (Mois)</label>
                    <input type="number" class="form-control" id="duree_mois" wire:model="duree_mois"
                        placeholder="Durée">
                    @error('duree_mois') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="date_demande">Date de Demande</label>
                    <input type="date" class="form-control" id="date_demande" wire:model="date_demande">
                    @error('date_demande') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form>
            @if (session()->has('message'))
                <div class="alert alert-success mt-3">
                    {{ session('message') }}
                </div>
            @endif
        </div>
    </div>
</div>