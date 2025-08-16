<div class="card mt-4">
    <div class="card-header d-flex justify-between">
        <h5 class="card-title mb-0">Historique des Clôtures de Caisse</h5>
        <button wire:click="exportPdf" class="btn btn-primary btn-sm">
            <span wire:loading wire:target="exportPdf" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Exporter PDF
        </button>
    </div>

    <div class="card-body">
        @if (session()->has('message'))
            <div class="alert alert-success">{{ session('message') }}</div>
        @endif

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Agent de</th>
                    <th>Date</th>
                    <th>Solde Théorique USD</th>
                    <th>Solde Théorique CDF</th>
                    <th>Solde Physique USD</th>
                    <th>Solde Physique CDF</th>
                    <th>Ecart USD</th>
                    <th>Ecart CDF</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($closings as $closing)
                    <tr>
                        <td>{{ $closing->user->name }}</td>
                        <td>{{ \Carbon\Carbon::parse($closing->closing_date)->format('d/m/Y') }}</td>
                        <td>{{ number_format($closing->logical_usd, 2) }} $</td>
                        <td>{{ number_format($closing->physical_cdf, 2) }} Fc</td>
                        <td>{{ number_format($closing->logical_usd, 2) }} $</td>
                        <td>{{ number_format($closing->physical_cdf, 2) }} Fc</td>
                        <td>{{ number_format($closing->gap_usd, 2) }} $</td>
                        <td>{{ number_format($closing->gap_cdf, 2) }} Fc</td>
                        <td>
                            @if($closing->status == 'pending')
                                <span class="badge bg-warning">En attente</span>
                            @elseif($closing->status == 'validated')
                                <span class="badge bg-success">Validée</span>
                            @else
                                <span class="badge bg-danger">Rejetée</span>
                            @endif
                        </td>
                        <td>
                            @if(auth()->user()->role === 'admin' && $closing->status === 'pending')
                                <button wire:click="validateClosing({{ $closing->id }})" class="btn btn-success btn-sm">Valider</button>

                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $closing->id }}">Rejeter</button>

                                {{-- Modal de rejet --}}
                                <div class="modal fade" id="rejectModal{{ $closing->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Motif du rejet</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <textarea wire:model.defer="rejection_reason" class="form-control" rows="3"></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button wire:click="rejectClosing({{ $closing->id }})" class="btn btn-danger" data-bs-dismiss="modal">Rejeter</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- @if(auth()->id() == $closing->user_id && $closing->status === 'pending')
                                <button wire:click="editClosing({{ $closing->id }})" class="btn btn-primary btn-sm">Modifier</button>
                            @endif --}}

                            @if($closing->status !== 'pending')
                                <a href="{{ route('cloture.print', $closing->id) }}"
                                    class="btn btn-primary btn-sm">
                                    <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>Imprimer</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        {{ $closings->links() }}
    </div>

    {{-- Modal de rejet --}}
            <!-- Modal Bootstrap -->
        <div wire:ignore.self class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier la clôture</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <h6>Billetage USD</h6>
                        <div class="row mb-3">
                            @foreach ($editBilletageUSD as $valeur => $nombre)
                                <div class="col-md-2">
                                    <label class="form-label">${{ $valeur }}</label>
                                    <input type="number" wire:model.defer="editBilletageUSD.{{ $valeur }}" class="form-control" min="0">
                                </div>
                            @endforeach
                        </div>

                        <h6>Billetage CDF</h6>
                        <div class="row mb-3">
                            @foreach ($editBilletageCDF as $valeur => $nombre)
                                <div class="col-md-2">
                                    <label class="form-label">{{ $valeur }} Fc</label>
                                    <input type="number" wire:model.defer="editBilletageCDF.{{ $valeur }}" class="form-control" min="0">
                                </div>
                            @endforeach
                        </div>

                        <div class="mb-3">
                            <label for="editNote" class="form-label">Note</label>
                            <textarea class="form-control" wire:model.defer="editNote" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button class="btn btn-primary" wire:click="updateCloture">Enregistrer</button>
                    </div>
                </div>
            </div>
        </div>
</div>
