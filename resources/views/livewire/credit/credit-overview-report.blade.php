<div class="mt-4">
    <div class="card">
        <div class="card-header justify-content-between d-flex flex-wrap align-items-center">
            <div class="d-flex flex-column flex-md-row gap-2 align-items-center">
                <p class="mb-0">📊 Rapport Global - Retard de Remboursement des Crédits</p>

                {{-- Filtre devise --}}
                <div class="ms-md-3">
                    <label for="devise" class="me-1 fw-bold">Devise :</label>
                    <select wire:model.lazy="selectedCurrency" id="devise" class="form-select form-select-sm d-inline w-auto">
                        <option value="all">Toutes les devises</option>
                        @foreach ($currencies as $currency)
                            <option value="{{ $currency }}">{{ $currency }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Bouton Export PDF --}}
            <a href="{{ route('credits-retard.pdf', ['devise' => $selectedCurrency]) }}" class="btn btn-primary btn-sm" target="_blank">
                📄 Exporter en PDF
            </a>
        </div>

        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover table-sm" >
                <thead class="table-light text-center">
                    <tr>
                        <th>ID Crédit</th>
                        <th>Membre</th>
                        <th>Date du Crédit</th>
                        <th>Date Payement</th>
                        <th>Montant Crédit</th>
                        <th>Solde Restant</th>
                        <th>Pénalités</th>
                        <th>% Pénalités</th>
                        <th>Jours Retard</th>
                        <th>1-30j</th>
                        <th>31-60j</th>
                        <th>61-90j</th>
                        <th>91-180j</th>
                        <th>181-360j</th>
                        <th>361-720j</th>
                        <th>>720j</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($credits as $credit)
                        @php $d = $this->getCreditDetails($credit); @endphp
                        <tr>
                            <td>{{ $d['credit_id'] }}</td>
                            <td>{{ $d['member_name'] }}</td>
                            <td>{{ \Carbon\Carbon::parse($d['credit_date'])->format('d/m/Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($d['credit_payment'])->format('d/m/Y') }}</td>
                            <td>{{ number_format($d['credit_amount'], 2) }} {{ $credit->currency }}</td>
                            <td>{{ number_format($d['remaining_balance'], 2) }} {{ $credit->currency }}</td>
                            <td>{{ number_format($d['total_penalty'], 2) }} {{ $credit->currency }}</td>
                            <td>{{ $d['penalty_percentage'] }}%</td>
                            <td class="text-center">{{ $d['days_late'] }}</td>
                            <td>{{ $d['range_1'] ? number_format($d['range_1'], 2) : '' }}</td>
                            <td>{{ $d['range_2'] ? number_format($d['range_2'], 2) : '' }}</td>
                            <td>{{ $d['range_3'] ? number_format($d['range_3'], 2) : '' }}</td>
                            <td>{{ $d['range_4'] ? number_format($d['range_4'], 2) : '' }}</td>
                            <td>{{ $d['range_5'] ? number_format($d['range_5'], 2) : '' }}</td>
                            <td>{{ $d['range_6'] ? number_format($d['range_6'], 2) : '' }}</td>
                            <td>{{ $d['range_7'] ? number_format($d['range_7'], 2) : '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="16" class="text-center">Aucun crédit en retard.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="4">Totaux</th>
                        <th>{{ number_format($totaux['credit_amount'], 2) }}</th>
                        <th>{{ number_format($totaux['remaining_balance'], 2) }}</th>
                        <th>{{ number_format($totaux['total_penalty'], 2) }}</th>
                        <th></th>
                        <th></th>
                        <th>{{ number_format($totaux['range_1'], 2) }}</th>
                        <th>{{ number_format($totaux['range_2'], 2) }}</th>
                        <th>{{ number_format($totaux['range_3'], 2) }}</th>
                        <th>{{ number_format($totaux['range_4'], 2) }}</th>
                        <th>{{ number_format($totaux['range_5'], 2) }}</th>
                        <th>{{ number_format($totaux['range_6'], 2) }}</th>
                        <th>{{ number_format($totaux['range_7'], 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
