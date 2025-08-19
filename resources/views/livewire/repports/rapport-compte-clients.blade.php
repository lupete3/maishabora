<div>
    <h4 class="card-title">Soldes des Membres</h4>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card text-center bg-light shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Total USD</h5>
                    <h3 class="text-success">{{ number_format($globalUsd, 2) }} $</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-center bg-light shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Total CDF</h5>
                    <h3 class="text-primary">{{ number_format($globalCdf, 2) }} CDF</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="row mb-3">
                <div class="col-md-12">
                    <input type="text" wire:model.live="search" id="search" class="form-control"
                        placeholder="Rechercher un membre (nom, code, prénom)...">
                </div>
            </div>
            <div class="mb-3">
                <button wire:click="exportPdf" class="btn btn-danger" wire:loading.attr="disabled">
                    <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                    <i class="bi bi-file-earmark-pdf"></i> Exporter en PDF
                </button>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Membre</th>
                        <th>Solde USD</th>
                        <th>Solde CDF</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($balances as $balance)
                        <tr>
                            <td>{{ $balance['member']->code }}</td>
                            <td>{{ $balance['member']->name . ' ' . $balance['member']->postnom . ' ' . $balance['member']->prenom }}
                            </td>
                            <td>{{ number_format($balance['usd_balance'], 2) }}</td>
                            <td>{{ number_format($balance['cdf_balance'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-3">
                {{ $members->links() }}
            </div>

        </div>
    </div>

</div>
