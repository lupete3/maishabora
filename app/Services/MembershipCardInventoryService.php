<?php

namespace App\Services;

use App\Models\MembershipCard;
use App\Models\MembershipCardInventory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MembershipCardInventoryService
{
    public function eligibleCards(int $agentId, ?string $search = null): Collection
    {
        return MembershipCard::query()
            ->with(['member:id,name,postnom,prenom,code'])
            ->withSum(['contributions as theoretical_total' => fn ($query) => $query->where('is_paid', true)], 'amount')
            ->withCount(['contributions as paid_contributions_count' => fn ($query) => $query->where('is_paid', true)])
            ->where('user_id', $agentId)
            ->where('card_type', 'epargne')
            ->active()
            ->when(trim($search ?? '') !== '', function ($query) use ($search) {
                foreach (preg_split('/\s+/u', trim($search)) as $term) {
                    $pattern = "%{$term}%";
                    $query->where(function ($match) use ($pattern) {
                        $match->where('membership_cards.code', 'like', $pattern)
                            ->orWhereHas('member', function ($member) use ($pattern) {
                                $member->where('code', 'like', $pattern)
                                    ->orWhere('name', 'like', $pattern)
                                    ->orWhere('postnom', 'like', $pattern)
                                    ->orWhere('prenom', 'like', $pattern);
                            });
                    });
                }
            })
            ->orderBy('code')
            ->get();
    }

    public function createDraft(int $agentId, string $inventoryDate, array $cardIds): MembershipCardInventory
    {
        return DB::transaction(function () use ($agentId, $inventoryDate, $cardIds) {
            $agent = User::query()
                ->where('role', 'recouvreur')
                ->where('status', true)
                ->find($agentId);

            if (! $agent) {
                throw ValidationException::withMessages([
                    'agent_id' => 'Sélectionnez un agent actif.',
                ]);
            }

            $cards = $this->eligibleCards($agentId);
            $eligibleIds = $cards->modelKeys();
            $submittedIds = array_map('intval', $cardIds);

            sort($eligibleIds);
            sort($submittedIds);

            if (
                $submittedIds === []
                || count($submittedIds) !== count(array_unique($submittedIds))
                || array_diff($submittedIds, $eligibleIds) !== []
            ) {
                throw ValidationException::withMessages([
                    'card_ids' => 'La sélection des carnets a changé ou est invalide. Rechargez la page avant de valider.',
                ]);
            }

            $inventory = MembershipCardInventory::create([
                'inventory_date' => $inventoryDate,
                'agent_id' => $agent->id,
                'agent_name' => $this->personName($agent),
                'created_by' => auth()->id(),
                'status' => 'draft',
            ]);

            foreach ($cards->whereIn('id', $submittedIds) as $card) {
                $theoreticalCents = $this->toCents($card->theoretical_total ?? '0');
                $memberName = $card->member ? $this->personName($card->member) : 'Membre supprimé';

                $inventory->items()->create([
                    'membership_card_id' => $card->id,
                    'card_code' => $card->code,
                    'member_code' => $card->member?->code,
                    'member_name' => $memberName,
                    'currency' => $card->currency,
                    'subscription_amount' => $card->subscription_amount,
                    'paid_contributions_count' => $card->paid_contributions_count,
                    'theoretical_total' => $this->fromCents($theoreticalCents),
                    'physical_total' => null,
                    'difference' => null,
                    'status' => 'pending',
                ]);
            }

            return $inventory;
        });
    }

    public function saveLine(MembershipCardInventory $inventory, int $itemId, ?string $physicalTotal): void
    {
        DB::transaction(function () use ($inventory, $itemId, $physicalTotal) {
            $lockedInventory = MembershipCardInventory::query()->lockForUpdate()->findOrFail($inventory->id);

            if ($lockedInventory->status !== 'draft') {
                throw ValidationException::withMessages([
                    'inventory' => 'Cet inventaire est déjà enregistré et ne peut plus être modifié.',
                ]);
            }

            $item = $lockedInventory->items()->lockForUpdate()->findOrFail($itemId);
            $physicalTotal = $physicalTotal !== null && trim($physicalTotal) !== '' ? $physicalTotal : null;

            if ($physicalTotal === null) {
                $item->update([
                    'physical_total' => null,
                    'difference' => null,
                    'status' => 'not_inventoried',
                ]);

                return;
            }

            $physicalCents = $this->toCents($physicalTotal);
            $theoreticalCents = $this->toCents($item->theoretical_total);

            $item->update([
                'physical_total' => $this->fromCents($physicalCents),
                'difference' => $this->fromCents($physicalCents - $theoreticalCents),
                'status' => 'inventoried',
            ]);
        });
    }

    public function finalize(MembershipCardInventory $inventory): void
    {
        DB::transaction(function () use ($inventory) {
            $lockedInventory = MembershipCardInventory::query()->lockForUpdate()->findOrFail($inventory->id);

            if ($lockedInventory->status !== 'draft') {
                throw ValidationException::withMessages([
                    'inventory' => 'Cet inventaire est déjà enregistré.',
                ]);
            }

            if ($lockedInventory->items()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages([
                    'inventory' => 'Enregistrez une décision pour chaque ligne avant de finaliser l’inventaire.',
                ]);
            }

            $lockedInventory->update([
                'status' => 'recorded',
                'finalized_at' => now(),
            ]);
        });
    }

    private function personName(User $user): string
    {
        return trim(implode(' ', array_filter([
            $user->name,
            $user->postnom,
            $user->prenom,
        ])));
    }

    private function toCents(string|int|float $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function fromCents(int $amount): string
    {
        $absoluteAmount = abs($amount);

        return ($amount < 0 ? '-' : '')
            .intdiv($absoluteAmount, 100)
            .'.'
            .str_pad((string) ($absoluteAmount % 100), 2, '0', STR_PAD_LEFT);
    }
}
