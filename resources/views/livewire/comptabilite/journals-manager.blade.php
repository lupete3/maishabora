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
        <div class="card-header border-bottom">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <!-- Filtres de date et recherche -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <select wire:model.live="filterType" class="form-select form-select-sm" style="width: auto;">
                        <option value="day">Aujourd'hui</option>
                        <option value="week">Cette semaine</option>
                        <option value="month">Ce mois</option>
                        <option value="range">Intervalle personnalisé</option>
                    </select>

                    @if ($filterType === 'range')
                        <div class="d-flex align-items-center gap-1">
                            <input type="date" wire:model.live="startDate" class="form-control form-control-sm"
                                style="width: auto;">
                            <span class="small text-muted">au</span>
                            <input type="date" wire:model.live="endDate" class="form-control form-control-sm"
                                style="width: auto;">
                        </div>
                    @endif

                    <div class="input-group input-group-merge" style="width: 250px;">
                        <span class="input-group-text"><i class="bx bx-search-alt"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search"
                            class="form-control form-control-sm shadow-none" placeholder="Recherche...">
                    </div>
                </div>

                <!-- Autres filtres et exports -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <select wire:model.live="filter_journal_type" class="form-select form-select-sm"
                        style="width: auto;">
                        <option value="">-- Journaux --</option>
                        @foreach ($journalTypes as $jt)
                            <option value="{{ $jt->id }}">{{ $jt->libelle }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filter_account" class="form-select form-select-sm"
                        style="width: auto; max-width: 250px;">
                        <option value="">-- Comptes --</option>
                        @foreach ($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->intitule }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filter_currency" class="form-select form-select-sm" style="width: auto;">
                        <option value="">-- Devises --</option>
                        @foreach ($currencies as $cur)
                            <option value="{{ $cur }}">{{ $cur }}</option>
                        @endforeach
                    </select>

                    <div class="d-flex gap-2">
                        @if ($journals->count() > 0)
                            <button wire:click="export" class="btn btn-sm btn-danger" wire:loading.attr="disabled">
                                <span wire:loading wire:target="export" class="spinner-border spinner-border-sm me-1"
                                    role="status"></span>
                                <i class="bx bxs-file-pdf"></i> PDF
                            </button>
                            <button wire:click="exportExcel" class="btn btn-sm btn-success" wire:loading.attr="disabled">
                                <span wire:loading wire:target="exportExcel"
                                    class="spinner-border spinner-border-sm me-1" role="status"></span>
                                <i class="bx bx-table"></i> Excel
                            </button>
                        @endif
                    </div>
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
                                            <select class="form-select" wire:model.lazy="lines.{{ $index }}.compte_id">
                                                <option value="">-- Compte --</option>
                                                @foreach ($accounts as $acc)
                                                    <option value="{{ $acc->id }}">{{ $acc->code }} -
                                                        {{ $acc->intitule }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select" wire:model.lazy="lines.{{ $index }}.type_operation">
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
