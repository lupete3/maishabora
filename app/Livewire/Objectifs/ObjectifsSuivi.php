<?php

namespace App\Livewire\Objectifs;

use App\Models\Objectif;
use App\Services\ObjectifProgressService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Suivi des objectifs : progression globale entreprise + classement et détail par agent.
 */
class ObjectifsSuivi extends Component
{
    #[Url]
    public string $periodType = 'month';

    #[Url]
    public string $reference = '';

    #[Url]
    public string $vue = 'global'; // global | agents

    #[Url]
    public ?int $agentId = null;   // détail d'un agent

    public function mount(): void
    {
        Gate::authorize('afficher-objectifs');

        if (!array_key_exists($this->periodType, config('objectifs.periodes'))) {
            $this->periodType = 'month';
        }
        $this->reference = $this->validDate($this->reference) ?? now()->toDateString();
    }

    private function validDate(?string $date): ?string
    {
        try {
            return $date ? Carbon::parse($date)->toDateString() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function updatedPeriodType(): void
    {
        $this->validate(['periodType' => Rule::in(array_keys(config('objectifs.periodes')))]);
    }

    public function updatedReference(): void
    {
        $this->reference = $this->validDate($this->reference) ?? now()->toDateString();
    }

    public function precedente(): void
    {
        [$start] = ObjectifProgressService::periode($this->periodType, $this->reference);
        $this->reference = ObjectifProgressService::periodePrecedente($this->periodType, $start)->toDateString();
    }

    public function suivante(): void
    {
        [, $end] = ObjectifProgressService::periode($this->periodType, $this->reference);
        $this->reference = $end->copy()->addDay()->toDateString();
    }

    public function aujourdHui(): void
    {
        $this->reference = now()->toDateString();
    }

    public function voirAgent(?int $id): void
    {
        $this->agentId = $id;
        $this->vue = 'agents';
    }

    /** Regroupe les lignes de progression par groupe d'indicateurs, dans l'ordre du catalogue. */
    private function parGroupe(Collection $lignes, array $catalogue): array
    {
        $ordre = array_flip(array_keys($catalogue));
        $groupes = [];

        foreach ($lignes->sortBy(fn ($l) => [$ordre[$l['objectif']->indicator] ?? 999, $l['objectif']->currency]) as $ligne) {
            $groupes[$ligne['def']['group']][] = $ligne;
        }

        return $groupes;
    }

    public function render(ObjectifProgressService $service)
    {
        [$start, $end, $label] = ObjectifProgressService::periode($this->periodType, $this->reference);
        $catalogue = $service->indicateurs(null, false);

        $objectifs = Objectif::with('agent:id,name,postnom,prenom')
            ->forPeriod($this->periodType, $start->toDateString())
            ->get();

        $progression = $service->progression($objectifs);

        $globales = $progression->filter(fn ($l) => $l['objectif']->user_id === null);
        $parAgent = $progression->filter(fn ($l) => $l['objectif']->user_id !== null)
            ->groupBy(fn ($l) => $l['objectif']->user_id);

        // Classement des agents : score moyen (réalisé plafonné à 100 % par objectif)
        $classement = $parAgent->map(function (Collection $lignes, $id) {
            $agent = $lignes->first()['objectif']->agent;

            return [
                'id'        => (int) $id,
                'nom'       => trim(($agent->name ?? '') . ' ' . ($agent->postnom ?? '') . ' ' . ($agent->prenom ?? '')),
                'total'     => $lignes->count(),
                'atteints'  => $lignes->where('statut_label', 'Atteint')->count(),
                'ok'        => $lignes->where('statut', 'success')->count(),
                'vigilance' => $lignes->where('statut', 'warning')->count(),
                'retard'    => $lignes->where('statut', 'danger')->count(),
                'score'     => round($lignes->avg('score'), 1),
            ];
        })->sortByDesc('score')->values();

        $agentDetail = null;
        if ($this->agentId && $parAgent->has($this->agentId)) {
            $agentDetail = [
                'info'    => $classement->firstWhere('id', $this->agentId),
                'groupes' => $this->parGroupe($parAgent->get($this->agentId), $catalogue),
            ];
        }

        return view('livewire.objectifs.objectifs-suivi', [
            'periodes'       => config('objectifs.periodes'),
            'groupesDef'     => config('objectifs.groupes'),
            'periodLabel'    => $label,
            'start'          => $start,
            'end'            => $end,
            'tempsEcoule'    => round(ObjectifProgressService::tempsEcoule($start, $end) * 100, 1),
            'globalGroupes'  => $this->parGroupe($globales, $catalogue),
            'scoreGlobal'    => $globales->isNotEmpty() ? round($globales->avg('score'), 1) : null,
            'classement'     => $classement,
            'agentDetail'    => $agentDetail,
            'canManage'      => Gate::allows('gerer-objectifs'),
        ]);
    }
}
