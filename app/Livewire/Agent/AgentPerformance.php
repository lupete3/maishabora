<?php

namespace App\Livewire\Agent;

use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AgentPerformance extends Component
{
    /**
     * Types de transaction comparables (clé normalisée => libellé).
     * Les valeurs en base sont ramenées à ces clés par normalizeType()
     * (ex : "dépôt", "Dépôt", "depot" => "depot").
     */
    public const TYPES = [
        'vente_carte_adhesion'   => "Vente carte d'adhésion",
        'mise_quotidienne'       => 'Mise quotidienne',
        'depot'                  => 'Dépôt',
        'retrait'                => 'Retrait',
        'retrait_carte_adhesion' => "Retrait carte d'adhésion",
    ];

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
    public array $typeSummary = [];
    public array $evaluatedTypes = [];
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

    /** Mêmes agents que les cartes du dashboard (comptes visibles uniquement) */
    protected function agentsQuery()
    {
        return User::where('status', true)
            ->whereHas('agentAccounts', fn ($q) => $q->where('is_visible_dashboard', true))
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
            'selectedAgents.required' => 'Sélectionnez au moins un agent.',
            'selectedAgents.min'      => 'Sélectionnez au moins un agent.',
            'selectedTypes.required'  => 'Sélectionnez au moins un type de transaction.',
            'selectedTypes.min'       => 'Sélectionnez au moins un type de transaction.',
            'startDate.required_if'   => 'La date de début est obligatoire.',
            'endDate.required_if'     => 'La date de fin est obligatoire.',
            'endDate.after_or_equal'  => 'La date de fin doit être postérieure ou égale à la date de début.',
        ];
    }

    /** Retourne toujours des bornes [début, fin] pour un whereBetween. */
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

    /**
     * Ramène une valeur de la base à la clé de TYPES.
     * Nécessaire car la collation MySQL *_ci ignore les accents dans le WHERE
     * ("depot" = "dépôt"), mais PHP, lui, les distingue.
     */
    protected function normalizeType(?string $type): string
    {
        $slug = Str::slug((string) $type, '_');

        return array_key_exists($slug, self::TYPES) ? $slug : (string) $type;
    }

    public function evaluate(): void
    {
        Gate::authorize('afficher-caisse-agent');

        $this->validate();

        [$start, $end, $this->periodLabel] = $this->resolvePeriod();

        $agentIds = array_map('intval', $this->selectedAgents);

        // Types dans l'ordre de référence (affichage cohérent partout)
        $this->evaluatedTypes = array_values(array_intersect(array_keys(self::TYPES), $this->selectedTypes));

        // UNE seule requête : agent × type => nb, CDF, USD
        $rows = Transaction::query()
            ->whereIn('user_id', $agentIds)
            ->whereIn('type', $this->evaluatedTypes)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw(
                'user_id, type,
                 COUNT(*) AS nb,
                 COALESCE(SUM(CASE WHEN currency = ? THEN amount ELSE 0 END), 0) AS total_cdf,
                 COALESCE(SUM(CASE WHEN currency = ? THEN amount ELSE 0 END), 0) AS total_usd',
                ['CDF', 'USD']
            )
            ->groupBy('user_id', 'type')
            ->get();

        $empty = ['count' => 0, 'cdf' => 0.0, 'usd' => 0.0];
        $perAgent = [];
        $this->typeSummary = array_fill_keys($this->evaluatedTypes, $empty);

        foreach ($rows as $r) {
            $key = $this->normalizeType($r->type);
            $uid = (int) $r->user_id;

            $perAgent[$uid][$key] ??= $empty;
            $this->typeSummary[$key] ??= $empty;

            foreach ([&$perAgent[$uid][$key], &$this->typeSummary[$key]] as &$bucket) {
                $bucket['count'] += (int) $r->nb;
                $bucket['cdf']   += (float) $r->total_cdf;
                $bucket['usd']   += (float) $r->total_usd;
            }
            unset($bucket);
        }

        $this->results = User::whereIn('id', $agentIds)
            ->get(['id', 'name', 'postnom'])
            ->map(function ($agent) use ($perAgent, $empty) {
                $byType = [];
                foreach (array_keys($this->typeSummary) as $type) {
                    $byType[$type] = $perAgent[$agent->id][$type] ?? $empty;
                }

                return [
                    'id'      => $agent->id,
                    'name'    => trim($agent->name . ' ' . $agent->postnom),
                    'count'   => array_sum(array_column($byType, 'count')),
                    'cdf'     => array_sum(array_column($byType, 'cdf')),
                    'usd'     => array_sum(array_column($byType, 'usd')),
                    'by_type' => $byType,
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

    /** Tri décroissant selon le critère, départage count > cdf > usd, ex æquo = même rang. */
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

    public function exportPDF()
    {
        Gate::authorize('afficher-caisse-agent');

        $this->evaluate();

        [$start, $end] = $this->resolvePeriod();

        $pdf = Pdf::loadView('pdf.agent-performance-operations', [
            'results'     => $this->results,
            'totals'      => $this->totals,
            'typeSummary' => $this->typeSummary,
            'types'       => self::TYPES,
            'criterion'   => self::CRITERIA[$this->sortBy],
            'sortBy'      => $this->sortBy,
            'periodLabel' => $this->periodLabel,
            'startDate'   => $start,
            'endDate'     => $end,
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'performance_agents_' . now()->format('Ymd_His') . '.pdf');
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
