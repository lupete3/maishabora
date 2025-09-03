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
                            {{ number_format($cardDetail->contributions->where('is_paid', '=', 1)->sum('amount')) }} {{ $cardDetail->currency }}</p>
                        <p><strong>{{ __('Nombre de contributions') }} :</strong>
                            {{ count($cardDetail->contributions->where('is_paid', '=', 1)) }}</p>
                        <p><strong>{{ __('Date de début') }} :</strong>
                            {{ \Carbon\Carbon::parse($cardDetail->start_date)->format('d/m/Y') }}</p>
                        <p><strong>{{ __('Date de fin') }} :</strong>
                            {{ \Carbon\Carbon::parse($cardDetail->end_date)->format('d/m/Y') }}</p>
                    </div>
                    <div class="col-12">
                        <h5>{{ __('Contributions associées') }} :</h5>
                        @if (count($cardDetail->contributions->where('is_paid', '=', 1)) > 0)
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Montant') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cardDetail->contributions as $contribution)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($contribution->contribution_date)->format('d/m/Y') }}
                                            </td>
                                            <td>{{ number_format($contribution->amount, 2, ',', ' ') }} </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p>{{ __('Aucune contribution trouvée pour cette carte.') }}</p>
                        @endif
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


<!-- Table des adhésions (inchangée) -->
