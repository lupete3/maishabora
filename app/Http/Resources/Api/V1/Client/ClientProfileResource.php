<?php

namespace App\Http\Resources\Api\V1\Client;

use App\Support\MemberCode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Définit exactement quels champs du membre sortent vers le mobile.
 * Tout ce qui n'est pas listé ici (pièce d'identité, revenus, mot de passe…)
 * ne quitte jamais le serveur.
 *
 * @mixin \App\Models\User
 */
class ClientProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            // Code court à afficher et à taper : MB000258
            'short_code' => MemberCode::short($this->code),
            'name' => $this->name,
            'postnom' => $this->postnom,
            'prenom' => $this->prenom,
            'full_name' => trim("{$this->name} {$this->postnom} {$this->prenom}"),
            'telephone' => $this->telephone,
            'photo_url' => $this->photo_profil
                ? asset('storage/' . $this->photo_profil)
                : null,
            'date_adhesion' => $this->date_adhesion,
            'must_change_password' => (bool) $this->must_change_password,

            // Agent qui suit le membre (chargé seulement si demandé)
            'agent' => $this->whenLoaded('agent', fn () => $this->agent ? [
                'full_name' => trim("{$this->agent->name} {$this->agent->postnom}"),
                'telephone' => $this->agent->telephone,
            ] : null),
        ];
    }
}
