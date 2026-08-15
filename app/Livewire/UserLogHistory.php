<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class UserLogHistory extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $perPage = 10;
    public string $period = 'day'; // day, week, interval
    public $startDate;
    public $endDate;

    public function updatedPeriod($value)
    {
        // clear interval dates when switching away from interval
        if ($value !== 'interval') {
            $this->startDate = null;
            $this->endDate = null;
        }

        $this->resetPage();
    }

    public function updatedStartDate()
    {
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Auth::user()->logs()->latest();

        // Apply period filters
        if ($this->period === 'day') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($this->period === 'week') {
            $start = Carbon::now()->startOfWeek();
            $end = Carbon::now()->endOfWeek();
            $query->whereBetween('created_at', [$start, $end]);
        } elseif ($this->period === 'interval' && $this->startDate && $this->endDate) {
            // ensure proper date order
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        }

        $logs = $query->paginate($this->perPage);

        return view('livewire.user-log-history', [
            'logs' => $logs
        ]);
    }
}
