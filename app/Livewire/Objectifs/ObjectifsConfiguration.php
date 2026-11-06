<?php

namespace App\Livewire\Objectifs;

use App\Models\Objectif;
use App\Models\ObjectifIndicateur;
use App\Services\ObjectifProgressService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Fixation des objectifs (par agent ou global entreprise) et paramétrage des indicateurs.
 */
class ObjectifsConfiguration extends Component
{
    private const SANS_DEVISE = '_';

    #[Url]
    public string $periodType = 'month';

    #[Url]
    public string $reference = '';

    public string $onglet = 'saisie'; // saisie | outils | indicateurs

    /** 'global' ou id d'agent */
    public string $cible = 'global';

    /** targets[indicateur][devise|_] = valeur saisie */
    public array $targets = [];

    // --- Outil : répartition en masse ---
    public string $bulkIndicator = '';
    public string $bulkCurrency = 'CDF';
    public string $bulkMode = 'identique'; // identique | portefeuille | historique
    public ?string $bulkValue = null;
    public array $bulkAgents = [];

    // --- Outil : reprise de la période précédente ---
    public float $copyCoefficient = 0;     // % d'augmentation
    public bool $copyOverwrite = false;

    // --- Paramétrage des indicateurs ---
    public array $indicateurActif = [];
    public array $indicateurOrdre = [];

    public function mount(): void
    {
        Gate::authorize('gerer-objectifs');

        if (!array_key_exists($this->periodType, config('objectifs.periodes'))) {
            $this->periodType = 'month';
        }
        try {
            $this->reference = $this->reference ? Carbon::parse($this->reference)->toDateString() : now()->toDateString();
        } catch (\Throwable) {
            $this->reference = now()->toDateString();
        }

        $this->chargerObjectifs();
        $this->chargerIndicateurs();
    }

    private function service(): ObjectifProgressService
    {
        return app(ObjectifProgressService::class);
    }

    private function bornes(): array
    {
        return ObjectifProgressService::periode($this->periodType, $this->reference);
    }

    private function agentIdCible(): ?int
    {
        return $this->cible === 'global' ? null : (int) $this->cible;
    }

    // ------------------------------------------------------------------
    // Navigation
    // ------------------------------------------------------------------

    public function updatedPeriodType(): void
    {
        $this->validate(['periodType' => Rule::in(array_keys(config('objectifs.periodes')))]);
        $this->chargerObjectifs();
    }

    public function updatedReference(): void
    {
        try {
            $this->reference = Carbon::parse($this->reference)->toDateString();
        } catch (\Throwable) {
            $this->reference = now()->toDateString();
        }
        $this->chargerObjectifs();
    }

    public function updatedCible(): void
    {
        $this->chargerObjectifs();
    }

    public function precedente(): void
    {
        [$start] = $this->bornes();
        $this->reference = ObjectifProgressService::periodePrecedente($this->periodType, $start)->toDateString();
        $this->chargerObjectifs();
    }

    public function suivante(): void
    {
        [, $end] = $this->bornes();
        $this->reference = $end->copy()->addDay()->toDateString();
        $this->chargerObjectifs();
    }

    // ------------------------------------------------------------------
    // Saisie des objectifs
    // ------------------------------------------------------------------

    public function chargerObjectifs(): void
    {
        [$start] = $this->bornes();

        $this->targets = [];
        Objectif::forPeriod($this->periodType, $start->toDateString())
            ->where('user_id', $this->agentIdCible())
            ->get()
            ->each(function (Objectif $o) {
                $this->targets[$o->indicator][$o->currency ?? self::SANS_DEVISE] = $this->nombre($o->target_value);
            });
    }

    private function nombre(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }

    public function enregistrer(): void
    {
        Gate::authorize('gerer-objectifs');

        $this->validate([
            'targets.*.*' => 'nullable|numeric|min:0',
        ], [
            'targets.*.*.numeric' => 'Chaque objectif doit être un nombre.',
            'targets.*.*.min'     => 'Un objectif ne peut pas être négatif.',
        ]);

        $scope = $this->cible === 'global' ? 'global' : 'agent';
        $catalogue = $this->service()->indicateurs($scope, false);
        [$start, $end] = $this->bornes();
        $agentId = $this->agentIdCible();
        $saved = 0;
        $removed = 0;

        DB::transaction(function () use ($catalogue, $start, $end, $agentId, &$saved, &$removed) {
            foreach ($catalogue as $key => $def) {
                $devises = $def['unit'] === 'amount' ? config('objectifs.devises') : [self::SANS_DEVISE];

                foreach ($devises as $devise) {
                    $valeur = $this->targets[$key][$devise] ?? null;
                    $currency = $devise === self::SANS_DEVISE ? null : $devise;
                    $saved += (int) $this->sauver($key, $agentId, $currency, $valeur, $start, $end, $removed);
                }
            }
        });

        notyf()->success("Objectifs enregistrés ({$saved} actifs" . ($removed ? ", {$removed} supprimés" : '') . ').');
        $this->chargerObjectifs();
    }

