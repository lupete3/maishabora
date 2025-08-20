<div>
    <div class="row mb-4">
    @foreach ($totals as $currency => $t)
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">Devise : {{ $currency }}</h6>
                    <p class="mb-1 text-success">
                        <strong>Total Remboursé :</strong> {{ number_format($t['total_paid'], 2) }}
                    </p>
                    <p class="mb-0 text-danger">
                        <strong>Total Pénalités :</strong> {{ number_format($t['total_penality'], 2) }}
                    </p>
                </div>
            </div>
        </div>
    @endforeach
</div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Rapport des Remboursements</h5>
        </div>

        <div class="card-body">
            {{-- Filtres --}}
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label for="reportType" class="form-label">Type de Rapport</label>
                    <select wire:model.live="reportType" id="reportType" class="form-select">
                        <option value="daily">Journalier</option>
                        <option value="weekly">Hebdomadaire</option>
                        <option value="monthly">Mensuel</option>
                        <option value="yearly">Annuel</option>
                        <option value="custom">Personnalisé</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="currency" class="form-label">Devise</label>
                    <select wire:model.live="currency" id="currency" class="form-select">
                        <option value="all">Toutes</option>
                        <option value="USD">USD</option>
                        <option value="CDF">CDF</option>
                    </select>
                </div>

                @if ($reportType === 'custom')
                    <div class="col-md-3">
                        <label for="startDate" class="form-label">Date de début</label>
                        <input type="date" wire:model.live="startDate" id="startDate" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="endDate" class="form-label">Date de fin</label>
                        <input type="date" wire:model.live="endDate" id="endDate" class="form-control">
                    </div>
                @endif
            </div>

            {{-- Boutons Export --}}
            <div class="row mb-3">
                <div class="col-md-12 text-end">
                    <button wire:click="exportPdf" class="btn btn-sm btn-danger">
                        <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                        <i class="bx bx-file me-1"></i> Exporter en PDF
                    </button>
                    <button wire:click="exportExcel" class="btn btn-sm btn-success">
                        <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                        <i class="bx bx-table me-1"></i> Exporter en Excel
                    </button>
                </div>
            </div>

            {{-- Tableau --}}
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Code</th>
                            <th>Membre</th>
                            <th>Montant Remboursé</th>
                            <th>Pénalité</th>
                            <th>Devise</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $repayment)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($repayment->paid_date)->format('d/m/Y') }}</td>
                                <td>
                                    @if ($repayment->credit && $repayment->credit->user)
                                        {{ $repayment->credit->user->code }}
                                    @else
                                        <span class="badge bg-label-secondary">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($repayment->credit && $repayment->credit->user)
                                        {{ $repayment->credit->user->name. ' '.$repayment->credit->user->postnom. ' '.$repayment->credit->user->prenom }}
                                    @else
                                        <span class="badge bg-label-secondary">N/A</span>
                                    @endif
                                </td>
                                <td>{{ number_format($repayment->total_due, 2) }}</td>
                                <td>{{ number_format($repayment->penalty, 2) }}</td>
                                <td>
                                    @if ($repayment->credit)
                                        <span class="badge bg-label-info">{{ $repayment->credit->currency }}</span>
                                    @else
                                        <span class="badge bg-label-secondary">N/A</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Aucune donnée disponible.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>