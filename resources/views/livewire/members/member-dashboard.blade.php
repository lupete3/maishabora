<div class="container-xxl flex-grow-1 container-p-y">

    @can('afficher-tableaudebord-client')

        <!-- Header & Quick Actions -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center pb-4 mb-4 border-bottom">
            <div>
                <h3 class="fw-bold mb-0 text-primary">Tableau de bord</h3>
                <p class="text-muted mb-0">Bienvenue, {{ $member->name }} {{ $member->postnom }}</p>
            </div>
            <div class="mt-3 mt-md-0">
                <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#transferModal">
                    <i class="fas fa-paper-plane me-2"></i> Faire un virement
                </button>
            </div>
        </div>

        <div class="row g-4">

            <!-- Sidebar: Profile & Contact -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 sticky-top" style="top: 2rem; z-index: 1;">
                    <div class="card-body text-center pt-5 pb-4">
                        <div class="mb-3">
                            <div class="avatar avatar-xl rounded-circle bg-label-primary fs-2 mx-auto d-flex align-items-center justify-content-center shadow-sm"
                                style="width: 80px; height: 80px;">
                                {{ strtoupper(substr($member->name, 0, 1)) }}{{ strtoupper(substr($member->postnom, 0, 1)) }}
                            </div>
                        </div>
                        <h5 class="fw-bold mb-1">{{ $member->name }} {{ $member->postnom }}</h5>
                        <span class="badge bg-label-secondary mb-4">CODE: {{ $member->code }}</span>

                        <div class="text-start border-top pt-4">
                            <div class="d-flex align-items-start mb-3">
                                <i class="fas fa-map-marker-alt text-primary mt-1 me-3"></i>
                                <div>
                                    <small class="text-muted d-block">Adresse</small>
                                    <span class="fw-medium">{{ $member->adresse_physique }}</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-start mb-3">
                                <i class="fas fa-phone text-primary mt-1 me-3"></i>
                                <div>
                                    <small class="text-muted d-block">Téléphone</small>
                                    <span class="fw-medium">{{ $member->telephone }}</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-start">
                                <i class="fas fa-envelope text-primary mt-1 me-3"></i>
                                <div>
                                    <small class="text-muted d-block">Email</small>
                                    <span class="fw-medium">{{ $member->email ?? 'Non renseigné' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content: Financials & Stats -->
            <div class="col-lg-8">

                <!-- Section: Comptes Bancaires Courant -->
                <div class="mb-4" x-data="{ revealTimer: null }" x-init="$watch('@js($showBalances)', value => {
                        if (value) {
                            if (revealTimer) clearTimeout(revealTimer);
                            revealTimer = setTimeout(() => { $wire.hideBalances() }, 15000);
                        }
                    })">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-muted text-uppercase fs-7 ls-1">Mes Comptes</h5>
                        @if($showBalances)
                            <button class="btn btn-sm btn-outline-secondary py-1" wire:click="hideBalances">
                                <i class="fas fa-eye-slash me-1"></i> Masquer
                            </button>
                        @endif
                    </div>

                    @if(!$showBalances)
                        <div class="card shadow-sm border-0 mb-4 bg-label-secondary">
                            <div class="card-body py-4">
                                <div class="row align-items-center">
                                    <div class="col-md-7 mb-3 mb-md-0">
                                        <h6 class="fw-bold mb-1">Soldes masqués pour votre sécurité</h6>
                                        <p class="text-muted small mb-0">Entrez votre mot de passe pour afficher vos soldes
                                            disponibles.</p>
                                    </div>
                                    <div class="col-md-5">
                                        <form wire:submit.prevent="revealBalances" class="d-flex gap-2">
                                            <input type="password" wire:model="balancePassword"
                                                class="form-control form-control-sm @error('balancePassword') is-invalid @enderror"
                                                placeholder="Mot de passe">
                                            <button type="submit" class="btn btn-primary btn-sm px-3"
                                                wire:loading.attr="disabled">
                                                <span wire:loading wire:target="revealBalances"
                                                    class="spinner-border spinner-border-sm me-1"></span>
                                                Révéler
                                            </button>
                                        </form>
                                        @error('balancePassword') <div class="text-danger x-small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ── STYLES CARTES ── --}}
                    <style>
                        .account-card {
                            position: relative;
                            border-radius: 20px;
                            padding: 1.5rem;
                            overflow: hidden;
                            color: #fff;
                            min-height: 200px;
                            box-shadow: 0 20px 60px rgba(0,0,0,.25);
                            transition: transform .25s, box-shadow .25s;
                        }
                        .account-card:hover { transform: translateY(-4px); box-shadow: 0 28px 70px rgba(0,0,0,.32); }

                        /* Compte Courant – navy/indigo Visa Infinite */
                        .card-courant {
                            background: linear-gradient(135deg, #0a0f2c 0%, #1a237e 45%, #283593 75%, #1565c0 100%);
                        }
                        .card-courant::before {
                            content: '';
                            position: absolute;
                            top: -60px; right: -60px;
                            width: 220px; height: 220px;
                            border-radius: 50%;
                            background: rgba(255,255,255,.06);
                        }
                        .card-courant::after {
                            content: '';
                            position: absolute;
                            bottom: -80px; left: -40px;
                            width: 260px; height: 260px;
                            border-radius: 50%;
                            background: rgba(255,255,255,.04);
                        }

                        /* Compte Épargne – teal/emerald premium */
                        .card-epargne {
                            background: linear-gradient(135deg, #004d40 0%, #00695c 40%, #00897b 75%, #26a69a 100%);
                        }
                        .card-epargne::before {
                            content: '';
                            position: absolute;
                            top: -50px; right: -50px;
                            width: 200px; height: 200px;
                            border-radius: 50%;
                            background: rgba(255,255,255,.07);
                        }
                        .card-epargne::after {
                            content: '';
                            position: absolute;
                            bottom: -70px; left: -30px;
                            width: 250px; height: 250px;
                            border-radius: 50%;
                            background: rgba(255,255,255,.04);
                        }

                        .card-chip {
                            width: 38px; height: 28px;
                            background: linear-gradient(135deg, #ffd700 0%, #ffa000 100%);
                            border-radius: 5px;
                            position: relative;
                        }
                        .card-chip::before {
                            content: '';
                            position: absolute;
                            top: 50%; left: 50%;
                            transform: translate(-50%, -50%);
                            width: 22px; height: 16px;
                            border: 2px solid rgba(0,0,0,.25);
                            border-radius: 3px;
                        }
                        .card-network-logo {
                            display: flex; align-items: center; gap: -6px;
                        }
                        .card-network-logo span {
                            width: 26px; height: 26px;
                            border-radius: 50%;
                            opacity: .85;
                        }
                        .card-network-logo span:first-child { background: rgba(255,255,255,.6); margin-right: -10px; }
                        .card-network-logo span:last-child  { background: rgba(255,255,255,.35); }

                        .balance-row {
                            background: rgba(255,255,255,.1);
                            border-radius: 10px;
                            padding: .45rem .75rem;
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            margin-bottom: .4rem;
                            backdrop-filter: blur(4px);
                        }
                        .balance-currency-badge {
                            font-size: .7rem;
                            font-weight: 700;
                            letter-spacing: 1px;
                            background: rgba(255,255,255,.2);
                            padding: 2px 8px;
                            border-radius: 20px;
                        }
                        .balance-amount { font-size: 1rem; font-weight: 700; letter-spacing: .5px; }
                    </style>

                    <div class="row g-3">
                        {{-- ── Compte Courant ── --}}
                        <div class="col-md-6">
                            <div class="account-card card-courant">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <div class="fw-bold" style="font-size:.65rem;letter-spacing:2px;opacity:.7;text-transform:uppercase;">
                                            {{ config('app.name') }}
                                        </div>
                                        <div class="fw-bold" style="font-size:.8rem;letter-spacing:1.5px;margin-top:2px;">
                                            Compte Courant
                                        </div>
                                    </div>
                                    <div class="card-network-logo">
                                        <span></span><span></span>
                                    </div>
                                </div>

                                <div class="card-chip mb-3"></div>

                                <div class="mb-3" style="font-size:.72rem;opacity:.6;letter-spacing:2px;">
                                    {{ implode('  ', str_split(str_pad(preg_replace('/\D/', '', $member->code), 16, '0', STR_PAD_LEFT), 4)) }}
                                </div>

                                @foreach(['USD', 'CDF'] as $curr)
                                    @php
                                        $acc     = $member->accounts->where('currency', $curr)->where('type', 'current')->first();
                                        $balance = (float)($acc?->balance ?? 0);
                                    @endphp
                                    <div class="balance-row">
                                        <span class="balance-currency-badge">{{ $curr }}</span>
                                        <span class="balance-amount">
                                            @if($showBalances)
                                                {{ number_format($balance, 2, '.', ' ') }}
                                            @else
                                                <span style="letter-spacing:3px;opacity:.7">••••••</span>
                                            @endif
                                        </span>
                                    </div>
                                @endforeach

                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <small style="opacity:.55;font-size:.65rem;text-transform:uppercase;letter-spacing:1.5px;">
                                        {{ $member->name }} {{ $member->postnom }}
                                    </small>
                                    <i class="bx bxs-bank" style="font-size:1.2rem;opacity:.35;"></i>
                                </div>
                            </div>
                        </div>

                        {{-- ── Compte Épargne ── --}}
                        <div class="col-md-6">
                            <div class="account-card card-epargne">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <div class="fw-bold" style="font-size:.65rem;letter-spacing:2px;opacity:.7;text-transform:uppercase;">
                                            {{ config('app.name') }}
                                        </div>
                                        <div class="fw-bold" style="font-size:.8rem;letter-spacing:1.5px;margin-top:2px;">
                                            Compte Épargne
                                        </div>
                                    </div>
                                    <div class="card-network-logo">
                                        <span></span><span></span>
                                    </div>
                                </div>

                                <div class="card-chip mb-3"></div>

                                <div class="mb-3" style="font-size:.72rem;opacity:.6;letter-spacing:2px;">
                                    {{ implode('  ', str_split(str_pad(preg_replace('/\D/', '', $member->code) . '9', 16, '0', STR_PAD_LEFT), 4)) }}
                                </div>

                                @foreach(['USD', 'CDF'] as $curr)
                                    @php
                                        $acc     = $member->accounts->where('currency', $curr)->where('type', 'savings')->first();
                                        $balance = (float)($acc?->balance ?? 0);
                                    @endphp
                                    <div class="balance-row">
                                        <span class="balance-currency-badge">{{ $curr }}</span>
                                        <span class="balance-amount">
                                            @if($showBalances)
                                                {{ number_format($balance, 2, '.', ' ') }}
                                            @else
                                                <span style="letter-spacing:3px;opacity:.7">••••••</span>
                                            @endif
                                        </span>
                                    </div>
                                @endforeach

                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <small style="opacity:.55;font-size:.65rem;text-transform:uppercase;letter-spacing:1.5px;">
                                        {{ $member->name }} {{ $member->postnom }}
                                    </small>
                                    <i class="bx bxs-leaf" style="font-size:1.2rem;opacity:.35;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Section: Statistiques Carnets -->
                <div class="mb-4" wire:ignore>
                    <h5 class="fw-bold mb-3 text-muted text-uppercase fs-7 ls-1">Statistiques Carnets</h5>
                    <livewire:membership-card-stats wire:key="membership-stats" />
                </div>

                <div class="row g-4 mb-4">
                    <!-- Section: Crédits Actifs -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                                <h5 class="card-title fw-bold mb-0"><i
                                        class="fas fa-hand-holding-usd me-2 text-warning"></i> Crédits en cours</h5>
                            </div>
                            <div class="card-body">
                                @if($credits->where('is_paid', false)->isEmpty())
                                    <div class="text-center py-4 text-muted">
                                        <i class="fas fa-check-circle fa-2x mb-2 text-success opacity-50"></i>
                                        <p class="mb-0">Aucun crédit en cours.</p>
                                    </div>
                                @else
                                    <div class="list-group list-group-flush">
                                        @foreach ($credits->where('is_paid', false) as $credit)
                                            <div class="list-group-item px-0 py-3 border-bottom-dashed">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-1 fw-bold">{{ number_format($credit->amount, 2) }}
                                                            {{ $credit->currency }}
                                                        </h6>
                                                        <small class="text-muted"><i class="far fa-calendar-alt me-1"></i>
                                                            {{ \Carbon\Carbon::parse($credit->start_date)->format('d/m/Y') }}</small>
                                                    </div>
                                                    <span class="badge bg-label-warning">En cours</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Section: Échéances en retard -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                                <h5 class="card-title fw-bold mb-0 text-danger"><i
                                        class="fas fa-exclamation-triangle me-2"></i> Échéances en retard</h5>
                            </div>
                            <div class="card-body">
                                @if($overdueRepayments->isEmpty())
                                    <div class="text-center py-4 text-muted">
                                        <i class="fas fa-smile fa-2x mb-2 text-success opacity-50"></i>
                                        <p class="mb-0">Aucun retard de paiement.</p>
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless mb-0 align-middle">
                                            <thead class="text-muted border-bottom">
                                                <tr>
                                                    <th class="fw-normal">Date</th>
                                                    <th class="fw-normal text-end">Total dû</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($overdueRepayments as $r)
                                                    <tr>
                                                        <td class="text-danger fw-medium">
                                                            {{ \Carbon\Carbon::parse($r->due_date)->format('d/m/Y') }}
                                                        </td>
                                                        <td class="text-end fw-bold">{{ number_format($r->total_due, 2) }}</td>
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


                <!-- Section: Historique Transactions -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0"><i class="fas fa-history me-2 text-muted"></i> Transactions Récentes</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-nowrap ps-4">Date</th>
                                    <th>Description</th>
                                    <th class="text-center">Type</th>
                                    <th class="text-end">Montant</th>
                                    <th class="text-end pe-4">Solde</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transactions as $transaction)
                                    <tr>
                                        <td class="ps-4">
                                            <span
                                                class="fw-medium text-dark">{{ $transaction->created_at->format('d/m/Y') }}</span>
                                            <small
                                                class="d-block text-muted">{{ $transaction->created_at->format('H:i') }}</small>
                                        </td>
                                        <td>
                                            <span class="text-wrap d-block" style="max-width: 300px;">
                                                {{ $transaction->description }}
                                                @if(in_array($transaction->type, ['transfert_entrant', 'transfert_sortant']))
                                                    <br><small
                                                        class="text-muted fst-italic">{{ $transaction->counterparty_name }}</small>
                                                @endif
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $typeMap = [
                                                    'dépôt' => ['icon' => 'fas fa-arrow-down', 'color' => 'success', 'label' => 'Dépôt'],
                                                    'retrait' => ['icon' => 'fas fa-arrow-up', 'color' => 'danger', 'label' => 'Retrait'],
                                                    'transfert_entrant' => ['icon' => 'fas fa-long-arrow-alt-down', 'color' => 'info', 'label' => 'Reçu'],
                                                    'transfert_sortant' => ['icon' => 'fas fa-paper-plane', 'color' => 'warning', 'label' => 'Envoyé'],
                                                    'retrait_carte_adhesion' => ['icon' => 'fas fa-arrow-up', 'color' => 'danger', 'label' => 'Retrait'],
                                                    'mise_quotidienne' => ['icon' => 'fas fa-arrow-down', 'color' => 'success', 'label' => 'Mise quotidienne']
                                                ];
                                                $t = $typeMap[$transaction->type] ?? ['icon' => 'fas fa-circle', 'color' => 'secondary', 'label' => $transaction->type];
                                             @endphp
                                            <span class="badge bg-label-{{ $t['color'] }} rounded-pill text-uppercase fs-xs">
                                                <i class="{{ $t['icon'] }} me-1"></i> {{ $t['label'] }}
                                            </span>
                                        </td>
                                        <td
                                            class="text-end fw-bold {{ in_array($transaction->type, ['retrait', 'transfert_sortant', 'retrait_carte_adhesion']) ? 'text-danger' : 'text-success' }}">
                                            {{ in_array($transaction->type, ['retrait', 'transfert_sortant', 'retrait_carte_adhesion']) ? '-' : '+' }}{{ number_format($transaction->amount, 2) }}
                                            <small>{{ $transaction->currency }}</small>
                                        </td>
                                        <td class="text-end pe-4 text-muted fw-medium">
                                            @if($showBalances)
                                                {{ number_format($transaction->balance_after, 2) }}
                                            @else
                                                <span class="opacity-50">••••••</span>
                                            @endif
                                            <small>{{ $transaction->currency }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="mb-2"><i class="fas fa-wallet fa-3x text-muted opacity-25"></i></div>
                                            <p class="text-muted mb-0">Aucune transaction trouvée.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white border-top py-2">
                        <small class="text-muted fst-italic">Affichage des 10 dernières transactions.</small>
                    </div>
                </div>

            </div>
        </div>

        <!-- Transfer Modal -->
        <div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-hidden="true"
            wire:ignore.self>
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold text-white" id="transferModalLabel"><i
                                class="fas fa-exchange-alt me-2"></i> Virement Inter-Membres</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 bg-light">
                        <livewire:members.member-transfer />
                    </div>
                </div>
            </div>
        </div>

    @endcan

</div>