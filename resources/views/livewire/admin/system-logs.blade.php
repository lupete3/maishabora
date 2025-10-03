<div class="container mt-4">
    <h4 class="mb-3">📋 Logs du système</h4>

    <input type="text" class="form-control mb-3" placeholder="Rechercher..."
           wire:model.live="search">
    <div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Niveau</th>
                <th>Message</th>
                <th>Résolu</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($logs as $log)
            <tr @if($log->is_resolved) class="table-success" @endif>
                <td>{{ strtoupper($log->level) }}</td>
                <td>
                    <strong>{{ $log->message }}</strong>
                    <br>
                    <small class="text-muted">{{ Str::limit($log->trace, 100) }}</small>
                </td>
                <td>
                    @if($log->is_resolved)
                        ✅
                    @else
                        ❌
                    @endif
                </td>
                <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                <td>
                    <button wire:click="showDetail({{ $log->id }})"
                            class="btn btn-sm btn-info">
                        Détail
                    </button>

                    @if(!$log->is_resolved)
                        <button wire:click="markAsResolved({{ $log->id }})"
                                class="btn btn-sm btn-success">Résoudre</button>
                    @endif

                    <button wire:click="delete({{ $log->id }})"
                            class="btn btn-sm btn-danger">Supprimer</button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center">Aucun log trouvé</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div>
        {{ $logs->links() }}
    </div>
    </div>


    <!-- Modal Bootstrap -->
    <div wire:ignore.self class="modal fade" id="logDetailModal" tabindex="-1" aria-labelledby="logDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détail du Log</h5>
                    <button type="button" class="btn-close" wire:click="closeModal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    @if($selectedLog)
                        <p><strong>Niveau :</strong> {{ strtoupper($selectedLog->level) }}</p>
                        <p><strong>Message :</strong> {{ $selectedLog->message }}</p>
                        <hr>
                        <pre class="bg-dark text-white p-3 rounded" style="max-height:400px; overflow:auto;">
                            {{ $selectedLog->trace }}
                        </pre>
                    @else
                        <p>Aucun détail à afficher.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
</div>
