<!-- resources/views/livewire/admin/confirm-retrait.blade.php -->
@if($openConfirmRetraitNormal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-800 bg-opacity-50">
        <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-lg m-2">
            <div class="flex items-center justify-between pb-4 border-b">
                <h3 class="text-lg font-semibold">Confirmer le Retrait</h3>
                <button wire:click="closeWithdrawalConfirmationModal" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="mt-4">
                <p>Client <strong>{{ $member->name.' '.$member->postnom.' '.$member->prenom }}</strong></p> 
                <p>Compte <strong>{{ $member->code }}</strong></p>  
                <p>Du type d'opération <strong>{{ ucfirst($operation_type) }}</strong>.</p>
                @if ($operation_type == 'carte')
                    <p>Retrait depuis la carte <strong>{{ $cardDetail->code }}</strong>.</p>
                    <p>Solde disponible : <strong>{{ number_format($cardDetail->contributions->where('is_paid', true)->sum('amount'), 2) }} {{ $cardDetail->currency }}</strong>.</p>
                    <p>Retenue est de <strong>{{ number_format($cardDetail->subscription_amount, 2) }} {{ $cardDetail->currency }}</strong>.</p>
                @else
                    <p>Retrait normal de <strong>{{ $amount }} {{ $currency }}</strong>.</p>
                    <p>Retenu <strong>{{ $a_retenir }} {{ $currency }}</strong>.</p>
                @endif
                <p>Voulez-vous vraiment continuer ?</p>
            </div>
            <div class="flex justify-end mt-6 space-x-4">
                <button wire:click="closeRetraitConfirmationModal" class="px-4 py-2 font-semibold text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                    Annuler
                </button>
                <button wire:click="makeRetrait" class="px-4 py-2 font-semibold btn btn-success rounded-lg hover:bg-green-600" wire:loading.attr="disabled">
                    <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Confirmer
                </button>
            </div>
        </div>
    </div>
@endif
