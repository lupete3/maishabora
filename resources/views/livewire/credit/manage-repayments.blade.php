<!-- resources/views/livewire/manage-repayments.blade.php -->
<div class="container mt-4">
    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-header bg-primary text-white">Gérer les Remboursements</div>
        <div class="card-body pt-2">
            <form wire:submit.prevent="updatedCreditId">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="position-relative">
                            <label>Membre</label>
                            <input type="text"
                                    wire:model.live="search"
                                    class="form-control"
                                    placeholder="Rechercher un membre"
                                    autocomplete="off" />

                            @if (!empty($results))
                                <ul class="list-group w-100" style="z-index: 1000;">
                                @foreach ($results as $user)
                                    <li class="list-group-item list-group-item-action"
                                        wire:click="selectResult({{ $user['id'] }})">
                                    {{ "{$user['code']} {$user['name']} {$user['postnom']}" }}
                                    </li>
                                @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                    @if($credits)
                        <div class="col-md-6 mb-3">
                            <label>Crédit</label>
                            <select wire:model.lazy="credit_id" class="form-control">
                                <option value="">Sélectionner un crédit</option>
                                @foreach($credits as $credit)
                                    <option value="{{ $credit->id }}">
                                        {{ $credit->currency }} - {{ number_format($credit->amount, 2) }}
                                        ({{ $credit->installments }} échéances)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </form>

            @if($selectedCredit)
                <div class="mt-4">
                    <h5>Calendrier de remboursement</h5>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date d'échéance</th>
                                <th>Montant dû</th>
                                <th>Pénalité</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($selectedCredit->repayments as $r)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($r->due_date)->format('d/m/Y') }}</td>
                                    <td>{{ number_format($r->expected_amount, 2) }}</td>
                                    <td>{{ number_format($r->penalty, 2) }}</td>
                                    <td>{{ number_format($r->total_due, 2) }}</td>
                                    <td>
                                        @if($r->is_paid)
                                            <span class="badge bg-success">Payé</span>
                                        @else
                                            <span class="badge bg-warning">En attente</span>
                                        @endif
                                    </td>
                                    {{-- <td>
                                        @if(!$r->is_paid)
                                            <button wire:click="payRepayment({{ $r->id }})" class="btn btn-sm btn-success">
                                                <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                                                Payer
                                            </button>
                                        @else
                                            -
                                        @endif
                                    </td> --}}
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center">Aucune échéance trouvée.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
