<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SystemLog;

class SystemLogs extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $selectedLog = null;

    public function markAsResolved($id)
    {
        $log = SystemLog::find($id);
        if ($log) {
            $log->is_resolved = true;
            $log->save();
        }
    }

    public function delete($id)
    {
        SystemLog::where('id', $id)->delete();
    }

    public function showDetail($id)
    {
        $this->selectedLog = SystemLog::find($id);
        $this->dispatch('openModal', name: 'logDetailModal');
    }

    public function closeModal()
    {
        $this->dispatch('closeModal', name: 'logDetailModal');
    }

    public function render()
    {
        $logs = SystemLog::where('message', 'like', "%{$this->search}%")
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.admin.system-logs', [
            'logs' => $logs,
        ]);
    }
}

