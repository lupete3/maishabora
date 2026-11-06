<?php

namespace App\Livewire\Agent;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AgentPerformance extends Component
{
    /**
     * Types de transaction comparables.
     * ⚠ Les clés doivent correspondre EXACTEMENT aux valeurs de la colonne `transactions.type`.
     */
    public const TYPES = [
        'vente_carte_adhesion'   => "Vente carte d'adhésion",
        'mise_quotidienne'       => 'Mise quotidienne',
        'depot'                  => 'Dépôt',
        'retrait'                => 'Retrait',
        'retrait_carte_adhesion' => "Retrait carte d'adhésion",
    ];

    /** Critères de classement */
    public const CRITERIA = [
        'count' => "Nombre d'opérations",
        'cdf'   => 'Montant total CDF',
        'usd'   => 'Montant total USD',
    ];

    public const FILTERS = [
        'day'    => "Aujourd'hui",
        'week'   => 'Cette semaine',
        'month'  => 'Ce mois',
        'year'   => 'Cette année',
        'custom' => 'Personnalisé',
    ];

    public bool $showModal = false;

    // Formulaire
    public array $selectedAgents = [];
    public array $selectedTypes = [];
    public string $filter = 'month';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $sortBy = 'count';

    // Résultats
    public array $results = [];
    public array $totals = ['count' => 0, 'cdf' => 0, 'usd' => 0];
    public ?string $periodLabel = null;
    public bool $hasResults = false;

    public function open(): void
    {
        Gate::authorize('afficher-caisse-agent');

        $this->resetValidation();

        if (empty($this->selectedTypes)) {
            $this->selectedTypes = array_keys(self::TYPES);
        }

        $this->showModal = true;
    }

    public function close(): void
    {
        $this->showModal = false;
    }

    /** Agents éligibles : actifs et possédant au moins un compte agent */
    protected function agentsQuery()
    {
        return User::where('status', true)
            ->whereHas('agentAccounts')
            ->orderBy('name')
            ->get(['id', 'name', 'postnom']);
    }

    public function toggleAllAgents(): void
    {
        $ids = $this->agentsQuery()->pluck('id')->map(fn ($id) => (string) $id)->all();

        $this->selectedAgents = count($this->selectedAgents) === count($ids) ? [] : $ids;
    }

    public function toggleAllTypes(): void
    {
        $all = array_keys(self::TYPES);

        $this->selectedTypes = count($this->selectedTypes) === count($all) ? [] : $all;
    }

    /** Re-tri instantané quand on change le critère, sans refaire la requête */
    public function updatedSortBy(): void
    {
        if ($this->hasResults) {
            $this->sortResults();
        }
    }

    public function updatedFilter(): void
    {
        $this->hasResults = false;
    }

    protected function rules(): array
    {
        return [
            'selectedAgents'   => 'required|array|min:1',
            'selectedAgents.*' => 'integer',
            'selectedTypes'    => 'required|array|min:1',
            'selectedTypes.*'  => Rule::in(array_keys(self::TYPES)),
            'filter'           => Rule::in(array_keys(self::FILTERS)),
            'sortBy'           => Rule::in(array_keys(self::CRITERIA)),
            'startDate'        => 'nullable|required_if:filter,custom|date',
            'endDate'          => 'nullable|required_if:filter,custom|date|after_or_equal:startDate',
        ];
    }

