<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-label-danger fw-bold">
                Échéances en retard 
                {{-- NOUVEAU: Affichage des totaux en retard --}}
                @if ($overdueTotals->isNotEmpty())
                    <span class="float-end small fw-normal">
                        Total dû :
                        @foreach ($overdueTotals as $currency => $total)
                            <span class="badge bg-danger">
                                {{ number_format($total, 2, '.', ' ') }} {{ $currency }}
                            </span>
                        @endforeach
                    </span>
                @endif
            </div>
            <div class="card-body p-0">
                @if ($overdueCredits->isEmpty())
                    <div class="p-3">Aucune échéance en retard.</div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach ($overdueCredits as $r)
                            @php
                                $daysLate = \Carbon\Carbon::parse($r->due_date)->diffInDays(now());
                            @endphp
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $r->credit->user->code . ' ' . $r->credit->user->name. ' ' . $r->credit->user->postnom. ' ' . $r->credit->user->prenom ?? '' }}</strong><br>
                                    <small class="text-muted">
                                        Montant : {{ $r->total_due . ' ' . $r->credit->currency }}
                                    </small><br>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="pt-0 ">
                                            Solde :
                                            @foreach (['USD', 'CDF'] as $curr)
                                                @php
                                                    $balance = (float) ($r->credit->user->accounts->firstWhere(
                                                        'currency',
                                                        $curr,
                                                    )?->balance ?? 0);
                                                @endphp
                                                <small class="text-muted">
                                                    {{ number_format($balance, 2, '.', ' ') }}{{ $curr }} |
                                                </small>
                                            @endforeach
                                        </div>
                                        <small class="text-danger">
                                            Retard : {{ number_format($daysLate, 0) }}
                                            {{ Str::plural('jour', $daysLate) }}
                                        </small>
                                    </div>
                                </div>
                                <span class="badge bg-danger">
                                    {{ \Carbon\Carbon::parse($r->due_date)->format('d/m/Y') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <div class="mt-3 p-2">
                    {{ $overdueCredits->links() }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-label-warning fw-bold">
                Échéances à venir (7 jours)
                {{-- NOUVEAU: Affichage des totaux à venir --}}
                @if ($upcomingTotals->isNotEmpty())
                    <span class="float-end small fw-normal">
                        Total dû :
                        @foreach ($upcomingTotals as $currency => $total)
                            <span class="badge bg-warning text-dark">
                                {{ number_format($total, 2, '.', ' ') }} {{ $currency }}
                            </span>
                        @endforeach
                    </span>
                @endif
            </div>
            <div class="card-body p-0">
                @if ($upcomingCredits->isEmpty())
                    <div class="p-3">Aucune échéance prévue dans la semaine.</div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach ($upcomingCredits as $r)
                            @php
                                $daysRemaining = now()->diffInDays(\Carbon\Carbon::parse($r->due_date), false);
                            @endphp
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $r->credit->user->code . ' ' . $r->credit->user->name. ' ' . $r->credit->user->postnom. ' ' . $r->credit->user->prenom ?? '' }}</strong><br>
                                    <small class="text-muted">
                                        Montant : {{ $r->total_due . ' ' . $r->credit->currency }}
                                    </small><br>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="pt-0 ">
                                            Solde :
                                            @foreach (['USD', 'CDF'] as $curr)
                                                @php
                                                    $balance = (float) ($r->credit->user->accounts->firstWhere(
                                                        'currency',
                                                        $curr,
                                                    )?->balance ?? 0);
                                                @endphp
                                                <small class="text-muted">
                                                    {{ number_format($balance, 2, '.', ' ') }}{{ $curr }} |
                                                </small>
                                            @endforeach
                                        </div>
                                        <small class="text-primary">
                                            Échéance dans {{ number_format($daysRemaining, 0) }}
                                            {{ Str::plural('jour', $daysRemaining) }}
                                        </small>
                                    </div>
                                </div>
                                <span class="badge bg-warning text-dark">
                                    {{ \Carbon\Carbon::parse($r->due_date)->format('d/m/Y') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <div class="mt-3 p-2">
                    {{ $upcomingCredits->links() }}
                </div>
            </div>
        </div>
    </div>
</div>