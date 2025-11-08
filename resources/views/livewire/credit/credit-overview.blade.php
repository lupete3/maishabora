<div class="row">
    <!-- Échéances en retard -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-label-danger fw-bold d-flex justify-content-between align-items-center">
                <span>Échéances en retard</span>
                <div>
                    @foreach ($totals['overdue'] as $currency => $total)
                        <span class="badge bg-danger ms-1">
                            {{ $currency }} {{ number_format($total, 2, '.', ' ') }}
                        </span>
                    @endforeach
                </div>
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
                                    <strong>{{ $r->credit->user->code }} {{ $r->credit->user->name }}</strong><br>
                                    <small class="text-muted">
                                        Montant : {{ number_format($r->total_due, 2, '.', ' ') }} {{ $r->credit->currency }}
                                    </small><br>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            Solde :
                                            @foreach (['USD', 'CDF'] as $curr)
                                                @php
                                                    $balance = $r->credit->user->accounts->firstWhere('currency', $curr)?->balance ?? 0;
                                                @endphp
                                                <small class="text-muted">
                                                    {{ number_format($balance, 2, '.', ' ') }} {{ $curr }} |
                                                </small>
                                            @endforeach
                                        </div>
                                        <small class="text-danger">
                                            Retard : {{ $daysLate }} {{ Str::plural('jour', $daysLate) }}
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

    <!-- Échéances à venir -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-label-warning fw-bold d-flex justify-content-between align-items-center">
                <span>Échéances à venir (7 jours)</span>
                <div>
                    @foreach ($totals['upcoming'] as $currency => $total)
                        <span class="badge bg-warning text-dark ms-1">
                            {{ $currency }} {{ number_format($total, 2, '.', ' ') }}
                        </span>
                    @endforeach
                </div>
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
                                    <strong>{{ $r->credit->user->code }} {{ $r->credit->user->name }}</strong><br>
                                    <small class="text-muted">
                                        Montant : {{ number_format($r->total_due, 2, '.', ' ') }} {{ $r->credit->currency }}
                                    </small><br>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            Solde :
                                            @foreach (['USD', 'CDF'] as $curr)
                                                @php
                                                    $balance = $r->credit->user->accounts->firstWhere('currency', $curr)?->balance ?? 0;
                                                @endphp
                                                <small class="text-muted">
                                                    {{ number_format($balance, 2, '.', ' ') }} {{ $curr }} |
                                                </small>
                                            @endforeach
                                        </div>
                                        <small class="text-primary">
                                            Échéance dans {{ $daysRemaining }} {{ Str::plural('jour', $daysRemaining) }}
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