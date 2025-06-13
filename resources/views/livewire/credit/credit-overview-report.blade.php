<div class="mt-4">
    <div class="card">
        <div class="card-header">📊 Rapport Global - Retard de Remboursement des Crédits <a href="{{ route('credits-retard.pdf') }}" class="btn btn-danger mb-3" target="_blank">
    📄 Exporter en PDF
</a>
</div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover table-sm">
                <thead class="table-light text-center">
                    <tr>
                        <th>ID Crédit</th>
                        <th>Membre</th>
                        <th>Date du Crédit</th>
                        <th>Montant Crédit</th>
                        <th>Solde Restant</th>
                        <th>Pénalités</th>
                        <th>% Pénalités</th>
                        <th>Jours de Retard</th>
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
                        <tr><td colspan="15" class="text-center">Aucun crédit en retard.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="3">Totaux</th>
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
