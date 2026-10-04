<?php

namespace App\Services;

use App\Models\CardBatch;
use App\Models\CardStockItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CardStockService
{
    public function receive(array $data): CardBatch
    {
        Gate::authorize('recevoir-stock-carnets');
        $data['reference'] = trim((string) ($data['reference'] ?? ''));
        $data['supplier'] = trim((string) ($data['supplier'] ?? ''));
        $data = Validator::make($data, [
            'reference' => 'required|string|max:100|unique:card_batches,reference',
            'supplier' => 'required|string|max:255', 'received_at' => 'required|date|before_or_equal:today',
            'card_type' => 'required|in:epargne,simple',
            'number_start' => 'required_if:card_type,epargne|nullable|integer|min:1|max:999999999',
            'number_end' => 'required_if:card_type,epargne|nullable|integer|gte:number_start|max:999999999',
            'quantity' => 'required_if:card_type,simple|nullable|integer|min:1|max:10000',
        ])->validate();
        $quantity = $data['card_type'] === 'epargne' ? (int) $data['number_end'] - (int) $data['number_start'] + 1 : (int) $data['quantity'];
        if ($quantity < 1 || $quantity > 10000) {
            throw ValidationException::withMessages(['quantity' => 'Un lot doit contenir entre 1 et 10 000 carnets.']);
        }

        return DB::transaction(function () use ($data, $quantity) {
            if ($data['card_type'] === 'epargne' && CardStockItem::whereIn('printed_number', array_map('strval', range($data['number_start'], $data['number_end'])))->exists()) {
                throw ValidationException::withMessages(['number_start' => 'Cette plage contient des numéros déjà reçus.']);
            }
            $batch = CardBatch::create([
                'reference' => trim($data['reference']), 'supplier' => trim($data['supplier']),
                'received_at' => $data['received_at'], 'card_type' => $data['card_type'], 'quantity' => $quantity,
                'number_start' => $data['card_type'] === 'epargne' ? $data['number_start'] : null,
                'number_end' => $data['card_type'] === 'epargne' ? $data['number_end'] : null, 'created_by' => auth()->id(),
            ]);
            for ($i = 0; $i < $quantity; $i++) {
                $item = $batch->items()->create(['card_type' => $data['card_type'], 'printed_number' => $data['card_type'] === 'epargne' ? (string) ((int) $data['number_start'] + $i) : null, 'status' => 'in_stock']);
                $this->record($item, 'receive', null, null);
            }

            return $batch;
        });
    }

    public function updateNumber(int $id, array $data): void
    {
        Gate::authorize('modifier-numero-carnet');
        $data['reason'] = trim((string) ($data['reason'] ?? ''));
        $data = Validator::make($data, [
            'printed_number' => ['required', 'regex:/^[1-9][0-9]{0,8}$/'],
            'original_number' => 'required|string|max:9',
            'reason' => 'required|string|max:1000',
        ])->validate();
        try {
            DB::transaction(function () use ($id, $data) {
                $item = CardStockItem::whereKey($id)->lockForUpdate()->firstOrFail();
                if ($item->card_type !== 'epargne') {
                    throw ValidationException::withMessages(['printed_number' => 'Un carnet courant utilise son identifiant interne, non modifiable.']);
                }
                if ($item->printed_number !== $data['original_number']) {
                    throw ValidationException::withMessages(['printed_number' => 'Le numéro a changé depuis l’ouverture du formulaire. Rechargez la page.']);
                }
                $number = (string) $data['printed_number'];
                if (CardStockItem::where('printed_number', $number)->where('id', '!=', $id)->exists()) {
                    throw ValidationException::withMessages(['printed_number' => 'Ce numéro appartient déjà à un autre carnet.']);
                }
                $previous = $item->printed_number;
                if ($previous === $number) {
                    return;
                }
                // Le numéro physique peut sortir de la plage initiale du lot historique.
                // Les codes des ventes et les bornes de réception restent des preuves historiques.
                $item->update(['printed_number' => $number]);
                $this->record($item, 'update_number', $item->status, $item->collector_id,
                    $data['reason'].' | Ancien numéro : '.$previous.' | Nouveau numéro : '.$number);
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $exception) {
            // La contrainte unique couvre aussi deux corrections concurrentes.
            throw ValidationException::withMessages(['printed_number' => 'Ce numéro appartient déjà à un autre carnet.']);
        }
    }

    public function updateBatch(int $id, array $data): void
    {
        Gate::authorize('modifier-lot-carnets');
        $data['reference'] = trim((string) ($data['reference'] ?? ''));
        $data['supplier'] = trim((string) ($data['supplier'] ?? ''));
        $data = Validator::make($data, [
            'reference' => ['required', 'string', 'max:100', \Illuminate\Validation\Rule::unique('card_batches', 'reference')->ignore($id)],
            'supplier' => 'required|string|max:255', 'received_at' => 'required|date|before_or_equal:today',
            'reason' => 'required|string|max:1000',
        ])->validate();
        DB::transaction(function () use ($id, $data) {
            $batch = CardBatch::whereKey($id)->lockForUpdate()->firstOrFail();
            $before = $batch->only(['reference', 'supplier', 'received_at']);
            // Le type, la quantite et les numeros physiques ne sont jamais modifies par ce formulaire.
            $batch->update(['reference' => trim($data['reference']), 'supplier' => trim($data['supplier']), 'received_at' => $data['received_at']]);
            $reason = $data['reason'].' | Avant : '.json_encode($before, JSON_UNESCAPED_UNICODE)
                .' | Apres : '.json_encode($batch->only(['reference', 'supplier', 'received_at']), JSON_UNESCAPED_UNICODE);
            foreach ($batch->items()->orderBy('id')->lockForUpdate()->get() as $item) {
                $this->record($item, 'update_batch', $item->status, $item->collector_id, $reason);
            }
        });
    }

    public function move(array $ids, string $action, ?int $collectorId, string $reason): void
    {
        Gate::authorize($action === 'assign' ? 'distribuer-stock-carnets' : 'corriger-stock-carnets');
        if (! in_array($action, ['assign', 'return', 'transfer', 'lost', 'damaged'], true) || ! count($ids) || count($ids) > 10000 || trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Sélection et motif obligatoires.']);
        }
        if (in_array($action, ['assign', 'transfer'], true)) {
            $this->assertCollector($collectorId);
        }
        if (in_array($action, ['lost', 'damaged'], true)) {
            // L'administrateur choisi assure le suivi des pertes et deteriorations.
            $collectorId ??= auth()->user()?->role === 'admin' ? auth()->id() : null;
            if ($collectorId !== null && ! User::whereKey($collectorId)->where('role', 'admin')->where('status', true)->exists()) {
                throw ValidationException::withMessages(['collector_id' => 'Pour une perte ou deterioration, choisissez un administrateur actif.']);
            }
        }
        DB::transaction(function () use ($ids, $action, $collectorId, $reason) {
            // Ordre stable et verrouillage : un carnet ne peut pas être vendu et transféré simultanément.
            $items = CardStockItem::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            if ($items->count() !== count(array_unique($ids))) {
                throw ValidationException::withMessages(['items' => 'Sélection de carnets invalide.']);
            }
            foreach ($items as $item) {
                $allowed = match ($action) {
                    'assign' => ['in_stock'], 'return', 'transfer' => ['with_collector'], default => ['in_stock', 'with_collector']
                };
                if (! in_array($item->status, $allowed, true) || ($action === 'transfer' && $item->collector_id === $collectorId)) {
                    throw ValidationException::withMessages(['items' => "Mouvement impossible pour le carnet {$item->reference}."]);
                }
                $previousStatus = $item->status;
                $previousCollector = $item->collector_id;
                $item->status = match ($action) {
                    'assign', 'transfer' => 'with_collector', 'return' => 'in_stock', default => $action
                };
                $item->collector_id = in_array($action, ['assign', 'transfer', 'lost', 'damaged'], true) ? $collectorId : null;
                $item->save();
                $this->record($item, $action, $previousStatus, $previousCollector, $reason);
            }
        });
    }

    public function assignFromBatch(int $batchId, int $collectorId, ?int $first, ?int $last, ?int $quantity, string $reason): void
    {
        Gate::authorize('distribuer-stock-carnets');
        DB::transaction(function () use ($batchId, $collectorId, $first, $last, $quantity, $reason) {
            $batch = CardBatch::whereKey($batchId)->lockForUpdate()->firstOrFail();
            $query = $batch->items()->where('status', 'in_stock')->orderBy('id');
            if ($batch->card_type === 'epargne') {
                if (! $first || ! $last || $last < $first || $last - $first + 1 > 10000) {
                    throw ValidationException::withMessages(['range_start' => 'Plage de numeros invalide.']);
                }
                $query->whereIn('printed_number', array_map('strval', range($first, $last)));
                $expected = $last - $first + 1;
            } else {
                if (! $quantity || $quantity < 1 || $quantity > 10000) {
                    throw ValidationException::withMessages(['assign_quantity' => 'Quantite invalide.']);
                }
                $query->limit($quantity);
                $expected = $quantity;
            }
            $ids = $query->lockForUpdate()->pluck('id')->all();
            if (count($ids) !== $expected) {
                throw ValidationException::withMessages(['batch_id' => 'Le lot ne contient pas assez de carnets disponibles pour cette selection.']);
            }
            $this->move($ids, 'assign', $collectorId, $reason);
        });
    }

    public function assertCollector(?int $id): void
    {
        if (! $id || ! User::whereKey($id)->whereIn('role', ['recouvreur', 'admin'])->where('status', true)->exists()) {
            throw ValidationException::withMessages(['agent_id' => 'Choisissez un collecteur ou un administrateur actif.']);
        }
    }

    public function lockForSale(int $id, int $collectorId, string $type): CardStockItem
    {
        Gate::authorize('ajouter-carnet', User::class);
        $this->assertCollector($collectorId);
        $item = CardStockItem::whereKey($id)->lockForUpdate()->first();
        if (! $item || $item->status !== 'with_collector' || (int) $item->collector_id !== $collectorId || $item->card_type !== $type || $item->sale()->exists()) {
            throw ValidationException::withMessages(['stock_item_id' => 'Ce carnet n’est plus disponible chez ce collecteur.']);
        }

        return $item;
    }

    public function markSold(CardStockItem $item): void
    {
        Gate::authorize('ajouter-carnet', User::class);
        if ($item->status !== 'with_collector') {
            throw ValidationException::withMessages(['stock_item_id' => 'Seul un carnet attribue peut etre vendu.']);
        }
        $item->status = 'sold';
        $item->save();
        $this->record($item, 'sell', 'with_collector', $item->collector_id);
    }

    public function cancelSale(int $itemId, string $reason, bool $recovered): void
    {
        Gate::authorize('annuler-vente-carnet');
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Le motif est obligatoire.']);
        }
        DB::transaction(function () use ($itemId, $reason, $recovered) {
            $item = CardStockItem::whereKey($itemId)->lockForUpdate()->firstOrFail();
            $sale = $item->sale()->lockForUpdate()->first();
            if ($item->status !== 'sold' || ! $sale || $sale->cancelled_at) {
                throw ValidationException::withMessages(['items' => 'Cette vente ne peut pas être annulée.']);
            }
            if ($sale->contributions()->where('is_paid', true)->exists()) {
                throw ValidationException::withMessages(['items' => 'Des cotisations ont déjà été payées.']);
            }
            $transactions = \App\Models\Transaction::where('membership_card_id', $sale->id)->where('type', 'vente_carte_adhesion')->orderBy('agent_account_id')->get();
            if ($transactions->count() !== 2) {
                throw ValidationException::withMessages(['items' => 'Les deux écritures originales sont nécessaires.']);
            }
            foreach ($transactions as $transaction) {
                $account = \App\Models\AgentAccount::whereKey($transaction->agent_account_id)->lockForUpdate()->firstOrFail();
                $account->balance = round((float) $account->balance - (float) $transaction->amount, 2);
                $account->save();
                \App\Models\Transaction::create([
                    'membership_card_id' => $sale->id, 'agent_account_id' => $account->id,
                    'user_id' => auth()->id(), 'type' => 'annulation_vente_carte_adhesion',
                    'currency' => $transaction->currency, 'amount' => -$transaction->amount,
                    'balance_after' => $account->balance, 'description' => "Annulation carnet #{$sale->code} : {$reason}",
                ]);
            }
            // Archiver le lien physique pour conserver la vente et liberer l'affectation unique.
            $sale->update(['cancelled_at' => now(), 'is_active' => false,
                'archived_card_stock_item_id' => $item->id, 'card_stock_item_id' => null]);
            $previousCollector = $item->collector_id;
            $item->update(['status' => $recovered ? 'in_stock' : 'lost',
                'collector_id' => ! $recovered && auth()->user()?->role === 'admin' ? auth()->id() : null]);
            $this->record($item, 'cancel_sale', 'sold', $previousCollector, $reason);
        });
    }

    public function composeCode(CardStockItem $item, ?string $manual): string
    {
        $manual = trim((string) $manual, " /\t\n\r\0\x0B");
        $code = $item->reference.($manual !== '' ? '/'.$manual : '');
        if (strlen($code) > 255 || preg_match('/[\x00-\x1F\x7F]/', $code)) {
            throw ValidationException::withMessages(['code' => 'Le code est trop long ou contient des caractères invalides.']);
        }

        return $code;
    }

    private function record(CardStockItem $item, string $action, ?string $from, ?int $collector, ?string $reason = null): void
    {
        $item->movements()->create(['action' => $action, 'from_status' => $from, 'to_status' => $item->status,
            'from_collector_id' => $collector, 'to_collector_id' => $item->collector_id,
            'created_by' => auth()->id(), 'reason' => $reason]);
    }
}
