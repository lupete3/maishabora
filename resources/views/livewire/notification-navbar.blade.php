<div>
    <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-2" wire:poll.30s>
        <a class="nav-link dropdown-toggle hide-arrow" href="#" data-bs-toggle="dropdown">
            <i class="bx bx-bell bx-sm"></i>
            @if ($unreadCount > 0)
                <span class="badge bg-danger rounded-pill">{{ $unreadCount }}</span>
            @endif
        </a>

        <ul class="dropdown-menu dropdown-menu-end p-0">
            <li class="dropdown-menu-header border-bottom">
                <div class="dropdown-header d-flex align-items-center py-3">
                    <h6 class="mb-0 me-auto">Notifications</h6>
                    <div class="d-flex align-items-center h6 mb-0">
                        @if ($unreadCount > 0)
                            <span class="badge bg-label-primary me-2">{{ $unreadCount }} New</span>
                        @endif
                        <a href="#" class="dropdown-notifications-all p-2" wire:click.prevent="markAllAsRead"
                            data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Tout marquer comme lu">
                            <i class="icon-base bx bx-envelope-open text-heading"></i>
                        </a>
                    </div>
                </div>
            </li>

            <li class="dropdown-notifications-list scrollable-container">
                <ul class="list-group list-group-flush">
                    @forelse ($notifications as $notification)
                        <li
                            class="list-group-item list-group-item-action dropdown-notifications-item @if ($notification->read) marked-as-read @endif">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-initial rounded-circle bg-label-info">
                                        <i class="bx bx-bell m-2"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="small mb-0">{{ $notification->title }}</h6>
                                    <small class="mb-1 d-block text-body">{{ $notification->message }}</small>
                                    <small
                                        class="text-body-secondary">{{ $notification->created_at->diffForHumans() }}</small>
                                </div>
                                <div class="flex-shrink-0 dropdown-notifications-actions">
                                    @if (!$notification->read)
                                        <button wire:click.prevent="markAsRead({{ $notification->id }})"
                                            class="dropdown-notifications-read">
                                            <span class="badge badge-dot"></span>
                                        </button>
                                    @endif
                                    <button wire:click.prevent="markAsRead({{ $notification->id }})"
                                        class="dropdown-notifications-archive">
                                        <span class="icon-base bx bx-x"></span>
                                    </button>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted">Aucune notification</li>
                    @endforelse
                </ul>
            </li>
        </ul>
    </li>

</div>
