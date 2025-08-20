<!-- resources/views/livewire/manage-repayments.blade.php -->
<div class="mt-0">
    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <h3>Gestion Remboursement Crédits</h3>

    <div class="card">
        <div class="card-header bg-primary text-white">Gérer les Remboursements</div>
        <div class="card-body pt-2">
            <form wire:submit.prevent="updatedCreditId">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="position-relative">
                            <label>Membre</label>

                            <div class="table-search-input">
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text" id="basic-addon-search31"><i class="icon-base bx bx-search"></i></span>
                                    <input type="search" wire:model.live="search" class="form-control" 
                                    placeholder="Rechercher Membre....." aria-label="Rechercher Membre....." aria-describedby="basic-addon-search31">
                                </div>
                            </div>

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
                    <a href="{{ route('schedule.generate', ['creditId' => $selectedCredit->id]) }}" target="_blank" class="btn btn-sm btn-secondary">
                        Imprimer le plan
                    </a>
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
                                    <td>
                                        @if(!$r->is_paid)
                                            <button wire:click="confirmRepayment({{ $r->id }})" class="btn btn-sm btn-success">
                                                <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                                                Payer
                                            </button>
                                        @else
                                            -
                                        @endif
                                    </td>
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




    <!-- Modal de confirmation -->
<div wire:ignore.self class="modal fade" id="confirm-repayment" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Confirmation remboursement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Voulez-vous appliquer les intérêts futurs sur ce remboursement ?</p>
      </div>
      <div class="modal-footer">
        <button wire:click="payRepayment(false)" class="btn btn-warning" data-bs-dismiss="modal">
            Non, solder sans intérêts
        </button>
        <button wire:click="payRepayment(true)" class="btn btn-success" data-bs-dismiss="modal">
            Oui, appliquer les intérêts
        </button>
      </div>
    </div>
  </div>
</div>


</div>
