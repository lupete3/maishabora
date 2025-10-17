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

                    <div class="col-md-6 mb-3">
                        <label>Type de conversion</label>
                        <select class="form-select" wire:model.lazy="conversion_type">
                            <option value="central">Caisse centrale</option>
                            <option value="client">Compte client</option>
                        </select>
                    </div>

                    @if ($conversion_type === 'client')

                        <div class="col-md-6 mb-3">
                            <div class="position-relative">
                                <label class="form-label fw-bold">Rechercher le client</label>
                                <div class="table-search-input">
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text" id="basic-addon-search31"><i
                                                class="icon-base bx bx-search"></i></span>
                                        <input type="search" wire:model.live="searchclient" class="form-control"
                                            placeholder="Rechercher Client....." aria-label="Rechercher Client....."
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
                                @error('selected_user_id')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror

                            </div>
                        </div>

                    @endif
                </div>

                <div class="row mb-3 mt-3">
                    <div class="col-md-4">
                        <label>De (Devise Source)</label>
                        <select class="form-select" wire:model="from_currency">
                            <option value="USD">USD ({{ number_format($balances['USD']->balance ?? 0, 2) }})</option>
                            <option value="CDF">CDF ({{ number_format($balances['CDF']->balance ?? 0, 2) }})</option>
                        </select>
                        @error('from_currency')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label>Vers (Devise Cible)</label>
                        <select class="form-select" wire:model="to_currency">
                            <option value="USD">USD</option>
                            <option value="CDF">CDF</option>
                        </select>
                        @error('to_currency')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label>Montant à convertir ({{ $from_currency }})</label>
                        <input type="number" step="0.01" wire:model="amount" class="form-control">
                        @error('amount')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                @if ($exchange_rate)
                    <div class="alert alert-info">
                        Taux actuel : 1 {{ $from_currency }} = {{ $exchange_rate }} {{ $to_currency }}
                    </div>
                @endif

                <button type="button" wire:click="showConfirmation" wire:loading.attr="disabled"
                    class="btn btn-primary">
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
                        @if ($conversion->paired_entry)
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

    {{-- Modal de confirmation avant conversion --}}
    <div class="modal fade @if ($showConfirmationModal) show d-block @endif" tabindex="-1"
        @if ($showConfirmationModal) style="background-color: rgba(0,0,0,0.5);" @endif role="dialog"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-warning text-dark rounded-top-4">
                    <h5 class="modal-title fw-bold">Confirmer la conversion</h5>
                    <button type="button" class="btn-close" wire:click="$set('showConfirmationModal', false)"></button>
                </div>

                <div class="modal-body">
                    <p class="fw-semibold mb-3">Veuillez vérifier les détails avant de confirmer :</p>
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Type :</span>
                            <span class="fw-bold text-capitalize">{{ $conversion_type }}</span>
                        </li>
                        @if ($conversion_type === 'client' && $selected_user_id)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Client :</span>
                                <span class="fw-bold">
                                    {{ \App\Models\User::find($selected_user_id)?->name }}
                                    {{ \App\Models\User::find($selected_user_id)?->postnom }}
                                </span>
                            </li>
                        @endif
                        <li class="list-group-item d-flex justify-content-between">
                            <span>De :</span>
                            <span class="fw-bold">{{ $from_currency }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Vers :</span>
                            <span class="fw-bold">{{ $to_currency }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Montant :</span>
                            <span class="fw-bold text-primary">{{ number_format($amount, 2) }}
                                {{ $from_currency }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Taux appliqué :</span>
                            <span class="fw-bold">{{ $exchange_rate }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Montant reçu :</span>
                            <span class="fw-bold text-success">
                                {{ number_format($amount * $exchange_rate, 2) }} {{ $to_currency }}
                            </span>
                        </li>
                    </ul>
                    <p class="text-muted small mb-0">Assurez-vous que toutes les informations sont correctes avant de
                        poursuivre.</p>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary"
                        wire:click="$set('showConfirmationModal', false)">
                        Annuler
                    </button>
                    <button type="button" class="btn btn-success" wire:click="confirmConversion" wire:loading.attr="disabled">
                        <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Confirmer la conversion
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
