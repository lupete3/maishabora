<div class="container-xxl flex-grow-1 container-p-y">

    @can('afficher-tableaudebord-client')

        <!-- Header & Quick Actions -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center pb-4 mb-4 border-bottom gap-3">
            <div>
                <h3 class="fw-bold mb-0 text-primary">Tableau de bord</h3>
                <p class="text-muted mb-0">Bienvenue, {{ $member->name }} {{ $member->postnom }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2  w-md-auto justify-content-start justify-content-md-end">
                {{-- Bouton Virement avec l'icône de partage/envoi Boxicons --}}
                <button class="btn btn-primary rounded-pill shadow-sm px-3 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#transferModal">
                    <i class="bx bx-send fs-5"></i>
                    <span>Virement</span>
                </button>

                @if($showBalances)
                    {{-- Bouton Masquer avec l'œil barré Boxicons --}}
                    <button class="btn btn-outline-success rounded-pill px-3 d-flex align-items-center gap-2" wire:click="hideBalances">
                        <i class="bx bx-hide fs-5"></i>
                        <span>Masquer soldes</span>
                    </button>
                @else
                    {{-- Bouton Afficher avec le cadenas ouvert ou l'œil affiché Boxicons --}}
                    <button class="btn btn-outline-primary rounded-pill px-3 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#revealModal">
                        <i class="bx bx-show fs-5"></i>
                        <span>Afficher soldes</span>
                    </button>
                @endif
            </div>
        </div>

        <div class="row g-4">

            <!-- Sidebar: Profile & Contact -->
            <div class="col-lg-4 order-2 order-lg-1">
                <style>
                    .profile-card {
                        border-radius: 20px;
                        overflow: hidden;
                        border: none;
                        box-shadow: 0 10px 40px rgba(3, 74, 14, 0.08);
                    }
                    .profile-cover {
                        height: 80px;
                        position: relative;
                    }
                    .profile-cover::after {
                        content: '';
                        position: absolute;
                        bottom: 0; left: 0; right: 0;
                        height: 40px;
                        background: linear-gradient(to top, rgba(255,255,255,1), rgba(50, 34, 1, 0));
                    }
                    .profile-avatar-wrapper {
                        margin-top: -60px;
                        position: relative;
                        z-index: 2;
                        text-align: center;
                    }
                    .profile-avatar {
                        width: 100px;
                        height: 100px;
                        border-radius: 50%;
                        border: 4px solid #fff;
                        background: linear-gradient(135deg, #d7a611ff 0%, #e6c02cff 100%);
                        color: #fff;
                        font-size: 2.5rem;
                        font-weight: bold;
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        box-shadow: 0 8px 20px rgba(235, 186, 37, 0.3);
                    }
                    .contact-item {
                        padding: 12px 16px;
                        border-radius: 12px;
                        transition: all 0.2s ease;
                        background: rgba(241, 245, 249, 0.5);
                        margin-bottom: 12px;
                        border: 1px solid transparent;
                    }
                    .contact-item:hover {
                        background: #fff;
                        border-color: rgba(59, 130, 246, 0.2);
                        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
                        transform: translateY(-2px);
                    }
                    .contact-icon-wrapper {
                        width: 40px;
                        height: 40px;
                        border-radius: 10px;
                        background: rgba(59, 130, 246, 0.1);
                        color: #cca406ff;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 1.1rem;
                        transition: all 0.2s;
                    }
                    .contact-item:hover .contact-icon-wrapper {
                        background: #cca406ff;
                        color: #fff;
                    }
                </style>
                <div class="card profile-card sticky-top" style="top: 2rem; z-index: 1;">
                    <div class="profile-cover"></div>
                    <div class="card-body text-center pt-0 pb-4 px-4">
                        <div class="profile-avatar-wrapper mb-3">
                            <div class="profile-avatar">
                                {{ strtoupper(substr($member->name, 0, 1)) }}{{ strtoupper(substr($member->postnom, 0, 1)) }}
                            </div>
                        </div>
                        <h4 class="fw-bold mb-1 text-dark">{{ $member->name }} {{ $member->postnom }}</h4>
                        <div class="mb-4">
                            <span class="badge bg-label-primary px-3 py-2 rounded-pill" style="letter-spacing: 1px;">
                                <i class="bx bx-id-card me-1"></i> {{ $member->code }}
                            </span>
                        </div>

                        <div class="mt-4">
                            <div class="d-flex flex-wrap justify-content-center gap-2">
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill">
                                    <i class="bx bx-phone me-1"></i> {{ $member->telephone }}
                                </span>
                                <span class="bg-light text-dark px-3 py-2 rounded-pill">
                                    <i class="bx bx-envelope me-1"></i> {{
                                        	Illuminate\Support\Str::limit($member->email ?? 'Non renseigné', 20)
                                    }}
                                </span>
                            </div>
                            <div class="mt-2 text-muted small text-center">
                                <i class="bx bx-map me-1"></i> {{
                                    	Illuminate\Support\Str::limit($member->adresse_physique ?? 'Non renseigné', 35)
                                }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content: Financials & Stats -->
            <div class="col-lg-8 order-1 order-lg-2">

                @php
                    $activeAccounts = $member->accounts->filter(fn($account) => $account->status === 'Actif');
                    $accountBalanceByCurrency = $activeAccounts->groupBy('currency')->map(fn($accounts) => $accounts->sum(fn($account) => (float) $account->balance));
                    $activeCredits = $credits->where('is_paid', false);
                    $creditTotal = $activeCredits->sum('amount');
                    $overdueTotal = $overdueRepayments->sum('total_due');
                @endphp

                <style>
                    .overview-card {
                        border: 0;
                        border-radius: 18px;
                        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
                        transition: transform .2s ease, box-shadow .2s ease;
                    }
                    .overview-card:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.1);
                    }
                    .overview-card-icon {
                        width: 44px;
                        height: 44px;
                        border-radius: 14px;
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 1.1rem;
                    }
                </style>

                <!-- Section: Comptes Bancaires Courant -->
                <div class="mb-4" x-data="{ revealTimer: null }" x-init="$watch('@js($showBalances)', value => {
                        if (value) {
                            if (revealTimer) clearTimeout(revealTimer);
                            revealTimer = setTimeout(() => { $wire.hideBalances() }, 15000);
                        }
                    })">

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
                            background: linear-gradient(135deg, #53450aff 0%, #94740dff 45%, #b18a0cff 75%, #d7bd15ff 100%);
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
                        @if($member->accounts->where('type', 'current')->where('status', 'Actif')->count() > 0)
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
                                        $acc = $member->accounts->where('currency', $curr)->where('type', 'current')->where('status', 'Actif')->first();
                                    @endphp
                                    @if($acc)
                                        @php
                                            $balance = (float)$acc->balance;
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
                                    @endif
                                @endforeach

                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <small style="opacity:.55;font-size:.65rem;text-transform:uppercase;letter-spacing:1.5px;">
                                        {{ $member->name }} {{ $member->postnom }}
                                    </small>
                                    <i class="bx bxs-bank" style="font-size:1.2rem;opacity:.35;"></i>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- ── Compte Épargne ── --}}
                        @if($member->accounts->where('type', 'savings')->where('status', 'Actif')->count() > 0)
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
                                    {{ implode('  ', str_split(str_pad(preg_replace('/\D/', '', $member->code), 16, '0', STR_PAD_LEFT), 4)) }}
                                </div>

                                @foreach(['USD', 'CDF'] as $curr)
                                    @php
                                        $acc     = $member->accounts->where('currency', $curr)->where('type', 'savings')->where('status', 'Actif')->first();
                                    @endphp
                                    @if($acc)
                                        @php
                                            $balance = (float)$acc->balance;
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
                                    @endif
                                @endforeach

                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <small style="opacity:.55;font-size:.65rem;text-transform:uppercase;letter-spacing:1.5px;">
                                        {{ $member->name }} {{ $member->postnom }}
                                    </small>
                                    <i class="bx bxs-leaf" style="font-size:1.2rem;opacity:.35;"></i>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Section: Statistiques Carnets -->
                @if($member->accounts->where('type', 'savings')->where('status', 'Actif')->count() > 0)
                <div class="mb-4" wire:ignore>
                    <h5 class="fw-bold mb-3 text-muted text-uppercase fs-7 ls-1">Statistiques Carnets</h5>
                    <livewire:membership-card-stats wire:key="membership-stats" />
                </div>
                @endif

                @if ($credits->where('is_paid', false)->isNotEmpty())
                <div class="row g-4 mb-4">
                    <!-- Section: Crédits Actifs -->
                    <div class="col-md-@if(!$overdueRepayments->isEmpty()){{6}}@else{{12}} @endif">
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
                                                <div>
                                                    Calendrier des remboursements <br>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-borderless mb-0 align-middle">
                                                            <thead class="text-muted border-bottom">
                                                                <tr>
                                                                    <th class="fw-normal">Date</th>
                                                                    <th class="fw-normal text-end">Total</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($credit->repayments as $r)
                                                                    <tr>
                                                                        <td class="text-{{ $r->is_paid ? 'success' : 'primary' }} fw-medium">
                                                                            {{ \Carbon\Carbon::parse($r->due_date)->format('d/m/Y') }} {!! $r->is_paid ? '<i class="bx bx-check"></i>' : '' !!}
                                                                        </td>
                                                                        <td class="text-end fw-bold">{{ number_format($r->total_due, 2) }} {{ $r->credit->currency }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Section: Échéances en retard -->
                    @if(!$overdueRepayments->isEmpty())
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
                                                        <td class="text-end fw-bold">{{ number_format($r->total_due, 2) }} {{ $r->credit->currency }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                </div>
                @endif

                @if ($credits->where('is_paid', true)->isNotEmpty())
                <div class="mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                            <h5 class="card-title fw-bold mb-0"><i
                                    class="fas fa-check-circle me-2 text-success"></i> Crédits remboursés</h5>
                        </div>
                        <div class="card-body">
                            @if($credits->where('is_paid', true)->isEmpty())
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-smile fa-2x mb-2 text-success opacity-50"></i>
                                    <p class="mb-0">Aucun crédit remboursé.</p>
                                </div>
                            @else
                                <div class="list-group list-group-flush">
                                    @foreach ($credits->where('is_paid', true) as $credit)
                                        <div class="list-group-item px-0 py-3 border-bottom-dashed">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1 fw-bold">{{ number_format($credit->amount, 2) }}
                                                        {{ $credit->currency }}
                                                    </h6>
                                                    <small class="text-muted"><i class="bx bx-calendar-alt me-1"></i>
                                                        Date début : {{ \Carbon\Carbon::parse($credit->start_date)->format('d/m/Y') }}</small>
                                                    <small class="text-muted"><i class="bx bx-calendar-alt me-1"></i>
                                                        Date fin : {{ \Carbon\Carbon::parse($credit->end_date)->format('d/m/Y') }}</small>
                                                </div>
                                                <span class="badge bg-label-success">Remboursé</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- Section: Historique Transactions -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-0"><i class="fas fa-history me-2 text-muted"></i> Activité récente</h5>
                            <small class="text-muted">Les 10 dernières opérations de votre compte</small>
                        </div>
                    </div>
                    <div class="card-body p-2 p-md-3">
                        <div class="d-flex flex-column gap-2">
                            @forelse ($transactions as $transaction)
                                @php
                                    $typeMap = [
                                        'dépôt' => ['icon' => 'bx bx-down-arrow-alt', 'color' => 'success', 'label' => 'Dépôt'],
                                        'retrait' => ['icon' => 'bx bx-up-arrow-alt', 'color' => 'danger', 'label' => 'Retrait'],
                                        'transfert_entrant' => ['icon' => 'bx bx-left-top-arrow-circle', 'color' => 'info', 'label' => 'Reçu'],
                                        'transfert_sortant' => ['icon' => 'bx bx-send', 'color' => 'warning', 'label' => 'Envoyé'],
                                        'retrait_carte_adhesion' => ['icon' => 'bx bx-up-arrow-alt', 'color' => 'danger', 'label' => 'Retrait'],
                                        'mise_quotidienne' => ['icon' => 'bx bx-down-arrow-alt', 'color' => 'success', 'label' => 'Mise quotidienne'],
                                        'octroi_de_credit' => ['icon' => 'bx bx-down-arrow-alt', 'color' => 'success', 'label' => 'Octroi de crédit'],
                                        'frais_mutuelle' => ['icon' => 'bx bx-up-arrow-alt', 'color' => 'danger', 'label' => 'Frais mutuelle'],
                                        'commission_credit' => ['icon' => 'bx bx-up-arrow-alt', 'color' => 'danger', 'label' => 'Commission crédit'],
                                        'paie_entrant' => ['icon' => 'bx bx-down-arrow-alt', 'color' => 'success', 'label' => 'Salaire reçu'],
                                        'remboursement_de_credit' => ['icon' => 'bx bx-up-arrow-alt', 'color' => 'danger', 'label' => 'Remboursement de crédit'],
                                    ];

                                    $t = $typeMap[$transaction->type] ?? ['icon' => 'bx bx-dots-horizontal-rounded', 'color' => 'secondary', 'label' => $transaction->type];
                                    $isDebit = in_array($transaction->type, ['retrait', 'transfert_sortant', 'retrait_carte_adhesion', 'frais_mutuelle', 'commission_credit', 'remboursement_de_credit',]);
                                @endphp

                                <div class="border rounded-2 p-3 bg-light-subtle">
                                    {{-- flex-column sur mobile, row sur tablettes/ordinateurs --}}
                                    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-start gap-2">

                                        {{-- Partie Gauche : Icône + Texte --}}
                                        <div class="d-flex align-items-start gap-2 w-100">
                                            <div class="rounded-circle p-2 bg-{{ $t['color'] }}-subtle text-{{ $t['color'] }} d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                                                <i class="{{ $t['icon'] }} fs-4"></i>
                                            </div>
                                            {{-- On empêche la description de casser le layout --}}
                                            <div class="text-break">
                                                <div class="fw-semibold text-dark">{{ $transaction->description }}</div>
                                                <div class="text-muted small">
                                                    {{ $transaction->created_at->format('d/m/Y H:i') }}
                                                    @if(in_array($transaction->type, ['transfert_entrant', 'transfert_sortant']))
                                                        • {{ $transaction->counterparty_name }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Partie Droite : Montant + Solde --}}
                                        {{-- text-sm-end pour s'aligner à droite uniquement sur grand écran. ms-auto aligne à droite sur mobile --}}
                                        <div class="text-start text-sm-end ms-auto mt-1 mt-sm-0 flex-shrink-0">
                                            <div class="fw-bold {{ $isDebit ? 'text-danger' : 'text-success' }} fs-5 fs-sm-6">
                                                {{ $isDebit ? '-' : '+' }}{{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}
                                            </div>
                                            <div class="text-muted small">
                                                @if($showBalances)
                                                    Solde {{ number_format($transaction->balance_after, 2) }}
                                                @else
                                                    Solde ••••••
                                                @endif
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5">
                                    <div class="mb-2"><i class="bx bx-wallet fs-1 text-muted opacity-25"></i></div>
                                    <p class="text-muted mb-0">Aucune transaction trouvée.</p>
                                </div>
                            @endforelse
                        </div>
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

        <!-- Reveal Balance Modal -->
        <div class="modal fade" id="revealModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-body p-4 text-center">
                        <div class="mb-3 d-flex justify-content-center">
                            <div class="avatar avatar-md border rounded-circle bg-label-primary d-flex align-items-center justify-content-center mx-auto shadow-sm" style="width: 60px; height: 60px;">
                                <i class="bx bx-lock-alt fs-2"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold mb-2">Sécurité</h5>
                        <p class="text-muted small mb-4">Veuillez renseigner votre mot de passe pour afficher les soldes de vos comptes.</p>

                        <form wire:submit.prevent="revealBalances">
                            <input type="password" wire:model="balancePassword" class="form-control text-center mb-2 @error('balancePassword') is-invalid @enderror" placeholder="••••••••" required>
                            @error('balancePassword')
                                <span class="text-danger small d-block mb-2">{{ $message }}</span>
                            @enderror

                            <div class="d-flex gap-2 justify-content-center mt-4">
                                <button type="button" class="btn btn-label-secondary w-50" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary w-50" wire:loading.attr="disabled">
                                    <span wire:loading wire:target="revealBalances" class="spinner-border spinner-border-sm me-1"></span>
                                    Valider
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tooling script to close modal upon success -->
        <div x-data="{}" x-init="
            $watch('$wire.showBalances', val => {
                if(val) {
                    let el = document.getElementById('revealModal');
                    if(el) {
                        let m = bootstrap.Modal.getInstance(el);
                        if(m) m.hide();
                    }
                }
            })
        "></div>
    @endcan

</div>
