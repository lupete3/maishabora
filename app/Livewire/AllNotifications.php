<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class AllNotifications extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $filter = 'all'; // all, unread

    protected $listeners = ['notificationUpdated' => '$refresh'];

    public function setFilter($filter)
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())->find($id);
        if ($notification) {
            $notification->update(['read' => true]);
            $this->dispatch('notificationUpdated'); // Notify navbar
        }
    }

    public function delete($id)
    {
        $notification = Notification::where('user_id', Auth::id())->find($id);
        if ($notification) {
            $notification->delete();
            $this->dispatch('notificationUpdated'); // Notify navbar
        }
    }

    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())
            ->where('read', false)
            ->update(['read' => true]);

        $this->dispatch('notificationUpdated');
    }

    public function deleteAll()
    {
        Notification::where('user_id', Auth::id())->delete();
        $this->dispatch('notificationUpdated');
    }

    public function render()
    {
        $query = Notification::where('user_id', Auth::id())->latest();

        if ($this->filter === 'unread') {
            $query->where('read', false);
        }

        return view('livewire.all-notifications', [
            'notifications' => $query->paginate(10)
        ])->layout('layouts.app'); // Adjust layout if necessary
    }
}
