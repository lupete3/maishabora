<div>
    <div class="d-flex justify-content-between mb-3">
        <h5 class="mb-0">📒 Journaux Comptables</h5>
        @can('ajouter-ecriture-journal', App\Models\User::class)
            <button class="btn btn-primary" wire:click="$dispatch('openModal', {name: 'journalModal'})">
                <i class="bx bx-plus"></i> Nouvelle Écriture
            </button>
        @endcan
    </div>

    <div class="card">
        <!-- Recherche -->
    <div class="card-header">
        <div class="row">
            <div class="col-md-3">
                <input type="text" wire:model.live="search" class="form-control" placeholder="Recherche...">
            </div>
            <div class="col-md-3">
                <select wire:model.lazy="filter_journal_type" class="form-control">
                    <option value="">-- Tous les journaux --</option>
                    @foreach ($journalTypes as $jt)
                        <option value="{{ $jt->id }}">{{ $jt->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select wire:model.lazy="filter_account" class="form-control">
                    <option value="">-- Tous les comptes --</option>
                    @foreach ($accounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->intitule }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select wire:model.lazy="filter_currency" class="form-control">
                    <option value="">-- Toutes les devises --</option>
                    @foreach($currencies as $cur)
                        <option value="{{ $cur }}">{{ $cur }}</option>
                    @endforeach
                    
                </select>
            </div>
            <div class="col-md-12 text-end mt-2">
                <button wire:click="export" class="btn btn-sm btn-success" wire:loading.attr="disabled">
                    <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                    <i class="bx bx-download"></i> Exporter PDF
                </button>
            </div>
        </div>
    </div>
    <hr>

    <!-- Liste des journaux -->
    <div class="table-responsive">
        <table class="table table-striped">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Référence</th>
                    <th>Libellé</th>
                    <th>Type</th>
                    <th>Compte</th>
                    <th>Débit</th>
                    <th>Crédit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($journals as $j)
                    <tr>
                        <td>{{ $j->date_operation }}</td>
                        <td>{{ $j->reference }}</td>
                        <td>{{ $j->libelle }}</td>
                        <td>{{ $j->journalType->libelle ?? '-' }}</td>
                        <td>{{ $j->account->code }} - {{ $j->account->intitule }}</td>
                        <td class="text-success">{{ number_format($j->montant_debit, 2, ',', ' ') }}</td>
                        <td class="text-danger">{{ number_format($j->montant_credit, 2, ',', ' ') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">Aucune écriture trouvée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        {{ $journals->links() }}
    </div>
    </div>

    <!-- Modal Saisie -->
    <div wire:ignore.self class="modal fade" id="journalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form wire:submit.prevent="save" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvelle Écriture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label>Date</label>
                            <input type="date" class="form-control" wire:model="date_operation">
                        </div>
                        <div class="col-md-3">
                            <label>Référence</label>
                            <input type="text" class="form-control" wire:model="reference">
                        </div>
                        <div class="col-md-3">
                            <label>Devise</label>
                            <select class="form-select" wire:model.lazy="devise">
                                <option value="">-- Choisir --</option>
                                <option value="USD">USD</option>
                                <option value="CDF">CDF</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Type Journal</label>
                            <select class="form-select" wire:model.lazy="type_journal_id">
                                <option value="">-- Choisir --</option>
                                @foreach ($journalTypes as $t)
                                    <option value="{{ $t->id }}">{{ $t->libelle }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Libellé</label>
                        <input type="text" class="form-control" wire:model="libelle">
                    </div>

                    <!-- Lignes Débit/Crédit -->
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Compte</th>
                                    <th>Type</th>
                                    <th>Montant</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lines as $index => $line)
                                    <tr>
                                        <td>
                                            <select class="form-select"
                                                wire:model.lazy="lines.{{ $index }}.compte_id">
                                                <option value="">-- Compte --</option>
                                                @foreach ($accounts as $acc)
                                                    <option value="{{ $acc->id }}">{{ $acc->code }} -
                                                        {{ $acc->intitule }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select"
                                                wire:model.lazy="lines.{{ $index }}.type_operation">
                                                <option value="debit">Débit</option>
                                                <option value="credit">Crédit</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" class="form-control"
                                                wire:model="lines.{{ $index }}.montant">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger"
                                                wire:click="removeLine({{ $index }})">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addLine">
                            + Ajouter une ligne
                        </button>
                    </div>

                    <!-- Alerte équilibre -->
                    <div class="mt-3">
                        @if ($this->isBalanced)
                            <div class="alert alert-success">✅ L'écriture est équilibrée</div>
                        @else
                            <div class="alert alert-danger">⚠️ L'écriture n'est pas équilibrée</div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    @can('ajouter-ecriture-journal', App\Models\User::class)
                        <button type="submit" class="btn btn-primary">
                            Valider l'écriture
                        </button>
                    @endcan
                </div>
            </form>
        </div>
    </div>
</div>
