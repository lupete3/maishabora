<div>
    <div class="card mb-4">
        <div class="card-header fw-bold">
            Situation des crédits - {{ $user->name }}
            <ul class="nav nav-tabs card-header-tabs" id="creditTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="encourscredit-tab" data-bs-toggle="tab"
                        data-bs-target="#encourscredit" type="button" role="tab">
                        Encours
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="clotures-tab" data-bs-toggle="tab"
                        data-bs-target="#cloturescredit" type="button" role="tab">
                        Remboursés
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            @if ($credits->isEmpty())
                <p>Aucun crédit trouvé pour ce client.</p>
            @else
            <div class="tab-content" id="creditTabsContent">
                <div class="tab-pane fade show active" id="encourscredit" role="tabpanel" aria-labelledby="encourscredit-tab">
                    <div class="row">
                        @foreach ($credits->where('is_paid', false) as $credit)
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
                                        <div class="d-flex justify-content-between">
                                            <span class="badge bg-warning">Encours</span>
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
                </div>
                <div class="tab-pane fade" id="cloturescredit" role="tabpanel" aria-labelledby="clotures-tab">
                    <div class="row">
                        @foreach ($credits->where('is_paid', true) as $credit)
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

                                        <div class="d-flex justify-content-between">
                                            @if ($credit['is_paid'])
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
                    <button type="button" class="btn-close" wire:click="closeRepaymentsModal()"
                        aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    @if (empty($selectedRepayments))
                        <p>Aucune échéance trouvée.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date d'échéance</th>
                                        <th>Principal</th>
                                        <th>Intérêt</th>
                                        <th>Montant dû</th>
                                        <th>Pénalité</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Retard (jours)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $remainingCapital = floatval($selectedCredit->amount);
                                    @endphp
                                    @forelse($selectedCredit->repayments->sortBy('due_date') as $r)
                                        @php
                                            if ($selectedCredit->credit_type === 'degressif') {
                                                $interest = round(
                                                    $remainingCapital *
                                                        (floatval($selectedCredit->interest_rate) / 100),
                                                    2,
                                                );
                                            } else {
                                                $interest = round(
                                                    floatval($selectedCredit->amount) *
                                                        (floatval($selectedCredit->interest_rate) / 100),
                                                    2,
                                                );
                                            }
                                            $capital = round(floatval($r->expected_amount) - $interest, 2);
                                            $remainingCapital = round($remainingCapital - $capital, 2);
                                        @endphp
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($r->due_date)->format('d/m/Y') }}</td>
                                            <td>{{ number_format($capital, 2) }}</td>
                                            <td>{{ number_format($interest, 2) }}</td>
                                            <td>{{ number_format($r->expected_amount, 2) }}</td>
                                            <td>{{ number_format($r->penalty, 2) }}</td>
                                            <td>{{ number_format($r->total_due, 2) }}</td>
                                            <td>
                                                @if ($r->is_paid)
                                                    <span class="badge bg-success">Payé</span>
                                                @else
                                                    <span class="badge bg-warning">En attente</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $daysLate = \Carbon\Carbon::parse($r->due_date)->diffInDays(
                                                        $r->paid_date,
                                                    );
                                                @endphp
                                                @if ($daysLate > 0)
                                                    <span class="text-danger">{{ number_format($daysLate, 0) }}
                                                        jours</span>
                                                @else
                                                    0
                                                @endif
                                            </td>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center">Aucune échéance trouvée.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
