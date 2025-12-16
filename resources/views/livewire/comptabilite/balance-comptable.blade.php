<div>
    <h5 class="mb-4">Balance Comptable</h5>
    <div class="card">

        <div class="card-header">
            <div class="row">
                <div class="col-md-6 mt-2">
                    <input type="text" class="form-control" placeholder="Rechercher (code, intitulé)..."
                        wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-3 mt-2">
                    <select class="form-select" wire:model.lazy="filter_devise" style="min-width: 140px;">
                        <option value="">Toutes devises</option>
                        @foreach ($currencies as $cur)
                            <option value="{{ $cur }}">{{ $cur }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mt-2">
                    <button class="btn btn-sm btn-warning float-end" wire:click="exportPdf"
                        wire:loading.attr="disabled">
                        <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                        <i class="bx bx-download"></i> Exporter PDF
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>N° Compte</th>
                        <th>Intitulé du compte</th>
                        <th>Total Débit</th>
                        <th>Total Crédit</th>
                        <th>Solde Débiteur</th>
                        <th>Solde Créditeur</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($comptesData as $row)
                        <tr>
                            <td>{{ $row['code'] }}</td>
                            <td>{{ $row['intitule'] }}</td>
                            <td class="text-end">{{ number_format($row['total_debit'], 2, ',', ' ') }}</td>
                            <td class="text-end">{{ number_format($row['total_credit'], 2, ',', ' ') }}</td>
                            <td class="text-end">
                                {{ $row['solde_debiteur'] > 0 ? number_format($row['solde_debiteur'], 2, ',', ' ') : '' }}
                            </td>
                            <td class="text-end">
                                {{ $row['solde_crediteur'] > 0 ? number_format($row['solde_crediteur'], 2, ',', ' ') : '' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">Aucun compte</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $comptes->links() }}
        </div>
    </div>
</div>
