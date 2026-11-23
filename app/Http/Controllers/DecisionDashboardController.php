<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DecisionAlertsService;
use App\Services\DecisionDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Cockpit décisionnel (/tableau-de-bord-decisionnel) : synthèse, graphiques, alertes et décisions.
 * Les calculs sont dans DecisionDashboardService et DecisionAlertsService (voir docs/cockpit-decisionnel.md).
 */
class DecisionDashboardController extends Controller
{
    public function index(Request $request, DecisionDashboardService $cockpit, DecisionAlertsService $decisions)
    {
        if (! Auth::user()->isActive()) {
            return view('not-found');
        }

        // Filtres (dans l'adresse, pour pouvoir partager ou recharger la même vue)
        $filtres = [
            'periode' => array_key_exists((string) $request->query('periode'), DecisionDashboardService::PERIODES) ? $request->query('periode') : 'month',
            'ref' => $request->query('ref'),
            'du' => $request->query('du'),
            'au' => $request->query('au'),
            'devise' => in_array($request->query('devise'), DecisionDashboardService::DEVISES, true) ? $request->query('devise') : 'CDF',
            'agent_id' => $request->integer('agent_id') ?: null,
        ];
        if ($filtres['agent_id'] && ! User::whereKey($filtres['agent_id'])->where('role', '!=', 'membre')->exists()) {
            $filtres['agent_id'] = null;
        }
        $devise = $filtres['devise'];
        $agentId = $filtres['agent_id'];

        $periode = $cockpit->periode($filtres['periode'], $filtres['ref'], $filtres['du'], $filtres['au']);
        $synthese = $cockpit->synthese($periode, $devise, $agentId);
        $graphiques = $cockpit->graphiques($periode, $devise, $agentId);
        $navigation = [
            'precedente' => $cockpit->referenceVoisine($periode, -1),
            // Pas de période suivante au-delà d'aujourd'hui
            'suivante' => $periode['end']->lt(now()) ? $cockpit->referenceVoisine($periode, 1) : null,
        ];

        // Listes de travail, puis alertes et décisions qui s'appuient dessus
        $credits = $decisions->creditsASurveiller($devise, $agentId);
        $inactifs = $decisions->clientsInactifs($devise, $agentId);
        $agents = $decisions->performanceAgents($periode, $devise, $agentId);
        $alertes = $decisions->alertes($synthese, $periode, $credits, $inactifs, $agents, $agentId);

        $agentOptions = User::where('role', '!=', 'membre')
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'postnom', 'prenom', 'role']);

        return view('decision-dashboard', compact(
            'filtres',
            'periode',
            'synthese',
            'graphiques',
            'navigation',
            'agentOptions',
            'credits',
            'inactifs',
            'agents',
            'alertes'
        ));
    }
}
