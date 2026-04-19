<div class="container-xxl flex-grow-1 container-p-y" wire:poll.30s>
    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Toutes les notifications</h5>
            <div>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary {{ $filter === 'all' ? 'active' : '' }}"
                        wire:click="setFilter('all')">
                        Toutes
                    </button>
                    <button type="button" class="btn btn-outline-primary {{ $filter === 'unread' ? 'active' : '' }}"
                        wire:click="setFilter('unread')">
                        Non lues
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body mt-4">
            <div class="d-flex justify-content-end gap-2 mb-3">
                <button class="btn btn-sm btn-label-secondary" wire:click="markAllAsRead" wire:loading.attr="disabled">
                    <i class="bx bx-envelope-open me-1"></i> Tout marquer comme lu
                </button>
                <button class="btn btn-sm btn-label-danger" wire:click="deleteAll" wire:loading.attr="disabled"
                    onclick="return confirm('Êtes-vous sûr de vouloir tout supprimer ?') || event.stopImmediatePropagation()">
                    <i class="bx bx-trash me-1"></i> Tout supprimer
                </button>
            </div>

            <ul class="list-group">
                @forelse ($notifications as $notification)
                    <li
                        class="list-group-item d-flex justify-content-between align-items-center {{ !$notification->read ? 'bg-label-secondary' : '' }}">
                        <div class="d-flex align-items-start gap-3">
                            <div class="avatar avatar-md bg-label-primary rounded p-2">
                                <i class="bx bx-bell fs-3"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">{{ $notification->title }}</h6>
                                <p class="mb-1 text-muted">{{ $notification->message }}</p>
                                <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            @if (!$notification->read)
                                <button class="btn btn-icon btn-sm btn-label-primary"
                                    wire:click="markAsRead({{ $notification->id }})" title="Marquer comme lu">
                                    <i class="bx bx-check"></i>
                                </button>
                            @endif
                            <button class="btn btn-icon btn-sm btn-label-danger"
                                wire:click="delete({{ $notification->id }})" title="Supprimer">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </li>
                @empty
                    <li class="list-group-item text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <i class="bx bx-bell-off fs-1 text-muted mb-3"></i>
                            <p class="text-muted mb-0">Aucune notification trouvée</p>
                        </div>
                    </li>
                @endforelse
            </ul>

            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        </div>
    </div>
</div>
