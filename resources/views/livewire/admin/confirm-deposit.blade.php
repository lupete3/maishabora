<!-- resources/views/livewire/admin/confirm-deposit.blade.php -->
@if($openConfirmDepositNormal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-800 bg-opacity-50">
        <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-lg">
            <div class="flex items-center justify-between pb-4 border-b">
                <h3 class="text-lg font-semibold">Confirmer le Dépôt</h3>
                <button wire:click="closeDepositConfirmationModal" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="mt-4">
                <p>Vous êtes sur le point de faire un dépôt de <strong>{{ $amount }} {{ $currency }}</strong>.</p>
                <p>Pour le membre <strong>{{ $member->name.' '.$member->postnom.' '.$member->prenom }}</strong>, avec le compte <strong>{{ $member->code }}</strong>.</p>
                <p>Du type d'opération <strong>{{ ucfirst($operation_type) }}</strong>.</p>
                <p>Voulez-vous vraiment continuer ?</p>
            </div>
            <div class="flex justify-end mt-6 space-x-4">
                <button wire:click="closeDepositConfirmationModal" class="px-4 py-2 font-semibold text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                    Annuler
                </button>
                <button wire:click="makeDeposit" class="px-4 py-2 font-semibold btn btn-success rounded-lg hover:bg-green-600" wire:loading.attr="disabled">
                    <span wire:loading class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Confirmer
                </button>
            </div>
        </div>
    </div>
@endif
