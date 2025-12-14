<div>
    <style>
        /* ============================= */
        /* NOTIFICATIONS SNEAT - DESKTOP */
        /* ============================= */

        @media (min-width: 992px) {

            /* Largeur dropdown */
            .notifications-lg {
                width: 380px !important;
                max-width: 380px;
            }

            

            /* Item spacing */
            .dropdown-notifications-item {
                padding: 14px 16px;
            }

            /* Avatar */
            .avatar-md {
                width: 42px;
                height: 42px;
                font-size: 1.2rem;
            }

            /* Title */
            .notification-title {
                font-size: 0.95rem;
                font-weight: 600;
                color: #566a7f;
            }

            /* Message */
            .notification-message {
                font-size: 0.9rem;
                color: #6c757d;
                line-height: 1.4;
            }

            /* Time */
            .notification-time {
                font-size: 0.75rem;
                color: #a1acb8;
            }
        }

        /* ============================= */
        /* NOTIFICATION NON LUE */
        /* ============================= */

        .notification-unread {
            background-color: rgba(105, 108, 255, 0.08);
        }

        .notification-unread:hover {
            background-color: rgba(105, 108, 255, 0.12);
        }

        /* ============================= */
        /* ACTIONS */
        /* ============================= */

        .dropdown-notifications-actions button {
            background: none;
            border: none;
            padding: 4px;
            cursor: pointer;
        }

        .dropdown-notifications-actions i {
            color: #8592a3;
        }

        .dropdown-notifications-actions i:hover {
            color: #696cff;
        }

        /* Badge compteur */
        .notification-badge {
            font-size: 0.65rem;
            padding: 4px 6px;
        }

    </style>

    <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-2" wire:poll.30s>
        <a class="nav-link dropdown-toggle hide-arrow" href="#" data-bs-toggle="dropdown">
            <i class="bx bx-bell bx-sm"></i>

            @if ($unreadCount > 0)
                <span class="badge bg-danger rounded-pill notification-badge">
                    {{ $unreadCount }}
                </span>
            @endif
        </a>

        <ul class="dropdown-menu dropdown-menu-end p-0 notifications-lg">
            <!-- Header -->
            <li class="dropdown-menu-header border-bottom">
                <div class="dropdown-header d-flex align-items-center py-3">
                    <h5 class="mb-0 me-auto fw-semibold">Notifications</h5>

                    <div class="d-flex align-items-center">
                        @if ($unreadCount > 0)
                            <span class="badge bg-label-primary me-2">
                                {{ $unreadCount }} Nouveau(x)
                            </span>
                        @endif

                        <a href="#"
                           wire:click.prevent="markAllAsRead"
                           class="dropdown-notifications-all p-2"
                           data-bs-toggle="tooltip"
                           title="Tout marquer comme lu">
                            <i class="bx bx-envelope-open fs-4"></i>
                        </a>
                    </div>
                </div>
            </li>

            <!-- List -->
            <li class="dropdown-notifications-list scrollable-container">
                <ul class="list-group list-group-flush">

                    @forelse ($notifications as $notification)
                        <li
                            class="list-group-item list-group-item-action dropdown-notifications-item
                            {{ !$notification->read ? 'notification-unread' : 'marked-as-read' }}">

                            <div class="d-flex align-items-start gap-3">

                                <!-- Avatar -->
                                <div class="flex-shrink-0">
                                    <div class="avatar avatar-md avatar-initial rounded-circle bg-label-warning">
                                        <i class="bx bx-bell bx-sm ml-2 mt-2"></i>
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="flex-grow-1">
                                    <h6 class="notification-title mb-1">
                                        {{ $notification->title }}
                                    </h6>

                                    <p class="notification-message mb-1">
                                        {{ $notification->message }}
                                    </p>

                                    <small class="notification-time">
                                        {{ $notification->created_at->diffForHumans(). ' | ' . $notification->created_at->format('Y-m-d H:i:s') }}
                                    </small>
                                </div>

                                <!-- Actions -->
                                <div class="flex-shrink-0 dropdown-notifications-actions">

                                    @if (!$notification->read)
                                        <button
                                            wire:click.prevent="markAsRead({{ $notification->id }})"
                                            class="dropdown-notifications-read"
                                            title="Marquer comme lu">
                                            <span class="badge badge-dot bg-primary"></span>
                                        </button>
                                    @endif

                                    <button
                                        wire:click.prevent="markAsRead({{ $notification->id }})"
                                        class="dropdown-notifications-archive"
                                        title="Supprimer">
                                        <i class="bx bx-x fs-5"></i>
                                    </button>

                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-center text-muted py-4">
                            Aucune notification
                        </li>
                    @endforelse

                </ul>
            </li>
        </ul>
    </li>
</div>
