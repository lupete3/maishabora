<div class="row">
    <!-- Vue d'ensemble -->
    <div class="col-12 mb-3">
        <h5 class="mb-0 fw-bold text-dark d-flex align-items-center">
            <i class="bx bx-list-check me-2 text-primary fs-3"></i>Vue d'ensemble du Suivi
        </h5>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-label-danger py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap g-2">
                    <h5 class="mb-0 fw-bold text-danger d-flex align-items-center">
                        <i class="bx bx-error-alt me-2"></i>Échéances en retard
                    </h5>
                    <div class="d-flex align-items-center">
                        @if ($overdueTotals->isNotEmpty())
                            <div class="d-flex flex-column gap-2 mb-3">
                                @foreach ($overdueTotals as $currency => $amounts)
                                    <div class="p-2 border rounded d-inline-block">
                                        <strong class="text-dark">{{ $currency }} </strong>
                                        <span class="badge bg-secondary ms-1">
                                            Capital: {{ number_format($amounts['capital'], 2, '.', ' ') }}
                                        </span>
                                        <span class="badge bg-info ms-1">
                                            Intérêt: {{ number_format($amounts['interest'], 2, '.', ' ') }}
                                        </span>
                                        <span class="badge bg-warning text-dark ms-1">
                                            Pénalité: {{ number_format($amounts['penalty'], 2, '.', ' ') }}
                                        </span>
                                        <span class="badge bg-danger ms-1">
                                            Total Dû: {{ number_format($amounts['total'], 2, '.', ' ') }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <button wire:click="exportOverduePDF" wire:loading.attr="disabled" class="btn btn-danger btn-sm shadow-sm d-flex align-items-center ml-2" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                            <span wire:loading.remove wire:target="exportOverduePDF">
                                <i class="bx bxs-file-pdf me-1"></i> PDF
                            </span>
                            <span wire:loading wire:target="exportOverduePDF">
                                <span class="spinner-border spinner-border-sm" role="status" style="width: 0.75rem; height: 0.75rem;"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @if ($overdueCredits->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <i class="bx bx-check-circle fs-1 d-block mb-2"></i>
                        Aucune échéance en retard.
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach ($overdueCredits as $r)
                            @php
                                $daysLate = \Carbon\Carbon::parse($r->due_date)->diffInDays(now());
                            @endphp
                            <div class="list-group-item list-group-item-action border-0 border-bottom p-3"
                                @if(auth()->user()->canAny(['afficher-compte-membre', 'depot-compte-membre', 'retrait-compte-membre']))
                                    onclick="window.location.href='{{ route('member.details', $r->credit->user->id) }}'"
                                    style="cursor: pointer;"
                                @endif>
                                <div class="row align-items-center">
                                    <div class="col-12 mb-2 d-flex justify-content-between">
                                        <span class="fw-bold text-primary">{{ $r->credit->user->code }} -
                                            {{ $r->credit->user->name }} {{ $r->credit->user->postnom }}</span>
                                        <span class="badge bg-label-danger">{{ $r->due_date->format('d/m/Y') }}</span>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="text-muted small mb-1">Montant Dû :</div>
                                        <div class="h6 mb-0 text-danger fw-bold">{{ number_format($r->total_due, 2, '.', ' ') }}
                                            {{ $r->credit->currency }}
                                        </div>
                                        <small class="text-danger">
                                            <i class="bx bx-time-five me-1"></i>Retard : {{ number_format($daysLate, 0) }}
                                            {{ $daysLate > 1 ? 'jours' : 'jour' }}
                                        </small>
                                    </div>
                                    <div class="col-md-7 border-start ps-md-4">
                                        <div class="text-muted small mb-1">Soldes Disponibles :</div>
                                        <div class="row g-2">
                                            @foreach (['USD', 'CDF'] as $curr)
                                                @php
                                                    $currentBal = (float) ($r->credit->user->accounts->where('currency', $curr)->where('type', 'current')->first()?->balance ?? 0);
                                                    $savingsBal = (float) ($r->credit->user->accounts->where('currency', $curr)->where('type', 'savings')->first()?->balance ?? 0);
                                                @endphp
                                                <div class="col-6">
                                                    <div class="small fw-bold border-bottom mb-1 pb-1">{{ $curr }}</div>
                                                    <div class="d-flex justify-content-between small">
                                                        <span class="text-muted">Courant:</span>
                                                        <span class="fw-medium">{{ number_format($currentBal, 2, '.', ' ') }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between small">
                                                        <span class="text-muted">Carnet:</span>
                                                        <span class="fw-medium">{{ number_format($savingsBal, 2, '.', ' ') }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="card-footer bg-transparent border-0 p-3">
                <div>
                    {{ $overdueCredits->links(data: ['pageName' => 'pageOverdue']) }}
                </div>
                <div class="d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <label>
                            <select wire:model.lazy="perPage" class="form-select form-select-sm">
                                <option value="10">10</option>
                                <option value="30">30</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="999999">Tous</option>
                            </select>
                        </label>
                    </div>
                    <div class="text-muted">
                        Affichage de {{ $overdueCredits->firstItem() }} à {{ $overdueCredits->lastItem() }} sur
                        <span class="badge bg-primary">{{ $overdueCredits->total() }}</span> opérations
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-header bg-label-warning py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap g-2">
                    <h5 class="mb-0 fw-bold text-warning d-flex align-items-center">
                        <i class="bx bx-calendar me-2"></i>Échéances à venir (7 jours)
                    </h5>
                    <div class="d-flex align-items-center">
                        @if ($upcomingTotals->isNotEmpty())
                            <div class="d-flex flex-wrap align-items-center me-3 gap-2">
                                @foreach ($upcomingTotals as $currency => $amounts)
                                    <div class="p-2 border rounded d-inline-block">
                                        <strong class="text-dark px-2 small">{{ $currency }} </strong>

                                        <span class="badge bg-secondary ms-1">
                                            Principal : {{ number_format($amounts['capital'], 2, '.', ' ') }}
                                        </span>

                                        <span class="badge bg-info text-dark ms-1">
                                            Intérêt : {{ number_format($amounts['interest'], 2, '.', ' ') }}
                                        </span>

                                        <span class="badge bg-warning text-dark ms-1 fw-bold">
                                            Total : {{ number_format($amounts['total'], 2, '.', ' ') }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <button wire:click="exportUpcomingPDF" wire:loading.attr="disabled" class="btn btn-warning text-dark btn-sm shadow-sm d-flex align-items-center" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                            <span wire:loading.remove wire:target="exportUpcomingPDF">
                                <i class="bx bxs-file-pdf me-1"></i> PDF
                            </span>
                            <span wire:loading wire:target="exportUpcomingPDF">
                                <span class="spinner-border spinner-border-sm" role="status" style="width: 0.75rem; height: 0.75rem;"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @if ($upcomingCredits->isEmpty())
                    <div class="p-4 text-center text-muted">
                        <i class="bx bx-info-circle fs-1 d-block mb-2"></i>
                        Aucune échéance prévue dans la semaine.
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach ($upcomingCredits as $r)
                            @php
                                $daysRemaining = now()->diffInDays(\Carbon\Carbon::parse($r->due_date), false);
                            @endphp
                            <div class="list-group-item list-group-item-action border-0 border-bottom p-3"
                                @if(auth()->user()->canAny(['afficher-compte-membre', 'depot-compte-membre', 'retrait-compte-membre']))
                                    onclick="window.location.href='{{ route('member.details', $r->credit->user->id) }}'"
                                    style="cursor: pointer;"
                                @endif>
                                <div class="row align-items-center">
                                    <div class="col-12 mb-2 d-flex justify-content-between">
                                        <span class="fw-bold text-primary">{{ $r->credit->user->code }} -
                                            {{ $r->credit->user->name }} {{ $r->credit->user->postnom }}</span>
                                        <span
                                            class="badge bg-label-warning text-dark">{{ $r->due_date->format('d/m/Y') }}</span>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="text-muted small mb-1">Montant à Payer :</div>
                                        <div class="h6 mb-0 text-dark fw-bold">{{ number_format($r->total_due, 2, '.', ' ') }}
                                            {{ $r->credit->currency }}
                                        </div>
                                        <small class="text-primary">
                                            <i class="bx bx-timer me-1"></i>Dans {{ number_format($daysRemaining, 0) }}
                                            {{ $daysRemaining > 1 ? 'jours' : 'jour' }}
                                        </small>
                                    </div>
                                    <div class="col-md-7 border-start ps-md-4">
                                        <div class="text-muted small mb-1">Soldes Disponibles :</div>
                                        <div class="row g-2">
                                            @foreach (['USD', 'CDF'] as $curr)
                                                @php
                                                    $currentBal = (float) ($r->credit->user->accounts->where('currency', $curr)->where('type', 'current')->first()?->balance ?? 0);
                                                    $savingsBal = (float) ($r->credit->user->accounts->where('currency', $curr)->where('type', 'savings')->first()?->balance ?? 0);
                                                @endphp
                                                <div class="col-6">
                                                    <div class="small fw-bold border-bottom mb-1 pb-1">{{ $curr }}</div>
                                                    <div class="d-flex justify-content-between small">
                                                        <span class="text-muted">Courant:</span>
                                                        <span class="fw-medium">{{ number_format($currentBal, 2, '.', ' ') }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between small">
                                                        <span class="text-muted">Carnet:</span>
                                                        <span class="fw-medium">{{ number_format($savingsBal, 2, '.', ' ') }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="card-footer bg-transparent border-0 p-3">
                {{ $upcomingCredits->links(data: ['pageName' => 'pageUpcoming']) }}
            </div>
        </div>
    </div>
</div>
