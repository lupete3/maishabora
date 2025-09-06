<div>

    <div class="card mb-4">
        <div class="card-header fw-bold">
            Situation des crédits - {{ $user->name }}
        </div>
        <div class="card-body">
            @if($credits->isEmpty())
                <p>Aucun crédit trouvé pour ce client.</p>
            @else
                <div class="row">
                    @foreach($credits as $credit)
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
                                    <p><strong>Date début : </strong>{{ \Carbon\Carbon::parse($credit['start_date'])->format('d/m/Y') }}</p>
                                    <p><strong>Date échéance : </strong>{{ \Carbon\Carbon::parse($credit['due_date'])->format('d/m/Y') }}</p>
                                    <hr>
                                    <p><strong>Total payé : </strong>
                                        <span class="text-success">{{ number_format($credit['total_paid'], 2) }} {{ $credit['currency'] }}</span>
                                    </p>
                                    <p><strong>Montant restant : </strong>
                                        <span class="text-danger">{{ number_format($credit['remaining'], 2) }} {{ $credit['currency'] }}</span>
                                    </p>
                                    <p><strong>Pénalités cumulées : </strong>
                                        <span class="text-warning">{{ number_format($credit['penalties'], 2) }} {{ $credit['currency'] }}</span>
                                    </p>
                                    <p><strong>Nombre de retards : </strong>
                                        <span class="badge bg-danger">{{ $credit['late_count'] }}</span>
                                    </p>

                                    <div class="d-flex justify-content-between">
                                        @if($credit['is_paid'])
                                            <span class="badge bg-success">Payé</span>
                                        @else
                                            <span class="badge bg-warning">Encours</span>
                                        @endif

                                        <button class="btn btn-sm btn-outline-primary"
                                                wire:click="showRepayments({{ $credit['id'] }})">
                                            Voir remboursements
                                        </button>
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
    <div wire:ignore.self class="modal fade" id="repaymentsModal" tabindex="-1" aria-labelledby="repaymentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg  modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="repaymentsModalLabel">Détails des remboursements</h5>
                    <button type="button" class="btn-close" wire:click="closeRepaymentsModal()" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    @if(empty($selectedRepayments))
                        <p>Aucune échéance trouvée.</p>
                    @else
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date prévue</th>
                                    <th>Montant attendu</th>
                                    <th>Montant payé</th>
                                    <th>Pénalité</th>
                                    <th>Total dû</th>
                                    <th>Statut</th>
                                    <th>Retard (jours)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedRepayments as $rep)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($rep['due_date'])->format('d/m/Y') }}</td>
                                        <td>{{ number_format($rep['expected_amount'], 2) }}</td>
                                        <td class="text-success">{{ number_format($rep['paid_amount'], 2) }}</td>
                                        <td class="text-warning">{{ number_format($rep['penalty'], 2) }}</td>
                                        <td class="fw-bold">{{ number_format($rep['total_due'], 2) }}</td>
                                        <td>
                                            @if($rep['is_paid'])
                                                <span class="badge bg-success">Payé</span>
                                            @else
                                                <span class="badge bg-danger">Non payé</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($rep['days_late'])
                                                <span class="text-danger">{{ number_format($rep['days_late'], 0) }} jours</span>
                                            @else
                                                0
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
