<?php

namespace App\Http\Controllers;

use App\Exports\CardRenewalExport;
use App\Services\CardRenewalService;
use App\Services\DecisionDashboardService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Carnets non renouvelés (/carnets-non-renouveles) : clients qui ont retiré leur carnet
 * sans en reprendre un, pour les relancer. Export Excel avec les mêmes filtres.
 */
class CardRenewalController extends Controller
{
    public function __construct(private CardRenewalService $carnets, private DecisionDashboardService $periodes)
    {
    }

    public function index(Request $request)
    {
        [$filtres, $periode] = $this->filtres($request);

        $retraits = $this->carnets->retraits($periode, $filtres);
        $aRelancer = $this->carnets->aRelancer($retraits, $filtres['retard_min'], $filtres['masquer_actifs']);

        $page = LengthAwarePaginator::resolveCurrentPage();
        $clients = new LengthAwarePaginator(
            $aRelancer->forPage($page, 50)->values(), $aRelancer->count(), 50, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('carnets.non-renouveles', [
            'filtres' => $filtres,
            'periode' => $periode,
            'navigation' => [
                'precedente' => $this->periodes->referenceVoisine($periode, -1),
                'suivante' => $periode['end']->lt(now()) ? $this->periodes->referenceVoisine($periode, 1) : null,
            ],
            'resume' => $this->carnets->resume($retraits, $aRelancer),
            'agents' => $this->carnets->parAgent($retraits, $aRelancer),
            'clients' => $clients,
            'agentOptions' => $this->agentOptions(),
        ]);
    }

    public function export(Request $request)
    {
        [$filtres, $periode] = $this->filtres($request);

        $retraits = $this->carnets->retraits($periode, $filtres);
        $aRelancer = $this->carnets->aRelancer($retraits, $filtres['retard_min'], $filtres['masquer_actifs']);

        $nom = 'carnets-non-renouveles_' . $periode['start']->format('Y-m-d') . '_' . $periode['end']->format('Y-m-d') . '.xlsx';

        return Excel::download(new CardRenewalExport($aRelancer, $periode['label']), $nom);
    }

    /** Filtres lus dans l'adresse (partageables, repris par l'export). */
    private function filtres(Request $request): array
    {
        $filtres = [
            'periode' => array_key_exists((string) $request->query('periode'), CardRenewalService::PERIODES) ? $request->query('periode') : 'month',
            'ref' => $request->query('ref'),
            'du' => $request->query('du'),
            'au' => $request->query('au'),
            'agent_id' => $request->integer('agent_id') ?: null,
            'devise' => in_array($request->query('devise'), ['CDF', 'USD'], true) ? $request->query('devise') : null,
            'type' => array_key_exists((string) $request->query('type'), CardRenewalService::TYPES) ? $request->query('type') : 'epargne',
            'retard_min' => max(0, min(3650, $request->integer('retard_min'))),
            'masquer_actifs' => $request->boolean('masquer_actifs'),
        ];

        return [$filtres, $this->periodes->periode($filtres['periode'], $filtres['ref'], $filtres['du'], $filtres['au'])];
    }

    /** Agents inscrits sur au moins un carnet. */
    private function agentOptions()
    {
        return DB::table('users')
            ->whereIn('id', DB::table('membership_cards')->whereNotNull('user_id')->distinct()->select('user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'postnom', 'role']);
    }
}
