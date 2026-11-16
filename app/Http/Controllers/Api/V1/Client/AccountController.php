<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Client\AccountResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccountController extends Controller
{
    /**
     * GET /api/v1/client/accounts
     *
     * Comptes ouverts du membre connecté : courant puis épargne, CDF puis USD.
     * Les devises ne sont jamais additionnées entre elles.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $accounts = $request->user()
            ->accounts()      // uniquement les comptes de CE membre
            ->active()        // statut « Actif »
            ->orderBy('type') // current avant savings
            ->orderBy('currency')
            ->get();

        return AccountResource::collection($accounts)->additional([
            'balance_hidden' => ! $request->user()->visible_account,
        ]);
    }
}
