<div class="card">

    <div class="card-header">
        <h4>Historique de vos connexions et actions</h4>
    </div>

    <div class="card-body">
        <div class="mb-3 d-flex flex-wrap align-items-center gap-2">
            <label class="mb-0 me-2 fw-semibold">Période :</label>
            <select wire:model.lazy="period" class="form-select form-select-sm" style="width:140px;">
                <option value="day">Aujourd'hui</option>
                <option value="week">Cette semaine</option>
                <option value="interval">Intervalle</option>
            </select>

            @if($period === 'interval')
                <input type="date" wire:model.lazy="startDate" class="form-control form-control-sm ms-2" style="width:150px;" />
                <input type="date" wire:model.lazy="endDate" class="form-control form-control-sm ms-2" style="width:150px;" />
            @endif

            <button class="btn btn-sm btn-outline-secondary ms-auto" wire:click="$refresh">Actualiser</button>
        </div>

        <!-- Tableau des cartes -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Appareil</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->action }}</td>
                            <td>{{ $log->description }}</td>
                            <td>{{ $log->device }}</td>
                            <td>{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">Aucune activité enregistrée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-3">
        
            <div>
                {{ $logs->links() }}
            </div>
        </div>

    </div>
</div>