    /**
     * Crée / met à jour / supprime un objectif. Une valeur vide supprime l'objectif.
     * @return bool true si un objectif est enregistré
     */
    private function sauver(string $indicator, ?int $agentId, ?string $currency, $valeur, Carbon $start, Carbon $end, int &$removed = 0): bool
    {
        $query = Objectif::where('indicator', $indicator)
            ->where('user_id', $agentId)
            ->where('currency', $currency)
            ->forPeriod($this->periodType, $start->toDateString());

        if ($valeur === null || $valeur === '') {
            $removed += $query->delete();
            return false;
        }

        $existing = $query->first();
        $data = [
            'target_value' => round((float) $valeur, 2),
            'period_end'   => $end->toDateString(),
            'updated_by'   => Auth::id(),
        ];

        if ($existing) {
            $existing->update($data);
        } else {
            Objectif::create($data + [
                'indicator'    => $indicator,
                'user_id'      => $agentId,
                'currency'     => $currency,
                'period_type'  => $this->periodType,
                'period_start' => $start->toDateString(),
                'created_by'   => Auth::id(),
            ]);
        }

        return true;
    }

    // ------------------------------------------------------------------
    // Outils
    // ------------------------------------------------------------------

    public function toggleBulkAgents(): void
    {
        $ids = $this->service()->agents()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->bulkAgents = count($this->bulkAgents) === count($ids) ? [] : $ids;
    }

    /**
     * Attribue un objectif à plusieurs agents :
     *  - identique    : la même valeur pour chacun
     *  - portefeuille : un total réparti au prorata du nombre de clients de chaque agent
     *  - historique   : un total réparti au prorata du réalisé de la période précédente
     */
    public function appliquerEnMasse(): void
    {
        Gate::authorize('gerer-objectifs');

        $agentScope = $this->service()->indicateurs('agent', false);

        $this->validate([
            'bulkIndicator' => ['required', Rule::in(array_keys($agentScope))],
            'bulkMode'      => ['required', Rule::in(['identique', 'portefeuille', 'historique'])],
            'bulkValue'     => 'required|numeric|min:0',
            'bulkAgents'    => 'required|array|min:1',
            'bulkCurrency'  => ['required', Rule::in(config('objectifs.devises'))],
        ], [
            'bulkIndicator.required' => 'Choisissez un indicateur.',
            'bulkValue.required'     => 'Saisissez une valeur.',
            'bulkAgents.required'    => 'Sélectionnez au moins un agent.',
        ]);

        $def = $agentScope[$this->bulkIndicator];
        $currency = $def['unit'] === 'amount' ? $this->bulkCurrency : null;
        $agentIds = array_map('intval', $this->bulkAgents);
        [$start, $end] = $this->bornes();
        $total = (float) $this->bulkValue;

        $poids = match ($this->bulkMode) {
            'portefeuille' => $this->service()->mesurer('portefeuille_clients', $start, $end, null, $agentIds),
            'historique'   => (function () use ($start, $agentIds, $currency) {
                $prevStart = ObjectifProgressService::periodePrecedente($this->periodType, $start);
                [$ps, $pe] = ObjectifProgressService::periode($this->periodType, $prevStart);
                return $this->service()->mesurer($this->bulkIndicator, $ps, $pe, $currency, $agentIds);
            })(),
            default => array_fill_keys($agentIds, 1),
        };

        $somme = array_sum($poids);
        $valeurs = [];
        foreach ($agentIds as $id) {
            if ($this->bulkMode === 'identique') {
                $valeurs[$id] = $total;
            } elseif ($somme > 0) {
                $valeurs[$id] = $total * ($poids[$id] ?? 0) / $somme;
            } else {
                // Pas de base de répartition : parts égales
                $valeurs[$id] = $total / count($agentIds);
            }
            if ($def['unit'] === 'count') {
                $valeurs[$id] = round($valeurs[$id]);
            }
        }

        DB::transaction(function () use ($valeurs, $currency, $start, $end) {
            foreach ($valeurs as $id => $valeur) {
                $this->sauver($this->bulkIndicator, $id, $currency, $valeur, $start, $end);
            }
        });

        notyf()->success('Objectif « ' . $def['label'] . ' » attribué à ' . count($valeurs) . ' agent(s).');
        $this->chargerObjectifs();
    }

