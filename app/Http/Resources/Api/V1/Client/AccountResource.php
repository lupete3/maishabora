<?php

namespace App\Http\Resources\Api\V1\Client;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un compte du membre (courant ou épargne, en USD ou CDF).
 *
 * Si l'agence a choisi « Masquer les soldes » sur la fiche du membre
 * (users.visible_account = false), le solde n'est pas envoyé.
 *
 * @mixin \App\Models\Account
 */
class AccountResource extends JsonResource
{
    private const TYPE_LABELS = [
        'current' => 'Compte courant',
        'savings' => 'Compte d\'épargne',
    ];

    public function toArray(Request $request): array
    {
        $hidden = ! $request->user()->visible_account;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => self::TYPE_LABELS[$this->type] ?? ucfirst((string) $this->type),
            'currency' => $this->currency,
            'balance' => $hidden ? null : Money::format($this->balance),
            'balance_hidden' => $hidden,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