    protected function messages(): array
    {
        return [
            'selectedAgents.required'  => 'Sélectionnez au moins un agent.',
            'selectedAgents.min'       => 'Sélectionnez au moins un agent.',
            'selectedTypes.required'   => 'Sélectionnez au moins un type de transaction.',
            'selectedTypes.min'        => 'Sélectionnez au moins un type de transaction.',
            'startDate.required_if'    => 'La date de début est obligatoire.',
            'endDate.required_if'      => 'La date de fin est obligatoire.',
            'endDate.after_or_equal'   => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }

    /**
     * Retourne toujours des bornes [début, fin] utilisées dans un whereBetween.
     */
    protected function resolvePeriod(): array
    {
        $now = now();

        switch ($this->filter) {
            case 'day':
                $start = $now->copy()->startOfDay();
                $end   = $now->copy()->endOfDay();
                $label = "Aujourd'hui (" . $start->format('d/m/Y') . ')';
                break;

            case 'week':
                $start = $now->copy()->startOfWeek();
                $end   = $now->copy()->endOfWeek();
                $label = 'Semaine du ' . $start->format('d/m/Y') . ' au ' . $end->format('d/m/Y');
                break;

            case 'year':
                $start = $now->copy()->startOfYear();
                $end   = $now->copy()->endOfYear();
                $label = 'Année ' . $start->year;
                break;

            case 'custom':
                $start = Carbon::parse($this->startDate)->startOfDay();
                $end   = Carbon::parse($this->endDate)->endOfDay();
                $label = 'Du ' . $start->format('d/m/Y') . ' au ' . $end->format('d/m/Y');
                break;

            case 'month':
            default:
                $start = $now->copy()->startOfMonth();
                $end   = $now->copy()->endOfMonth();
                $label = 'Mois de ' . ucfirst($start->copy()->locale('fr')->isoFormat('MMMM YYYY'));
                break;
        }

        return [$start, $end, $label];
    }

    public function evaluate(): void
    {
        Gate::authorize('afficher-caisse-agent');

        $this->validate();

        [$start, $end, $this->periodLabel] = $this->resolvePeriod();

        $agentIds = array_map('intval', $this->selectedAgents);

        $base = Transaction::query()
            ->whereIn('user_id', $agentIds)
            ->whereIn('type', $this->selectedTypes)
            ->whereBetween('created_at', [$start, $end]);

        // 1 seule requête agrégée : nb d'opérations + totaux par devise
        $stats = (clone $base)
            ->selectRaw(
                'user_id,
                 COUNT(*) AS nb_operations,
                 COALESCE(SUM(CASE WHEN currency = ? THEN amount ELSE 0 END), 0) AS total_cdf,
                 COALESCE(SUM(CASE WHEN currency = ? THEN amount ELSE 0 END), 0) AS total_usd',
                ['CDF', 'USD']
            )
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        // Détail du nombre d'opérations par type
        $byType = (clone $base)
            ->selectRaw('user_id, type, COUNT(*) AS nb')
            ->groupBy('user_id', 'type')
            ->get()
            ->groupBy('user_id');

        // Les agents sans aucune opération apparaissent aussi (avec 0)
        $this->results = User::whereIn('id', $agentIds)
            ->get(['id', 'name', 'postnom'])
            ->map(function ($agent) use ($stats, $byType) {
                $s = $stats->get($agent->id);

                return [
                    'id'      => $agent->id,
                    'name'    => trim($agent->name . ' ' . $agent->postnom),
                    'count'   => (int) ($s->nb_operations ?? 0),
                    'cdf'     => (float) ($s->total_cdf ?? 0),
                    'usd'     => (float) ($s->total_usd ?? 0),
                    'by_type' => collect($byType->get($agent->id, []))
                        ->mapWithKeys(fn ($row) => [$row->type => (int) $row->nb])
                        ->all(),
                ];
            })
            ->values()
            ->all();

        $this->totals = [
            'count' => array_sum(array_column($this->results, 'count')),
            'cdf'   => array_sum(array_column($this->results, 'cdf')),
            'usd'   => array_sum(array_column($this->results, 'usd')),
        ];

        $this->sortResults();
        $this->hasResults = true;
    }

    /**
     * Classement décroissant selon le critère choisi,
     * départage : nb d'opérations, puis CDF, puis USD.
     * Les ex æquo partagent le même rang.
     */
    protected function sortResults(): void
    {
        $crit = $this->sortBy;
        $order = array_unique([$crit, 'count', 'cdf', 'usd']);

        usort($this->results, function ($a, $b) use ($order) {
            foreach ($order as $key) {
                if ($a[$key] != $b[$key]) {
                    return $b[$key] <=> $a[$key];
                }
            }
            return strcmp($a['name'], $b['name']);
        });

        $total = $this->totals[$crit] ?: 0;
        $rank = 0;
        $previous = null;

        foreach ($this->results as $i => &$row) {
            if ($previous === null || $row[$crit] != $previous) {
                $rank = $i + 1;
                $previous = $row[$crit];
            }
            $row['rank']  = $rank;
            $row['share'] = $total > 0 ? round($row[$crit] / $total * 100, 1) : 0;
        }
        unset($row);
    }

    public function render()
    {
        return view('livewire.agent.agent-performance', [
            'agents'   => $this->showModal ? $this->agentsQuery() : collect(),
            'types'    => self::TYPES,
            'criteria' => self::CRITERIA,
            'filters'  => self::FILTERS,
        ]);
    }
}
