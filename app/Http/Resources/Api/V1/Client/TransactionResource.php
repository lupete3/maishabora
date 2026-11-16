<?php

namespace App\Http\Resources\Api\V1\Client;

use App\Support\Money;
use App\Support\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une opération de l'historique du membre.
 *
 * @mixin \App\Models\Transaction
 */
class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hidden = ! $request->user()->visible_account;
        $direction = TransactionType::direction($this->resource);

        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'date' => $this->created_at?->toIso8601String(),
            'type' => $this->type,
            'label' => TransactionType::label((string) $this->type),
            // credit = argent reçu, debit = argent sorti, null = sens inconnu
            'direction' => $direction,
            'amount' => Money::format(abs((float) $this->amount)),
            'currency' => $this->currency,
            'balance_after' => $hidden ? null : Money::format($this->balance_after),
            'description' => $this->description,
        ];
    }
}
