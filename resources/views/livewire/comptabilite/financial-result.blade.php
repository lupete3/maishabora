<div>
    <div class="d-flex justify-content-between mb-3">
        <div>
            <input type="text" wire:model.live="search" class="form-control" placeholder="Rechercher un compte...">
        </div>
        <div>
            <select wire:model.lazy="filter_currency" class="form-select">
                <option value="">Toutes devises</option>
                <option value="USD">USD</option>
                <option value="CDF">CDF</option>
            </select>
        </div>
        <div>
            <button class="btn btn-danger" wire:click="export" wire:loading.attr="disabled">
                <i class="bx bxs-file-pdf"></i> Exporter PDF
            </button>
        </div>
    </div>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>N° Compte</th>
                <th>Intitulé</th>
                <th>Type</th>
                <th>Total Débit</th>
                <th>Total Crédit</th>
                <th>Solde Débiteur</th>
                <th>Solde Créditeur</th>
            </tr>
        </thead>
        <tbody>
            @foreach($accounts as $acc)
                @php
                    $debit = $acc->journals->sum('montant_debit');
                    $credit = $acc->journals->sum('montant_credit');
                @endphp
                <tr>
                    <td>{{ $acc->code }}</td>
                    <td>{{ $acc->intitule }}</td>
                    <td>{{ $acc->type }}</td>
                    <td>{{ number_format($debit, 2, ',', ' ') }}</td>
                    <td>{{ number_format($credit, 2, ',', ' ') }}</td>
                    <td>{{ $debit > $credit ? number_format($debit - $credit, 2, ',', ' ') : '-' }}</td>
                    <td>{{ $credit > $debit ? number_format($credit - $debit, 2, ',', ' ') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $accounts->links() }}

    <div class="mt-3">
        <h5>Totaux</h5>
        <p><strong>Actif :</strong> Débit = {{ number_format($totals['Actif']['debit'],2,',',' ') }} | Crédit = {{ number_format($totals['Actif']['credit'],2,',',' ') }} | Solde = {{ number_format($totals['Actif']['solde'],2,',',' ') }}</p>
        <p><strong>Passif :</strong> Débit = {{ number_format($totals['Passif']['debit'],2,',',' ') }} | Crédit = {{ number_format($totals['Passif']['credit'],2,',',' ') }} | Solde = {{ number_format($totals['Passif']['solde'],2,',',' ') }}</p>
    </div>
</div>