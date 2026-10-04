<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_cards', function (Blueprint $table) {
            $table->foreignId('archived_card_stock_item_id')->nullable()->constrained('card_stock_items');
        });
        // Recuperer aussi les carnets deja annules et physiquement recuperes.
        DB::transaction(function () {
            $sales = DB::table('membership_cards')->whereNotNull('cancelled_at')->whereNotNull('card_stock_item_id')->get();
            foreach ($sales as $sale) {
                $item = DB::table('card_stock_items')->where('id', $sale->card_stock_item_id)->first();
                DB::table('membership_cards')->where('id', $sale->id)->update([
                    'archived_card_stock_item_id' => $sale->card_stock_item_id, 'card_stock_item_id' => null,
                ]);
                if ($item && $item->status === 'cancelled') {
                    $actor = DB::table('card_stock_movements')->where('card_stock_item_id', $item->id)
                        ->where('action', 'cancel_sale')->orderByDesc('id')->value('created_by')
                        ?? DB::table('card_batches')->where('id', $item->card_batch_id)->value('created_by');
                    DB::table('card_stock_items')->where('id', $item->id)->update(['status' => 'in_stock', 'collector_id' => null, 'updated_at' => now()]);
                    DB::table('card_stock_movements')->insert([
                        'card_stock_item_id' => $item->id, 'action' => 'restore_stock', 'from_status' => 'cancelled',
                        'to_status' => 'in_stock', 'from_collector_id' => $item->collector_id, 'to_collector_id' => null,
                        'created_by' => $actor, 'reason' => 'Correction : retour au stock du carnet recupere apres annulation.',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Ne jamais effacer le lien historique apres reutilisation d'un carnet.
        if (DB::table('membership_cards')->whereNotNull('archived_card_stock_item_id')->exists()) {
            throw new RuntimeException('Les ventes archivees doivent etre preservees : retour de migration interdit.');
        }
        Schema::table('membership_cards', fn (Blueprint $table) => $table->dropConstrainedForeignId('archived_card_stock_item_id'));
    }
};
