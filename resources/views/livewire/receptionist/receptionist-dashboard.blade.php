<!-- resources/views/livewire/global-credit-dashboard.blade.php -->

<div class="container mt-4">
    <!-- Statistiques des crédits -->
    <div class="row">
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card card-border-shadow border-start-primary">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Totals Clients</h6>
                        <h4 class="mb-0">{{ $totalUsers }}</h4>
                    </div>
                    <div class="avatar bg-primary text-white rounded-circle shadow">
                        <i class="bx bx-user fs-4 m-2"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card card-border-shadow border-start-success">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Totals Clients Actifs</h6>
                        <h4 class="mb-0">{{ $totalUsersActifs }}</h4>
                    </div>
                    <div class="avatar bg-success text-white rounded-circle shadow">
                        <i class="bx bx-user fs-4 m-2"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card card-border-shadow border-start-danger">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Totals Clients Bloqués</h6>
                        <h4 class="mb-0">{{ $totalUsersInactifs }}</h4>
                    </div>
                    <div class="avatar bg-danger text-white rounded-circle shadow">
                        <i class="bx bx-user fs-4 m-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
