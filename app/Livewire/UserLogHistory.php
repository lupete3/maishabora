<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class UserLogHistory extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $perPage = 10;

    public function render()
    {
        $logs = Auth::user()->logs()->latest()->paginate($this->perPage);

        return view('livewire.user-log-history', [
            'logs' => $logs
        ]);
    }
}
