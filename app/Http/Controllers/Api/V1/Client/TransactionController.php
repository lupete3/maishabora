<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Client\TransactionIndexRequest;
use App\Http\Resources\Api\V1\Client\TransactionResource;
use App\Models\Transaction;
use App\Support\TransactionType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    /**
     * GET /api/v1/client/transactions
     *
     * Historique paginé des opérations du membre, de la plus récente à la
     * plus ancienne. Filtres : account_id, from, to, direction, per_page.
     */
    public function index(TransactionIndexRequest $request): AnonymousResourceCollection|JsonResponse
    {
        // 1. Les comptes ouverts du membre : on ne cherche JAMAIS ailleurs.
        //    (transactions.user_id désigne l'agent, pas le client : on passe
        //    donc toujours par account_id.)
        $accountIds = $request->user()->accounts()->active()->pluck('id');

        // 2. Filtre sur un compte : il doit appartenir au membre. Sinon on
        //    répond 404, sans dire si le compte existe chez quelqu'un d'autre.
        if ($accountId = $request->validated('account_id')) {
            if (! $accountIds->contains((int) $accountId)) {
                return response()->json([
                    'code' => 'account_not_found',
                    'message' => 'Compte introuvable.',
                ], 404);
            }

            $accountIds = collect([(int) $accountId]);
        }

        $from = $request->validated('from');
        $to = $request->validated('to');

        $transactions = Transaction::query()
            ->whereIn('account_id', $accountIds)
            // Écritures de caisse internes rattachées par erreur à des comptes
            // membres par l'ancien code (voir TransactionType::INTERNAL)
            ->whereNotIn('type', TransactionType::internalTypes())
            ->when($from, fn (Builder $q) => $q->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($to, fn (Builder $q) => $q->where('created_at', '<=', Carbon::parse($to)->endOfDay()))
            ->when($request->validated('direction'), fn (Builder $q, string $direction) => $this->filterDirection($q, $direction))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($request->perPage())
            ->withQueryString(); // garde les filtres dans les liens de pagination

        return TransactionResource::collection($transactions);
    }

    /**
     * Ne garde que les crédits ou que les débits, y compris les corrections
     * manuelles dont le sens est écrit dans la description.
     */
    private function filterDirection(Builder $query, string $direction): void
    {
        $query->where(function (Builder $q) use ($direction) {
            $q->whereIn('type', TransactionType::typesFor($direction))
                ->orWhere(function (Builder $c) use ($direction) {
                    $c->where('type', TransactionType::correctionType())
                        ->where('description', 'like', TransactionType::correctionPattern($direction));
                });
        });
    }
}
