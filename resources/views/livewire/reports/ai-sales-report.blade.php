<div>
    <div class="card mb-4 border-success shadow-sm">
        <div class="card-header bg-label-success d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-success"><i class="bx bx-trending-up me-2"></i> Performance des Ventes (Cartes/Carnets)
            </h6>
            <button wire:click="generateReport" class="btn btn-sm btn-success">
                <i class="bx bx-refresh me-1"></i> Actualiser l'analyse
            </button>
        </div>
        <div class="card-body mt-4">
            @if($loading)
                <div class="text-center py-5">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="mt-2 lead">L'IA analyse le volume des adhésions...</p>
                </div>
            @else
                <div class="white-space-pre-wrap lead h5">
                    {{ $summarySalesPerformance }}
                </div>
            @endif
        </div>
    </div>
</div>
