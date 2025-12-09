<div>
    <div class="d-flex justify-content-between mb-3">
        <div>
            <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Rechercher un compte...">
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

    {{-- Tableau principal avec 4 colonnes --}}
    <table class="table table-bordered text-center align-middle">
        <thead class="table-light">
            <tr>
                <th colspan="3">ACTIFS</th>
                <th colspan="3">PASSIFS</th>
                <th colspan="3">PRODUITS</th>
                <th colspan="3">CHARGES</th>
            </tr>
            <tr>
                {{-- Actifs --}}
                <th>Code</th>
                <th>Intitulé</th>
                <th>Solde</th>
                {{-- Passifs --}}
                <th>Code</th>
                <th>Intitulé</th>
                <th>Solde</th>
                {{-- Produits --}}
                <th>Code</th>
                <th>Intitulé</th>
                <th>Solde</th>
                {{-- Charges --}}
                <th>Code</th>
                <th>Intitulé</th>
                <th>Solde</th>
            </tr>
        </thead>
        <tbody>
            @php
                $max = max(
                    $accounts->where('type', 'Actif')->count(),
                    $accounts->where('type', 'Passif')->count(),
                    $accounts->where('type', 'Produit')->count(),
                    $accounts->where('type', 'Charge')->count(),
                );
            @endphp

            @for ($i = 0; $i < $max; $i++)
                <tr>
                    {{-- Actif --}}
                    @if (isset($accounts->where('type', 'Actif')->values()[$i]))
                        @php $a = $accounts->where('type','Actif')->values()[$i]; @endphp
                        <td>{{ $a->code }}</td>
                        <td>{{ $a->intitule }}</td>
                        <td>{{ number_format($a->journals->sum('montant_debit') - $a->journals->sum('montant_credit'), 2, ',', ' ') }}
                        </td>
                    @else
                        <td colspan="3"></td>
                    @endif

                    {{-- Passif --}}
                    @if (isset($accounts->where('type', 'Passif')->values()[$i]))
                        @php $p = $accounts->where('type','Passif')->values()[$i]; @endphp
                        <td>{{ $p->code }}</td>
                        <td>{{ $p->intitule }}</td>
                        <td>{{ number_format($p->journals->sum('montant_debit') - $p->journals->sum('montant_credit'), 2, ',', ' ') }}
                        </td>
                    @else
                        <td colspan="3"></td>
                    @endif

                    {{-- Produit --}}
                    @if (isset($accounts->where('type', 'Produit')->values()[$i]))
                        @php $pr = $accounts->where('type','Produit')->values()[$i]; @endphp
                        <td>{{ $pr->code }}</td>
                        <td>{{ $pr->intitule }}</td>
                        <td>{{ number_format($pr->journals->sum('montant_debit') - $pr->journals->sum('montant_credit'), 2, ',', ' ') }}
                        </td>
                    @else
                        <td colspan="3"></td>
                    @endif

                    {{-- Charge --}}
                    @if (isset($accounts->where('type', 'Charge')->values()[$i]))
                        @php $c = $accounts->where('type','Charge')->values()[$i]; @endphp
                        <td>{{ $c->code }}</td>
                        <td>{{ $c->intitule }}</td>
                        <td>{{ number_format($c->journals->sum('montant_debit') - $c->journals->sum('montant_credit'), 2, ',', ' ') }}
                        </td>
                    @else
                        <td colspan="3"></td>
                    @endif
                </tr>
            @endfor
        </tbody>
        <tfoot class="fw-bold table-secondary">
            <tr>
                <td colspan="2">Total Actif</td>
                <td>{{ number_format($totals['Actif']['solde'], 2, ',', ' ') }}</td>

                <td colspan="2">Total Passif</td>
                <td>{{ number_format($totals['Passif']['solde'], 2, ',', ' ') }}</td>

                <td colspan="2">Total Produits</td>
                <td>{{ number_format($totals['Produit']['solde'], 2, ',', ' ') }}</td>

                <td colspan="2">Total Charges</td>
                <td>{{ number_format($totals['Charge']['solde'], 2, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Résumés --}}
    <div class="mt-3">
        <div class="alert alert-info">
            Différence Bilan (Actif - Passif) :
            <strong>{{ number_format($differences['bilan'], 2, ',', ' ') }}</strong>
        </div>
        <div class="alert alert-success">
            Résultat Net (Produits - Charges) :
            <strong>{{ number_format($differences['resultat'], 2, ',', ' ') }}</strong>
        </div>
    </div>
</div>
