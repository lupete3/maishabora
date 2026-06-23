<?php

namespace App\Livewire\Comptabilite;

use App\Models\User;
use Livewire\Component;
use Carbon\Carbon;


class CollectorIndicators extends Component
{
    public $agentId = null; // Tous les agents
    public $period = 'month'; // today, week, month, year, custom
    public $startDate;
    public $endDate;

    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function render()
    {
        return view('livewire.comptabilite.collector-indicators', [
            'stats' => $this->getStatistics(),
            'agents' => $this->getAgents(),
        ]);
    }

    private function getPeriodDates(): array
    {
        return match ($this->period) {

            'today' => [
                now()->startOfDay(),
                now()->endOfDay(),
            ],

            'week' => [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ],

            'month' => [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ],

            'year' => [
                now()->startOfYear(),
                now()->endOfYear(),
            ],

            'custom' => [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ],

            default => [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ],
        };
    }

    private function baseQuery()
    {
        return User::query()

            // Clients seulement
            ->where('role', 'membre')

            // Filtre agent
            ->when(
                $this->agentId,
                fn($q) => $q->where('agent_id', $this->agentId)
            );
    }

    public function getStatistics(): array
    {
        [$start, $end] = $this->getPeriodDates();

        $base = $this->baseQuery();

        $total = (clone $base)->count();

        $active = (clone $base)
            ->whereBetween('last_transaction_at', [$start, $end])
            ->count();

        $follow = (clone $base)
            ->whereBetween(
                'last_transaction_at',
                [
                    now()->subDays(90),
                    now()->subDays(31)
                ]
            )
            ->count();

        $inactive = (clone $base)
            ->where(function ($q) {

                $q->whereNull('last_transaction_at')
                    ->orWhere(
                        'last_transaction_at',
                        '<',
                        now()->subDays(90)
                    );
            })
            ->count();
        
        $neverMoved = (clone $base)
            ->whereNull('last_transaction_at')
            ->count();

        return [

            'total' => $total,

            'active' => $active,

            'follow' => $follow,

            'inactive' => $inactive,

            'active_rate' => $total
                ? round($active * 100 / $total, 2)
                : 0,

            'follow_rate' => $total
                ? round($follow * 100 / $total, 2)
                : 0,

            'inactive_rate' => $total
                ? round($inactive * 100 / $total, 2)
                : 0,

            'never_moved' => $neverMoved,
        ];
    }

    private function getAgents()
    {
        return User::query()
            ->where('role', 'agent')
            ->orderBy('name')
            ->select('id', 'name', 'postnom')
            ->get();
    }
}
