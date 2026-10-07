@extends('layouts.backend')

@section('title', 'Historique du dépôt à terme')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <a href="{{ route('member.details', $user->id) }}" class="small"><i class="bx bx-arrow-back"></i> Détail du membre</a>
                <h4 class="mt-2 mb-0">Dépôt à terme #{{ $termDeposit->id }}</h4>
                <div class="text-muted">{{ trim($user->name . ' ' . $user->postnom . ' ' . $user->prenom) }}</div>
            </div>
            <span class="badge bg-label-{{ $termDeposit->status === 'active' ? 'success' : ($termDeposit->status === 'closed' ? 'primary' : 'secondary') }} fs-6">{{ ucfirst(str_replace('_', ' ', $termDeposit->status)) }}</span>
        </div>

        <section class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Résumé du contrat</h5>
            </div>
            <div class="card-body">
            <div class="row g-3">
                <div class="col-sm-6 col-lg-3"><div class="border-bottom p-2 h-100"><div class="small text-muted">Produit</div><div class="fw-semibold">{{ $termDeposit->product->name }}</div></div></div>
                <div class="col-sm-6 col-lg-3"><div class="border-bottom p-2 h-100"><div class="small text-muted">Capital initial</div><div class="fw-semibold">{{ number_format((float) $termDeposit->principal_amount, 2, ',', ' ') }} {{ $termDeposit->currency }}</div></div></div>
                <div class="col-sm-6 col-lg-3"><div class="border-bottom p-2 h-100"><div class="small text-muted">Taux annuel simple</div><div class="fw-semibold">{{ number_format((float) $termDeposit->annual_interest_rate, 4, ',', ' ') }} %</div></div></div>
                <div class="col-sm-6 col-lg-3"><div class="border-bottom p-2 h-100"><div class="small text-muted">Période</div><div class="fw-semibold">{{ $termDeposit->opened_at->format('d/m/Y') }} – {{ $termDeposit->matures_at->format('d/m/Y') }}</div></div></div>
                <div class="col-sm-6 col-lg-3"><div class="border-bottom p-2 h-100"><div class="small text-muted">Intérêts réglés</div><div class="fw-semibold">{{ number_format((float) $termDeposit->interest_amount, 2, ',', ' ') }} {{ $termDeposit->currency }}</div></div></div>
                <div class="col-sm-6 col-lg-3"><div class="border-bottom p-2 h-100"><div class="small text-muted">Pénalité</div><div class="fw-semibold">{{ number_format((float) $termDeposit->penalty_amount, 2, ',', ' ') }} {{ $termDeposit->currency }}</div></div></div>
                <div class="col-sm-6 col-lg-3"><div class="border-bottom p-2 h-100"><div class="small text-muted">Montant versé</div><div class="fw-semibold">{{ number_format((float) $termDeposit->payout_amount, 2, ',', ' ') }} {{ $termDeposit->currency }}</div></div></div>
                <div class="col-sm-6 col-lg-3"><div class="border-bottom p-2 h-100"><div class="small text-muted">Règle retrait anticipé</div><div class="fw-semibold">{{ number_format((float) $termDeposit->early_withdrawal_penalty_rate, 4, ',', ' ') }} % du principal · {{ $termDeposit->early_withdrawal_interest_policy === 'pro_rata' ? 'intérêts proratisés' : 'intérêts perdus' }}</div></div></div>
            </div>
            </div>
        </section>

        <section class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">Historique des opérations</h5>
                <span class="small text-muted">{{ $transactions->total() }} opération(s)</span>
            </div>
            <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Opération</th><th>Description</th><th>Référence</th><th class="text-end">Montant</th><th class="text-end">Solde du placement</th><th>Effectué par</th></tr></thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->occurred_at->format('d/m/Y H:i') }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $transaction->type)) }}</td>
                                <td>{{ $transaction->description }}</td>
                                <td class="small">{{ $transaction->reference }}</td>
                                <td class="text-end">{{ number_format((float) $transaction->amount, 2, ',', ' ') }} {{ $transaction->currency }}</td>
                                <td class="text-end">{{ number_format((float) $transaction->term_balance_after, 2, ',', ' ') }} {{ $transaction->currency }}</td>
                                <td>{{ $transaction->performedBy?->name ?? 'Système' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Aucune opération enregistrée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </div>
            <div class="mt-3">{{ $transactions->links() }}</div>
            </div>
        </section>
    </div>
@endsection