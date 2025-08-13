<!-- resources/views/livewire/credit-follow-up-report.blade.php -->
<div class="container mt-4">
    <h4>Rapport Global de crédits</h4>
    <!-- Récapitulatif -->
    <div class="row g-3 mt-2 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card border-primary h-100">
                <div class="card-body">
                    <h6 class="card-title text-primary">Crédits Totals</h6>
                    @foreach ($totals['totalByCurrency'] as $curr => $total)
                    <p class="card-text">{{ $curr }} : {{ number_format($total, 2) }}</p>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-success h-100">
                <div class="card-body">
                    <h6 class="card-title text-success">Remboursés</h6>
                    @foreach ($totals['totalPaidByCurrency'] as $curr => $total)
                    <p class="card-text">{{ $curr }} : {{ number_format($total, 2) }}</p>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-danger h-100">
                <div class="card-body">
                    <h6 class="card-title text-danger">En cours</h6>
                    @foreach ($totals['totalUnpaidByCurrency'] as $curr => $total)
                    <p class="card-text">{{ $curr }} : {{ number_format($total, 2) }}</p>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-warning h-100">
                <div class="card-body">
                    <h6 class="card-title text-warning">Pénalités</h6>
                    @foreach ($totals['penaltyByCurrency'] as $curr => $total)
                    <p class="card-text">{{ $curr }} : {{ number_format($total, 2) }}</p>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="table-wrapper">
        <div class="card has-table ">
            <div class="card-header bg-light d-flex justify-between">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" wire:model.live="searchMember" class="form-control"
                            placeholder="Rechercher membre..." />
                    </div>

                    <div class="col-md-2">
                        <select wire:model.live="currency" class="form-select">
                            <option value="">Devise</option>
                            <option value="USD">USD</option>
                            <option value="CDF">CDF</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <select wire:model.live="status" class="form-select">
                            <option value="">Statut</option>
                            <option value="paid">Remboursé</option>
                            <option value="unpaid">En cours</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <input type="date" wire:model.live="startDate" class="form-control" />
                    </div>

                    <div class="col-md-2">
                        <input type="date" wire:model.live="endDate" class="form-control" />
                    </div>


                </div>
                <div class="col-md-2">
                    <button wire:click="exportToPdf" class="btn btn-primary " wire:loading.attr="disabled">
                        <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                        <i class="bx bx-download"></i> Télécharger PDF
                    </button>
                </div>
            </div>
            <div class="card-table table-responsive">
                <table class="table card-table table-vcenter table-striped table-hover">
                    <thead class="table-warning">
                        <tr>
                            <th>ID Crédit</th>
                            <th>Code Membre</th>
                            <th>Nom Membre</th>
                            <th>Date Crédit</th>
                            <th>Montant</th>
                            <th>Solde Restant</th>
                            <th>Pénalité</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($credits as $credit)
                        <tr>
                            <td>{{ $credit->id }}</td>
                            <td>{{ $credit->user->code }}</td>
                            <td>{{ $credit->user->name.' '.$credit->user->postnom.' '.$credit->user->prenom ?? '' }}
                            </td>
                            <td>{{ \Carbon\Carbon::parse($credit->start_date)->format('d/m/Y') }}</td>
                            <td>{{ number_format($credit->amount, 2) }} {{ $credit->currency }}</td>
                            <td>
                                @if ($credit->amount - $credit->repayments->where('is_paid', true)->sum('paid_amount') > 0)
                                {{ number_format(
                                $credit->amount - $credit->repayments->where('is_paid', true)->sum('paid_amount'), 2
                                ) }} {{ $credit->currency }}
                                @else
                                +{{ number_format(
                                $credit->repayments->where('is_paid', true)->sum('paid_amount') - $credit->amount, 2
                                ) }} {{ $credit->currency }}
                                    
                                @endif
                                
                            </td>
                            <td>
                                {{ number_format(
                                $credit->repayments->sum('penalty'), 2
                                ) }} {{ $credit->currency }}
                            </td>
                            <td>
                                @if ($credit->is_paid)
                                <span class="badge bg-success">Remboursé</span>
                                @else
                                <span class="badge bg-warning">En cours</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">Aucun crédit trouvé.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex justify-content-between align-items-center">
                <div>
                    Affichage de {{ $credits->firstItem() }} à {{ $credits->lastItem() }} sur {{ $credits->total() }}
                </div>
                <div>
                    {{ $credits->links() }}
                </div>
            </div>
        </div>
        <!-- Récapitulatif -->

    </div>
</div>
