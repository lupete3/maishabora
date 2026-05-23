<div>
    <div class="card mb-4">
        <div class="card-header fw-bold">
            Situation des crédits - {{ $user->name }}
        </div>
        <div class="card-body">
            @if ($credits->isEmpty())
                <p>Aucun crédit trouvé pour ce client.</p>
            @else
                <div class="row">
                    @foreach ($credits as $credit)
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        Crédit #{{ $credit['id'] }} ({{ $credit['currency'] }})
                                    </h5>
                                    <p><strong>Montant initial : </strong>
                                        {{ number_format($credit['amount'], 2) }} {{ $credit['currency'] }}
                                    </p>
                                    <p><strong>Taux intérêt : </strong>{{ $credit['interest_rate'] }}%</p>
                                    <p><strong>Date début :
                                        </strong>{{ \Carbon\Carbon::parse($credit['start_date'])->format('d/m/Y') }}</p>
                                    <p><strong>Date échéance :
                                        </strong>{{ \Carbon\Carbon::parse($credit['due_date'])->format('d/m/Y') }}</p>
                                    <hr>
                                    <p><strong>Total payé : </strong>
                                        <span class="text-success">{{ number_format($credit['total_paid'], 2) }}
                                            {{ $credit['currency'] }}</span>
                                    </p>
                                    <p><strong>Montant restant : </strong>
                                        <span class="text-danger">{{ number_format($credit['remaining'], 2) }}
                                            {{ $credit['currency'] }}</span>
                                    </p>
                                    <p><strong>Pénalités cumulées : </strong>
                                        <span class="text-warning">{{ number_format($credit['penalties'], 2) }}
                                            {{ $credit['currency'] }}</span>
                                    </p>
                                    <p><strong>Nombre de retards : </strong>
                                        <span class="badge bg-danger">{{ $credit['late_count'] }}</span>
                                    </p>

                                    <div class="d-flex justify-content-between align-items-center">
                                        @if ($credit['is_paid'])
                                            <span class="badge bg-success">Payé</span>
                                        @else
                                            <span class="badge bg-warning">Encours</span>
                                        @endif

                                        <div class="d-flex gap-1">
                                            <a href="{{ route('credit.situation.pdf', ['creditId' => $credit['id']]) }}" target="_blank"
                                                class="btn btn-sm btn-outline-danger" title="Exporter Situation PDF">
                                                <i class="icon-base bx bxs-file-pdf"></i> PDF
                                            </a>
                                            <button class="btn btn-sm btn-outline-primary"
                                                wire:click="showRepayments({{ $credit['id'] }})">
                                                Détails
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="repaymentsModal" tabindex="-1" aria-labelledby="repaymentsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg  modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="repaymentsModalLabel">Détails des remboursements</h5>
                    <div class="d-flex align-items-center gap-2 ms-auto me-2">
                        @if ($selectedCreditId)
                            <a href="{{ route('credit.situation.pdf', ['creditId' => $selectedCreditId]) }}" target="_blank"
                                class="btn btn-sm btn-danger">
                                <i class="icon-base bx bxs-file-pdf me-1"></i>Situation PDF
                            </a>
                        @endif
                    </div>
                    <button type="button" class="btn-close" wire:click="closeRepaymentsModal()"
                        aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    @if (empty($selectedRepayments))
                        <p>Aucune échéance trouvée.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date d'échéance</th>
                                        <th>Attendu (Ventilation)</th>
                                        <th>Pénalité cumul.</th>
                                        <th>Total dû</th>
                                        <th>Déjà payé (Ventilation)</th>
                                        <th class="text-danger">Reste à payer</th>
                                        <th>Statut</th>
                                        <th>Retard (jours)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($selectedRepayments as $rep)
                                        @php
                                            $principal = floatval($rep['principal_amount']);
                                            $interest  = floatval($rep['interest_amount']);
                                            $penalty   = floatval($rep['penalty']);
                                            $totalDue  = floatval($rep['total_due']);

                                            $paidTotal = floatval($rep['paid_amount']);
                                            $paidPri   = floatval($rep['paid_principal']);
                                            $paidInt   = floatval($rep['paid_interest']);
                                            $paidPen   = floatval($rep['paid_penalty']);

                                            $remaining = max(0.0, $totalDue - $paidTotal);

                                            // Row styling based on status
                                            $rowClass = '';
                                            if ($rep['is_paid']) {
                                                $rowClass = 'table-success-light';
                                            } elseif (\Carbon\Carbon::parse($rep['due_date'])->isPast()) {
                                                $rowClass = 'table-danger-light';
                                            }
                                        @endphp
                                        <tr class="{{ $rowClass }}">
                                            <td>{{ \Carbon\Carbon::parse($rep['due_date'])->format('d/m/Y') }}</td>
                                            <td>
                                                <small class="d-block">Capital : <strong>{{ number_format($principal, 2) }}</strong></small>
                                                <small class="d-block text-muted">Intérêt : {{ number_format($interest, 2) }}</small>
                                            </td>
                                            <td class="{{ $penalty > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                                {{ number_format($penalty, 2) }}
                                            </td>
                                            <td class="fw-semibold">{{ number_format($totalDue, 2) }}</td>
                                            <td>
                                                @if ($paidTotal > 0)
                                                    <small class="d-block text-success">Cap : {{ number_format($paidPri, 2) }}</small>
                                                    <small class="d-block text-info">Int : {{ number_format($paidInt, 2) }}</small>
                                                    @if ($paidPen > 0)
                                                        <small class="d-block text-danger">Pén : {{ number_format($paidPen, 2) }}</small>
                                                    @endif
                                                    <small class="d-block fw-bold text-dark border-top mt-1">Total : {{ number_format($paidTotal, 2) }}</small>
                                                @else
                                                    <span class="text-muted small">Aucun paiement</span>
                                                @endif
                                            </td>
                                            <td class="{{ $remaining > 0 ? 'fw-bold text-danger' : 'text-muted' }}">
                                                {{ number_format($remaining, 2) }}
                                            </td>
                                            <td>
                                                @if ($rep['is_paid'])
                                                    <span class="badge bg-success">Payé</span>
                                                @elseif ($paidTotal > 0 && $remaining > 0)
                                                    <span class="badge bg-warning text-dark">Partiel</span>
                                                @else
                                                    <span class="badge bg-danger">Non payé</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($rep['days_late'] > 0)
                                                    <span class="text-danger fw-semibold">{{ $rep['days_late'] }} jours</span>
                                                @else
                                                    <span class="text-muted">0</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
