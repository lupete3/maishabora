<?php

namespace App\Livewire\Comptabilite;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Carbon\Carbon;
use Livewire\WithPagination;


class CollectorIndicators extends Component
{
    public $agentId = null; // Tous les agents
    public $period = 'month'; // today, week, month, year, custom
    public $startDate;
    public $endDate;
    public $status = 'all';

    use WithPagination;
    protected $paginationTheme = 'bootstrap';

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
            'members' => $this->getMembersQuery()
            ->select(
                'id',
                'code',
                'name',
                'postnom',
                'prenom',
                'telephone',
                'agent_id',
                'last_transaction_at'
            )
            ->with('agent:id,name,postnom')
            ->paginate(20)
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
        $base = $this->baseQuery();
        $total = (clone $base)->count();

        // 1. Actifs : Moins de 30 jours
        $active = (clone $base)
            ->where('last_transaction_at', '>=', now()->subDays(30))
            ->count();

        // 2. A relancer : Entre 31 jours et 90 jours
        $follow = (clone $base)
            ->whereBetween('last_transaction_at', [
                now()->subDays(90)->startOfDay(),
                now()->subDays(31)->endOfDay()
            ])
            ->count();

        // 3. Inactifs : Plus de 90 jours OU Jamais de transaction
        $inactive = (clone $base)
            ->where(function ($q) {
                $q->whereNull('last_transaction_at')
                ->orWhere('last_transaction_at', '<', now()->subDays(90)->startOfDay());
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
            'active_rate' => $total ? round($active * 100 / $total, 2) : 0,
            'follow_rate' => $total ? round($follow * 100 / $total, 2) : 0,
            'inactive_rate' => $total ? round($inactive * 100 / $total, 2) : 0,
            'never_moved' => $neverMoved,
        ];
    }

    private function getMembersQuery()
    {
        $query = User::query()
            ->where('role', 'membre')
            ->when($this->agentId, fn($q) => $q->where('agent_id', $this->agentId));

        switch ($this->status) {
            case 'active':
                $query->where('last_transaction_at', '>=', now()->subDays(30));
                break;

            case 'follow':
                $query->whereBetween('last_transaction_at', [
                    now()->subDays(90)->startOfDay(),
                    now()->subDays(31)->endOfDay()
                ]);
                break;

            case 'inactive':
                $query->where(function ($q) {
                    $q->whereNull('last_transaction_at')
                    ->orWhere('last_transaction_at', '<', now()->subDays(90)->startOfDay());
                });
                break;
        }

        return $query;
    }

    private function getAgents()
    {
        return User::query()
            ->where('role', '!=', 'membre')
            ->orderBy('name')
            ->select('id', 'name', 'postnom')
            ->get();
    }


    public function exportPdf()
    {
        $members = $this->getMembersQuery()

            ->with('agent:id,name,postnom')

            ->orderBy('name')

            ->get();

        $pdf = Pdf::loadView(
            'pdf.members-indicators',
            [
                'members' => $members,
                'status' => $this->status,
                'periodLabel' => $this->getPeriodLabel(),
                'agentName' => $this->agentId
                    ? User::find($this->agentId)?->name
                    : null,
                'agentLastName' => $this->agentId
                    ? User::find($this->agentId)?->postnom
                    : null
            ]
        );

        return response()->streamDownload(

            fn () => print($pdf->output()),

            'indicateurs-membres-'.now()->format('YmdHis').'.pdf'

        );

    }

    private function getPeriodLabel(): string
    {
        return match ($this->period) {

            'today' => "Aujourd'hui",

            'week' => "Cette semaine (" .
                now()->startOfWeek()->format('d/m/Y') .
                " au " .
                now()->endOfWeek()->format('d/m/Y') . ")",

            'month' => "Ce mois (" .
                now()->startOfMonth()->format('d/m/Y') .
                " au " .
                now()->endOfMonth()->format('d/m/Y') . ")",

            'year' => "Année " . now()->year,

            'custom' => "Du " .
                Carbon::parse($this->startDate)->format('d/m/Y') .
                " au " .
                Carbon::parse($this->endDate)->format('d/m/Y'),

            default => "Période non définie",
        };
    }
}
