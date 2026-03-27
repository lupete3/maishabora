<div>
    <div class="card mb-4 border-primary shadow-sm">
        <div class="card-header bg-label-primary d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-primary"><i class="bx bx-credit-card me-2"></i> Analyse du Portefeuille Crédit &
                Recouvrement</h6>
            <button wire:click="generateReport" class="btn btn-sm btn-primary">
                <i class="bx bx-refresh me-1"></i> Actualiser l'analyse
            </button>
        </div>
        <div class="card-body mt-4">
            @if($loading)
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 lead">L'IA analyse vos remboursements et retards...</p>
                </div>
            @else
                <div class="white-space-pre-wrap lead h5">
                    {{ $summaryCreditPerformance }}
                </div>
            @endif
        </div>
    </div>
</div>