<?php

namespace App\Livewire\Agent;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;

class ClientsNonCollectes extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $agentId = null;
    public $agents = [];
    public $search = '';

    public function mount()
    {
        $user = Auth::user();

        if ($user->hasAnyRole(['Admin', 'Caissier', 'SUPER IT', 'Comptable'])) {

            $this->agents = User::where('role', '!=', 'membre')
                ->where('status', 1)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'postnom',
                    'prenom'
                ]);

        } else {

            $this->agentId = $user->id;

        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingAgentId()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();

        $agentId = $user->hasAnyRole(['Admin', 'Caissier', 'SUPER IT', 'Comptable'])
            ? $this->agentId
            : $user->id;

        $baseQuery = User::query()
            ->where('role', 'membre')
            ->where('status', 1)
            ->where('is_suspended', false)

            ->when($agentId, function ($query) use ($agentId) {
                $query->where('agent_id', $agentId);
            });

        $stats = (clone $baseQuery)
            ->selectRaw("
                COUNT(*) as totalClients,
                SUM(
                    CASE
                        WHEN DATE(last_transaction_at)=CURDATE()
                        THEN 1
                        ELSE 0
                    END
                ) as collectesAujourdHui,

                SUM(
                    CASE
                        WHEN last_transaction_at IS NULL
                        THEN 1
                        ELSE 0
                    END
                ) as jamaisCollectes,

                SUM(
                    CASE
                        WHEN last_transaction_at IS NOT NULL
                        AND DATE(last_transaction_at) < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                        THEN 1
                        ELSE 0
                    END
                ) as plus7jours
            ")
            ->first();

        $totalClients = (int) $stats->totalClients;
        $collectesAujourdHui = (int) $stats->collectesAujourdHui;
        $jamaisCollectes = (int) $stats->jamaisCollectes;
        $plus7jours = (int) $stats->plus7jours;

        $restants = $totalClients - $collectesAujourdHui;

        $progression = $totalClients > 0
            ? round(($collectesAujourdHui / $totalClients) * 100)
            : 0;

        $clients = (clone $baseQuery)
            ->select([
                'id',
                'code',
                'name',
                'postnom',
                'prenom',
                'telephone',
                'adresse_physique',
                'last_transaction_at'
            ])
            ->where(function ($q) {
                $q->whereNull('last_transaction_at')
                    ->orWhereDate('last_transaction_at', '<', today());
            })
            ->when($this->search, function ($query) {
                $search = '%' . trim($this->search) . '%';
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', $search)
                        ->orWhere('name', 'like', $search)
                        ->orWhere('postnom', 'like', $search)
                        ->orWhere('prenom', 'like', $search)
                        ->orWhere('telephone', 'like', $search);
                });
            })

            ->orderByRaw("
                CASE
                    WHEN last_transaction_at IS NULL THEN 0
                    WHEN DATE(last_transaction_at)
                    <= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1
                    ELSE 2
                END
            ")
            ->orderBy('last_transaction_at')
            ->paginate(20);

        return view('livewire.agent.clients-non-collectes', compact(
            'clients',
            'totalClients',
            'collectesAujourdHui',
            'restants',
            'plus7jours',
            'jamaisCollectes',
            'progression'
        ));
    }
}
