<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationNavbar extends Component
{
    public $notifications;
    public $unreadCount = 0;

    protected $listeners = ['notificationUpdated' => 'loadNotifications'];

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        $this->notifications = Notification::where('user_id', Auth::user()->id)
            ->where('read', false) // ✅ garder seulement les non lues
            ->latest()
            ->take(5)
            ->get();

        $this->unreadCount = $this->notifications->count();
    }

    public function markAsRead($notificationId)
    {
        $notification = Notification::find($notificationId);

        if ($notification && !$notification->read) {
            $notification->read = true;
            $notification->save();
        }

        $this->loadNotifications(); // ✅ recharge après modification
    }

    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::user()->id)
            ->where('read', false)
            ->update(['read' => true]);

        $this->loadNotifications();
    }


    public function render()
    {
        return view('livewire.notification-navbar');
    }
}

