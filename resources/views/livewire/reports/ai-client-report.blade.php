<div>
    <div class="card mb-4 border-info shadow-sm">
        <div class="card-header bg-label-info d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-info"><i class="bx bx-user me-2"></i> Insights Clients & Épargne</h6>
            <button wire:click="generateReport" class="btn btn-sm btn-info text-white">
                <i class="bx bx-refresh me-1"></i> Actualiser l'analyse
            </button>
        </div>
        <div class="card-body mt-4">
            @if($loading)
                <div class="text-center py-5">
                    <div class="spinner-border text-info" role="status"></div>
                    <p class="mt-2 lead">L'IA analyse la fidélité et les soldes membres...</p>
                </div>
            @else
                <div class="white-space-pre-wrap lead h5">
                    {{ $summaryClientInsights }}
                </div>
            @endif
        </div>
    </div>
</div>