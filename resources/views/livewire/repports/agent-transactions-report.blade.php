<div>
    <div class="row mb-3">
        <div class="col">
            <select wire:model.lazy="agentId" class="form-select">
                <option value="">-- Tous les agents --</option>
                @foreach($agents as $agent)
                    <option value="{{ $agent->id }}">{{ $agent->name.' '.$agent->postnom.' '.$agent->prenom }}</option>
                @endforeach
            </select>
        </div>
        <div class="col">
            <select wire:model.lazy="currency" class="form-select">
                <option value="">-- Toutes les devises --</option>
                <option value="USD">USD</option>
                <option value="CDF">CDF</option>
            </select>
        </div>
        <div class="col">
            <select wire:model.lazy="period" class="form-select">
                <option value="day">Aujourd'hui</option>
                <option value="week">Cette semaine</option>
                <option value="month">Ce mois</option>
                <option value="year">Cette année</option>
                <option value="interval">Intervalle</option>
            </select>
        </div>
        @if($period === 'interval')
            <div class="col">
                <input type="date" wire:model.lazy="dateStart" class="form-control">
            </div>
            <div class="col">
                <input type="date" wire:model.lazy="dateEnd" class="form-control">
            </div>
        @endif
    </div>

    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card p-3 bg-primary text-white">
                <strong>Total Transactions :</strong> {{ number_format($totals['total'], 2) }}
            </div>
        </div>
        <div class="col-md-4 mt-4">
            <button wire:click="exportPdf" class="btn btn-primary " wire:loading.attr="disabled">
                <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                <i class="bx bx-download"></i> Télécharger PDF
            </button>
        </div>
    </div>

    

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Agent</th>
                            <th>Type</th>
                            <th>Devise</th>
                            <th>Montant</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $tx)
                            <tr>
                                <td>{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $tx->user->name.' '.$tx->user->postom.' '.$tx->user->prenom }}</td>
                                <td>{{ $tx->type }}</td>
                                <td>{{ $tx->currency }}</td>
                                <td>{{ number_format($tx->amount, 2) }}</td>
                                <td>{{ $tx->description }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Aucune transaction trouvée</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $transactions->links() }}
            </div>
        </div>
    </div>
</div>

