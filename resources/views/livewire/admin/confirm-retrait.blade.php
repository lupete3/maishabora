<!-- resources/views/livewire/admin/confirm-retrait.blade.php -->
@if($openConfirmRetraitNormal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-800 bg-opacity-50">
        <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-lg m-2">
            <div class="flex items-center justify-between pb-4 border-b">
                <h3 class="text-lg font-semibold">Confirmer le Retrait</h3>
                <button wire:click="closeRetraitConfirmationModal" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>
            <div class="mt-4">
                <p>Client <strong>{{ $member->name . ' ' . $member->postnom . ' ' . $member->prenom }}</strong></p>
                <p>Compte <strong>{{ $member->code ?? 'N/A' }}</strong></p>
                <p>Du type d'opération <strong>{{ ucfirst($operation_type) }}</strong>.</p>
                @if ($operation_type == 'carte')
                    <p>Retrait depuis la carte <strong>{{ $cardDetail->code ?? 'N/A' }}</strong>.</p>
                    @if($cardDetail)
                        <div class="bg-light p-3 rounded mb-3">
                            <p class="mb-1 d-flex justify-content-between">
                                <span>Total épargné :</span>
                                <strong>{{ number_format($cardDetail->contributions->where('is_paid', true)->sum('amount'), 2) }} {{ $cardDetail->currency }}</strong>
                            </p>
                            
                            @php
                                $toRetainNow = $cardDetail->first_mise_retained ? 0 : $cardDetail->subscription_amount;
                                $netAmount = $cardDetail->contributions->where('is_paid', true)->sum('amount') - $cardDetail->subscription_amount;
                            @endphp

                            <p class="mb-1 d-flex justify-content-between text-danger">
                                <span>Retenue (Commission) :</span>
                                <strong>- {{ number_format($cardDetail->subscription_amount, 2) }} {{ $cardDetail->currency }}</strong>
                            </p>
                            <hr class="my-2">
                            <p class="mb-0 d-flex justify-content-between text-success fw-bold">
                                <span>Net à percevoir :</span>
                                <span>{{ number_format($netAmount, 2) }} {{ $cardDetail->currency }}</span>
                            </p>
                        </div>

                        @if ($cardDetail->first_mise_retained == 1)
                            <p class="text-success small">
                                <i class="bx bx-check-double"></i> La première mise est déjà retenue.
                            </p>
                        @else
                            <p class="text-warning small italic">
                                <i class="bx bx-info-circle"></i> La première mise sera retenue maintenant.
                            </p>
                        @endif
                    @else
                        <p class="text-danger">Détails de la carte non disponibles.</p>
                    @endif
                @else
                    <p>Retrait normal de <strong>{{ number_format($amount, 2) }} {{ $currency }}</strong>.</p>
                    <p>Retenu : <strong>{{ number_format($a_retenir, 2) }} {{ $currency }}</strong></p>
                    <p class="fw-bold text-success">Net à percevoir : {{ number_format($amount, 2) }} {{ $currency }}</p>
                @endif
                <p>Voulez-vous vraiment continuer ?</p>
            </div>
            <div class="flex justify-end mt-6 space-x-4">
                <button wire:click="closeRetraitConfirmationModal"
                    class="px-4 py-2 font-semibold text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                    Annuler
                </button>
                <button wire:click.prevent="makeRetrait"
                    class="px-4 py-2 font-semibold btn btn-success rounded-lg hover:bg-green-600"
                    wire:loading.attr="disabled">
                    <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Confirmer
                </button>
            </div>
        </div>
    </div>
@endif
