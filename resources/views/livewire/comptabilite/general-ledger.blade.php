<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-book"></i> Grand Livre</h4>
            @if($compte_id)
                <button wire:click="exportPDF" class="btn btn-sm btn-danger">
                    <i class="fas fa-file-pdf"></i> Exporter PDF
                </button>
            @endif
        </div>
        <div class="card-body">
            {{-- Filtres --}}
            <div class="row mb-4">
                <div class="col-md-4">
                    <label>Compte</label>
                    <select wire:model.live="compte_id" class="form-control">
                        <option value="">Sélectionner un compte</option>
                        @foreach($comptes as $c)
                            <option value="{{ $c->id }}">{{ $c->getDisplayName() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label>Devise</label>
                    <select wire:model.live="devise" class="form-control">
                        @foreach($currencies as $currency)
                            <option value="{{ $currency }}">{{ $currency }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label>Période</label>
                    <select wire:model.live="period_type" class="form-control">
                        <option value="jour">Aujourd'hui</option>
                        <option value="semaine">Cette semaine</option>
                        <option value="mois">Ce mois</option>
                        <option value="trimestre">Ce trimestre</option>
                        <option value="annee">Cette année</option>
                        <option value="tout">Tout</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label>Date début</label>
                    <input type="date" wire:model.live="date_debut" class="form-control" />
                </div>

                <div class="col-md-2">
                    <label>Date fin</label>
                    <input type="date" wire:model.live="date_fin" class="form-control" />
                </div>
            </div>

            @if($compte)
                {{-- Informations du compte --}}
                <div class="alert alert-info">
                    <strong>{{ $compte->code }} - {{ $compte->intitule }}</strong><br>
                    <small>{{ $compte->getHierarchyPath() }}</small><br>
                    <small>Type: {{ $compte->type }} | Devise: {{ $devise }}</small>
                </div>

                {{-- Solde initial --}}
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>Solde initial</strong><br>
                                        @if($date_debut)
                                            <small class="text-muted">Au {{ \Carbon\Carbon::parse($date_debut)->format('d/m/Y') }}</small>
                                        @endif
                                    </div>
                                    <div class="h4 mb-0 {{ $soldeInitial >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format(abs($soldeInitial), 2, ',', ' ') }} {{ $devise }}
                                        @if($soldeInitial >= 0)
                                            <small class="text-muted">(Débiteur)</small>
                                        @else
                                            <small class="text-muted">(Créditeur)</small>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tableau des écritures --}}
                @if($journals->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>Date</th>
                                    <th>Libellé</th>
                                    <th>Référence</th>
                                    <th class="text-right">Débit</th>
                                    <th class="text-right">Crédit</th>
                                    <th class="text-right">Solde</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($journals as $journal)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($journal->date_operation)->format('d/m/Y') }}</td>
                                        <td>{{ $journal->libelle }}</td>
                                        <td><small class="text-muted">{{ $journal->reference }}</small></td>
                                        <td class="text-right">
                                            @if($journal->montant_debit > 0)
                                                {{ number_format($journal->montant_debit, 2, ',', ' ') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @if($journal->montant_credit > 0)
                                                {{ number_format($journal->montant_credit, 2, ',', ' ') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-right font-weight-bold {{ $journal->solde_progressif >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format(abs($journal->solde_progressif), 2, ',', ' ') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td colspan="3" class="text-right"><strong>SOLDE FINAL</strong></td>
                                    <td class="text-right">
                                        {{ number_format($journals->sum('montant_debit'), 2, ',', ' ') }}
                                    </td>
                                    <td class="text-right">
                                        {{ number_format($journals->sum('montant_credit'), 2, ',', ' ') }}
                                    </td>
                                    <td class="text-right {{ $soldeFinal >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format(abs($soldeFinal), 2, ',', ' ') }} {{ $devise }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="mt-3">
                        {{ $journals->links() }}
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle"></i> Aucune écriture trouvée pour ce compte sur la période sélectionnée.
                    </div>
                @endif
            @else
                <div class="alert alert-secondary text-center">
                    <i class="fas fa-arrow-up"></i> Veuillez sélectionner un compte pour afficher son grand livre
                </div>
            @endif
        </div>
    </div>
</div>