    /** Recopie tous les objectifs de la période précédente (agents + global), avec un coefficient. */
    public function reprendrePeriodePrecedente(): void
    {
        Gate::authorize('gerer-objectifs');

        $this->validate(['copyCoefficient' => 'numeric|min:-100|max:1000']);

        [$start, $end] = $this->bornes();
        $prevStart = ObjectifProgressService::periodePrecedente($this->periodType, $start);
        [$ps] = ObjectifProgressService::periode($this->periodType, $prevStart);

        $anciens = Objectif::forPeriod($this->periodType, $ps->toDateString())->get();

        if ($anciens->isEmpty()) {
            notyf()->warning("Aucun objectif sur la période précédente.");
            return;
        }

        $facteur = 1 + $this->copyCoefficient / 100;
        $copies = 0;

        DB::transaction(function () use ($anciens, $facteur, $start, $end, &$copies) {
            foreach ($anciens as $ancien) {
                $existe = Objectif::where('indicator', $ancien->indicator)
                    ->where('user_id', $ancien->user_id)
                    ->where('currency', $ancien->currency)
                    ->forPeriod($this->periodType, $start->toDateString())
                    ->exists();

                if ($existe && !$this->copyOverwrite) {
                    continue;
                }

                $def = $ancien->definition();
                $valeur = $ancien->target_value * $facteur;
                if ($def && $def['unit'] === 'count') {
                    $valeur = round($valeur);
                }

                $this->sauver($ancien->indicator, $ancien->user_id, $ancien->currency, $valeur, $start, $end);
                $copies++;
            }
        });

        notyf()->success("{$copies} objectif(s) repris de la période précédente.");
        $this->chargerObjectifs();
    }

    // ------------------------------------------------------------------
    // Paramétrage des indicateurs
    // ------------------------------------------------------------------

    private function chargerIndicateurs(): void
    {
        foreach ($this->service()->indicateurs(null, false) as $key => $def) {
            $this->indicateurActif[$key] = (bool) $def['is_active'];
            $this->indicateurOrdre[$key] = (int) $def['sort_order'];
        }
    }

    public function enregistrerIndicateurs(): void
    {
        Gate::authorize('gerer-objectifs');

        $this->validate(['indicateurOrdre.*' => 'integer|min:0|max:999']);

        foreach (array_keys(config('objectifs.indicateurs')) as $key) {
            ObjectifIndicateur::updateOrCreate(['key' => $key], [
                'is_active'  => (bool) ($this->indicateurActif[$key] ?? true),
                'sort_order' => (int) ($this->indicateurOrdre[$key] ?? 0),
            ]);
        }

        notyf()->success('Paramétrage des indicateurs enregistré.');
        $this->chargerIndicateurs();
    }

    // ------------------------------------------------------------------

    public function render()
    {
        $service = $this->service();
        [$start, $end, $label] = $this->bornes();
        $agents = $service->agents();
        $scope = $this->cible === 'global' ? 'global' : 'agent';
        $indicateurs = $service->indicateurs($scope);

        // Repères pour fixer des objectifs réalistes : réalisé de la période précédente
        $prevStart = ObjectifProgressService::periodePrecedente($this->periodType, $start);
        [$ps, $pe, $prevLabel] = ObjectifProgressService::periode($this->periodType, $prevStart);
        $agentId = $this->agentIdCible();
        $reperes = [];

        if ($this->onglet === 'saisie') {
            foreach ($indicateurs as $key => $def) {
                $devises = $def['unit'] === 'amount' ? config('objectifs.devises') : [self::SANS_DEVISE];
                foreach ($devises as $devise) {
                    $currency = $devise === self::SANS_DEVISE ? null : $devise;
                    $mesure = $service->mesurer($key, $ps, $pe, $currency, $agentId ? [$agentId] : null);
                    $reperes[$key][$devise] = is_array($mesure) ? ($mesure[$agentId] ?? 0) : $mesure;
                }
            }
        }

        $groupes = [];
        foreach ($indicateurs as $key => $def) {
            $groupes[$def['group']][$key] = $def;
        }

        $nbObjectifsPeriode = Objectif::forPeriod($this->periodType, $start->toDateString())
            ->selectRaw('user_id IS NULL AS est_global, COUNT(*) AS nb')
            ->groupBy('est_global')
            ->pluck('nb', 'est_global');

        return view('livewire.objectifs.objectifs-configuration', [
            'periodes'       => config('objectifs.periodes'),
            'groupesDef'     => config('objectifs.groupes'),
            'devises'        => config('objectifs.devises'),
            'periodLabel'    => $label,
            'prevLabel'      => $prevLabel,
            'start'          => $start,
            'end'            => $end,
            'agents'         => $agents,
            'groupes'        => $groupes,
            'reperes'        => $reperes,
            'sansDevise'     => self::SANS_DEVISE,
            'agentIndicateurs' => $service->indicateurs('agent'),
            'catalogueComplet' => $service->indicateurs(null, false),
            'nbGlobal'       => (int) ($nbObjectifsPeriode[1] ?? 0),
            'nbAgents'       => (int) ($nbObjectifsPeriode[0] ?? 0),
        ]);
    }
}
