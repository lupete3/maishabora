<!-- Modal -->
<div class="modal fade" id="modalCardDetails" tabindex="-1" aria-labelledby="modalCardDetailsLabel" aria-hidden="true"
    data-focus="false" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="modalCardDetailsLabel">{{ __('Dérails des contributions') }}</h5>
                <button type="button" class="btn-close" aria-label="Close" wire:click='closeCardViewModal'></button>
            </div>

            <div class="modal-body row">
                @if (!empty($cardDetail))
                    <div class="col-6">
                        <p><strong>{{ __('Numéro de la carte') }} :</strong> {{ $cardDetail->code }}</p>
                        <p><strong>{{ __("Date d'émission") }} :</strong>
                            {{ \Carbon\Carbon::parse($cardDetail->created_at)->format('d/m/Y') }}</p>
                        <p><strong>{{ __('Statut') }} :</strong>
                            @if ($cardDetail->is_active)
                                <span class="badge bg-success">{{ __('Active') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('Inactive') }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-6">
                        <p><strong>{{ __('Montant total') }} :</strong>
                            {{ number_format($cardDetail->contributions->where('is_paid', '=', 1)->sum('amount')) }}
                            {{ $cardDetail->currency }}</p>
                        <p><strong>{{ __('Nombre de contributions') }} :</strong>
                            {{ count($cardDetail->contributions->where('is_paid', '=', 1)) }}</p>
                        <p><strong>{{ __('Date de début') }} :</strong>
                            {{ \Carbon\Carbon::parse($cardDetail->start_date)->format('d/m/Y') }}</p>
                        <p><strong>{{ __('Date de fin') }} :</strong>
                            {{ \Carbon\Carbon::parse($cardDetail->end_date)->format('d/m/Y') }}</p>
                    </div>
                    <div class="col-12">
                        <hr>
                        <h6 class="fw-bold mb-3"><i class="lucide lucide-list-check me-2"></i>{{ __('Historique des contributions') }}</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-bordered">
                                <thead class="bg-light">
                                    <tr>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Montant') }}</th>
                                        <th class="text-center">{{ __('Statut') }}</th>
                                        @can('modifier-transaction-compte')
                                            <th class="text-center">{{ __('Action') }}</th>
                                        @endcan
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cardDetail->contributions->sortByDesc('updated_at') as $contribution)
                                        <tr>
                                            <td class="align-middle">
                                                {{ \Carbon\Carbon::parse($contribution->contribution_date)->format('d/m/Y') }}
                                            </td>
                                            <td class="align-middle fw-semibold">
                                                {{ number_format($contribution->amount, 2, ',', ' ') }} {{ $cardDetail->currency }}
                                            </td>
                                            <td class="text-center align-middle">
                                                @if ($contribution->is_paid)
                                                    <span class="badge bg-label-success">{{ __('Payé') }}</span>
                                                @else
                                                    <span class="badge bg-label-danger">{{ __('Impayé') }}</span>
                                                @endif
                                            </td>
                                            @can('modifier-transaction-compte')
                                                <td class="text-center align-middle">
                                                    <button type="button" 
                                                        wire:click="toggleContributionStatus({{ $contribution->id }})"
                                                        class="btn btn-xs {{ $contribution->is_paid ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                        title="{{ $contribution->is_paid ? 'Marquer comme impayé' : 'Marquer comme payé' }}">
                                                        {{ $contribution->is_paid ? 'Annuler' : 'Valider' }}
                                                    </button>
                                                </td>
                                            @endcan
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                    wire:click='closeCardViewModal'>{{ __('Fermer') }}</button>
            </div>
        </div>

    </div>
</div>
