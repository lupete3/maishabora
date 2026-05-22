<!-- Modal Remboursement -->
<div class="modal fade @if($openModalConfirm) show @endif" id="modalModifyBalance" tabindex="-1"
    style="@if($openModalConfirm) display: block; background: rgba(0,0,0,0.5); @else display: none; @endif">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bx bxs-credit-card me-2"></i>Remboursement d'échéance
                </h5>
                <button type="button" class="btn-close btn-close-white"
                    wire:click="$set('openModalConfirm', false)"></button>
            </div>

            <div class="modal-body">

                {{-- Tableau de ventilation des soldes restants --}}
                @if (!empty($repaymentDetails))
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Composante</th>
                                    <th class="text-end">Solde restant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><i class="bx bx-money text-primary me-1"></i>Capital</td>
                                    <td class="text-end fw-semibold">
                                        {{ number_format($repaymentDetails['remaining_principal'], 2) }}
                                        {{ $repaymentDetails['currency'] }}
                                    </td>
                                </tr>
                                <tr>
                                    <td><i class="bx bx-trending-up text-info me-1"></i>Intérêt</td>
                                    <td class="text-end fw-semibold">
                                        {{ number_format($repaymentDetails['remaining_interest'], 2) }}
                                        {{ $repaymentDetails['currency'] }}
                                    </td>
                                </tr>
                                <tr class="{{ $repaymentDetails['remaining_penalty'] > 0 ? 'table-danger' : '' }}">
                                    <td><i class="bx bx-error-circle text-danger me-1"></i>Pénalité</td>
                                    <td class="text-end fw-semibold text-danger">
                                        {{ number_format($repaymentDetails['remaining_penalty'], 2) }}
                                        {{ $repaymentDetails['currency'] }}
                                    </td>
                                </tr>
                                <tr class="table-secondary">
                                    <td><strong>Total restant dû</strong></td>
                                    <td class="text-end fw-bold">
                                        {{ number_format($repaymentDetails['total_remaining'], 2) }}
                                        {{ $repaymentDetails['currency'] }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Ordre de priorité d'allocation --}}
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bx bx-info-circle me-1"></i>
                        Ordre d'allocation : <strong>Pénalité → Intérêt → Capital</strong>
                    </div>
                @endif

                {{-- Saisie du montant de remboursement --}}
                <div class="mb-0">
                    <label class="form-label fw-semibold">
                        Montant à rembourser
                        @if(!empty($repaymentDetails))
                            <span class="text-muted fw-normal">(max : {{ number_format($repaymentDetails['total_remaining'], 2) }} {{ $repaymentDetails['currency'] }})</span>
                        @endif
                    </label>
                    <input type="number"
                           class="form-control @error('paymentAmount') is-invalid @enderror"
                           wire:model.defer="paymentAmount"
                           step="0.01"
                           min="0.01"
                           placeholder="Saisir le montant">
                    @error('paymentAmount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            <div class="modal-footer gap-2">
                <button type="button" class="btn btn-secondary btn-sm"
                    wire:click="$set('openModalConfirm', false)">
                    <i class="bx bx-x me-1"></i>Annuler
                </button>
                <button wire:click="payRepayment(false)" class="btn btn-warning btn-sm"
                    wire:loading.attr="disabled" wire:target="payRepayment">
                    <span wire:loading.remove wire:target="payRepayment">
                        <i class="bx bx-coin-stack me-1"></i>Solder capital seul
                    </span>
                    <span wire:loading wire:target="payRepayment">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </span>
                </button>
                <button wire:click="payRepayment(true)" class="btn btn-success btn-sm"
                    wire:loading.attr="disabled" wire:target="payRepayment">
                    <span wire:loading.remove wire:target="payRepayment">
                        <i class="bx bx-check-circle me-1"></i>Valider avec intérêts
                    </span>
                    <span wire:loading wire:target="payRepayment">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </span>
                </button>
            </div>

        </div>
    </div>
</div>
